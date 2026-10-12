<?php

namespace App\Services;

use App\Models\GameTransaction;
use App\Models\LaunchSetting;
use App\Models\User;
use App\Models\VipAccount;
use App\Models\VipBet;
use App\Models\VipQuest;
use App\Models\VipReward;
use App\Models\VipTier;
use App\Models\WalletEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VipService
{
    public function __construct(private LaunchRakebackService $launch) {}

    /** The caller holds the user's wallet lock inside the settlement transaction. */
    public function recordBet(User $user, GameTransaction $transaction, int $amount, array $detail): void
    {
        if ($amount <= 0 || ! empty($detail['tour_id']) || ! empty($detail['bonus_id'])) {
            return;
        }
        if (VipBet::where('game_transaction_id', $transaction->id)->exists()) {
            return;
        }
        $account = VipAccount::firstOrCreate(['user_id' => $user->id]);
        $now = CarbonImmutable::now('UTC');
        $tiers = VipTier::orderBy('level')->get();
        $tier = $tiers->firstWhere('level', $account->level) ?? $tiers->first();
        $roundKey = hash('sha256', json_encode([$user->id, $transaction->agent_code, $transaction->provider_code, $transaction->game_code, $transaction->round_id]));
        $spin = $transaction->game_type === 'slot' && ! VipBet::where('paid_round_key', $roundKey)->exists();
        $contributions = [];
        $receipt = VipBet::create(['user_id' => $user->id, 'game_transaction_id' => $transaction->id, 'amount_cents' => $amount, 'paid_round_key' => $spin ? $roundKey : null]);
        $launchDaily = $this->launch->daily($user);
        foreach (['daily' => $now->startOfDay(), 'weekly' => $now->startOfWeek(CarbonImmutable::MONDAY), 'monthly' => $now->startOfMonth()] as $period => $start) {
            if ($period === 'daily' && $launchDaily) {
                continue;
            }
            $end = match ($period) {
                'daily' => $start->addDay(), 'weekly' => $start->addWeek(), 'monthly' => $start->addMonth(),
            };
            $reward = VipReward::firstOrCreate(['user_id' => $user->id, 'key' => 'rakeback:'.$period.':'.$start->toDateString()], ['kind' => 'rakeback', 'ends_at' => $end, 'snapshot' => ['name' => ucfirst($period).' rakeback · '.$start->toDateString(), 'period' => $period, 'starts_at' => $start->toIso8601String()]]);
            $contributions[$reward->id] = ['numerator' => $amount * $tier->{$period.'_bps'}, 'progress' => $amount];
            $reward->numerator += $amount * $tier->{$period.'_bps'};
            $reward->amount_cents = intdiv($reward->numerator, 10000);
            $reward->progress += $amount;
            $reward->save();
        }
        foreach (VipQuest::where('enabled', true)->get() as $quest) {
            $this->enrollQuest($user, $quest, $now);
        }
        $quests = VipReward::where('user_id', $user->id)->where('kind', 'quest')->where('status', 'active')->where('ends_at', '>', $now)->get();
        foreach ($quests as $reward) {
            $rule = $reward->snapshot;
            if ($amount < $rule['min_bet_cents'] || (! empty($rule['provider_code']) && $rule['provider_code'] !== $transaction->provider_code) || (! empty($rule['game_code']) && $rule['game_code'] !== $transaction->game_code)) {
                continue;
            }
            $increment = $rule['kind'] === 'wager' ? $amount : (int) $spin;
            $contributions[$reward->id] = ['numerator' => 0, 'progress' => $increment];
            $reward->progress += $increment;
            if ($reward->progress >= $rule['target']) {
                $reward->status = 'available';
            }
            $reward->save();
        }
        $account->wager_cents += $amount;
        foreach ($tiers as $newTier) {
            if ($newTier->level > $account->level && $newTier->threshold_cents <= $account->wager_cents) {
                $levelReward = VipReward::firstOrCreate(['user_id' => $user->id, 'key' => 'level:'.$newTier->level], ['kind' => 'level', 'status' => 'available', 'amount_cents' => $newTier->bonus_cents, 'snapshot' => ['name' => 'Level '.$newTier->level.' · '.$newTier->name]]);
                if ($levelReward->status === 'revoked') {
                    $levelReward->update(['status' => 'available', 'claimed_at' => null]);
                }
                $account->level = $newTier->level;
            }
        }
        $account->save();
        $receipt->update(['contributions' => $contributions]);
    }

    /** Undo only the recorded contribution, with claimed overpayments recovered or held against future rewards. */
    public function reverseBet(User $user, int $transactionId, ?int $refundCents = null): void
    {
        $bet = VipBet::where('game_transaction_id', $transactionId)->first();
        if (! $bet || $bet->reversed_at) {
            return;
        }
        $account = VipAccount::where('user_id', $user->id)->firstOrFail();
        $priorDebt = $account->reward_debt_cents ?? 0;
        $refundCents = min($refundCents ?? $bet->amount_cents, $bet->amount_cents);
        if ($refundCents <= 0) {
            return;
        }
        $remaining = [];
        foreach ($bet->contributions ?? [] as $id => $contribution) {
            $reward = VipReward::findOrFail($id);
            $original = $contribution;
            $contribution['numerator'] = intdiv($contribution['numerator'], $bet->amount_cents) * $refundCents;
            $contribution['progress'] = $reward->kind === 'quest' && $reward->snapshot['kind'] === 'spins'
                ? ($refundCents === $bet->amount_cents ? $contribution['progress'] : 0)
                : intdiv($contribution['progress'], $bet->amount_cents) * $refundCents;
            $remaining[$id] = ['numerator' => $original['numerator'] - $contribution['numerator'], 'progress' => $original['progress'] - $contribution['progress']];
            $before = $reward->amount_cents;
            $wasClaimed = $reward->status === 'claimed';
            $reward->progress = max(0, $reward->progress - $contribution['progress']);
            if ($reward->kind === 'rakeback') {
                $reward->numerator = max(0, $reward->numerator - $contribution['numerator']);
                $reward->amount_cents = intdiv($reward->numerator, 10000);
            } elseif ($reward->progress < $reward->snapshot['target']) {
                $reward->status = 'active';
                $reward->claimed_at = null;
            }
            if ($wasClaimed) {
                $owed = $reward->kind === 'rakeback' ? $before - $reward->amount_cents : ($reward->status === 'active' ? $before : 0);
                $account->reward_debt_cents += $owed;
                if ($reward->status === 'active') {
                    $snapshot = $reward->snapshot;
                    $snapshot['claim_version'] = ($snapshot['claim_version'] ?? 0) + 1;
                    $reward->snapshot = $snapshot;
                }
            }
            $reward->save();
        }
        $account->wager_cents = max(0, $account->wager_cents - $refundCents);
        $account->level = VipTier::where('threshold_cents', '<=', $account->wager_cents)->max('level') ?? 1;
        foreach (VipReward::where('user_id', $user->id)->where('kind', 'level')->whereIn('status', ['available', 'claimed'])->get() as $reward) {
            if ((int) substr($reward->key, 6) <= $account->level) {
                continue;
            }
            if ($reward->status === 'claimed') {
                $account->reward_debt_cents += $reward->amount_cents;
                $snapshot = $reward->snapshot;
                $snapshot['claim_version'] = ($snapshot['claim_version'] ?? 0) + 1;
                $reward->snapshot = $snapshot;
                $reward->save();
            }
            $reward->update(['status' => 'revoked']);
        }
        $recover = min($account->reward_debt_cents - $priorDebt, $user->eur_balance_cents);
        if ($recover > 0) {
            $before = $user->eur_balance_cents;
            $user->eur_balance_cents -= $recover;
            $user->save();
            $account->reward_debt_cents -= $recover;
            WalletEntry::create(['user_id' => $user->id, 'reference' => 'vip-reversal:'.$bet->id.':'.$bet->amount_cents, 'kind' => 'vip_reversal', 'delta_cents' => -$recover, 'balance_before_cents' => $before, 'balance_after_cents' => $user->eur_balance_cents]);
        }
        $account->save();
        $left = $bet->amount_cents - $refundCents;
        $bet->update(['amount_cents' => $left, 'contributions' => $remaining, 'reversed_at' => $left === 0 ? now() : null, 'paid_round_key' => $left === 0 ? null : $bet->paid_round_key]);
        $this->launch->reverseBet($user, $transactionId, $refundCents);
    }

    private function enrollQuest(User $user, VipQuest $quest, CarbonImmutable $now): VipReward
    {
        return VipReward::firstOrCreate(['user_id' => $user->id, 'key' => 'quest:'.$quest->id.':'.$now->toDateString()], ['kind' => 'quest', 'amount_cents' => $quest->reward_cents, 'ends_at' => $now->startOfDay()->addDay(), 'snapshot' => $quest->only(['name', 'kind', 'target', 'min_bet_cents', 'provider_code', 'game_code'])]);
    }

    public function checkin(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $account = VipAccount::firstOrCreate(['user_id' => $user->id]);
            $today = CarbonImmutable::now('UTC')->startOfDay();
            if ($account->last_checkin_date?->toDateString() === $today->toDateString()) {
                return;
            }
            $account->streak = $account->last_checkin_date?->toDateString() === $today->subDay()->toDateString() ? ($account->streak % 7) + 1 : 1;
            $account->last_checkin_date = $today;
            $account->save();
            $tier = VipTier::where('level', $account->level)->firstOrFail();
            $reward = VipReward::firstOrCreate(['user_id' => $user->id, 'key' => 'checkin:'.$today->toDateString()], ['kind' => 'checkin', 'status' => 'available', 'amount_cents' => $tier->checkin_cents * $account->streak, 'snapshot' => ['name' => 'Daily check-in · day '.$account->streak]]);
            $this->credit($locked, $reward);
        }, 3);
    }

    public function claim(User $user, int $rewardId): void
    {
        DB::transaction(function () use ($user, $rewardId): void {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $reward = VipReward::where('user_id', $user->id)->lockForUpdate()->findOrFail($rewardId);
            if ($reward->status === 'claimed') {
                return;
            }
            if (! ($reward->status === 'available' || ($reward->kind === 'rakeback' && $reward->ends_at->lte(CarbonImmutable::now('UTC'))))) {
                throw ValidationException::withMessages(['reward' => 'This reward is not ready yet.']);
            }
            if ($reward->claimable_cents <= 0) {
                throw ValidationException::withMessages(['reward' => 'No whole EUR cent has accrued for this reward.']);
            }
            $this->credit($locked, $reward);
        }, 3);
    }

    private function credit(User $user, VipReward $reward): void
    {
        if ($reward->status === 'claimed') {
            return;
        }
        $account = VipAccount::where('user_id', $user->id)->first();
        $payable = $reward->amount_cents - ($reward->snapshot['credited_cents'] ?? 0);
        $offset = min($account?->reward_debt_cents ?? 0, $payable);
        if ($offset > 0) {
            $account->reward_debt_cents -= $offset;
            $account->save();
        }
        $before = $user->eur_balance_cents;
        $user->eur_balance_cents += $payable - $offset;
        $user->save();
        WalletEntry::create(['user_id' => $user->id, 'reference' => 'vip:'.$reward->id.(! empty($reward->snapshot['claim_version']) ? ':'.$reward->snapshot['claim_version'] : ''), 'kind' => 'vip_'.$reward->kind, 'delta_cents' => $payable - $offset, 'balance_before_cents' => $before, 'balance_after_cents' => $user->eur_balance_cents]);
        if (! empty($reward->snapshot['launch'])) {
            $snapshot = $reward->snapshot;
            $snapshot['credited_cents'] = $reward->amount_cents;
            $reward->snapshot = $snapshot;
        }
        $reward->update(['status' => 'claimed', 'claimed_at' => CarbonImmutable::now('UTC')]);
    }

    public function dashboard(?User $user): array
    {
        $tiers = VipTier::orderBy('level')->get();
        $account = $user ? VipAccount::where('user_id', $user->id)->first() : null;
        $now = CarbonImmutable::now('UTC');
        $tier = $tiers->firstWhere('level', $account?->level ?? 1);
        $nextCheckin = $account?->last_checkin_date?->toDateString() === $now->subDay()->toDateString() ? ($account->streak % 7) + 1 : 1;
        $todayClaimed = $account?->last_checkin_date?->toDateString() === $now->toDateString();
        $cycleDay = $todayClaimed ? $account->streak : $nextCheckin;
        $cycleStart = $now->startOfDay()->subDays($cycleDay - 1);
        $checkinKeys = collect(range(1, 7))->map(fn ($day) => 'checkin:'.$cycleStart->addDays($day - 1)->toDateString());
        $checkins = $user ? VipReward::where('user_id', $user->id)->whereIn('key', $checkinKeys)->get()->keyBy('key') : collect();
        $checkinDays = collect(range(1, 7))->map(function ($day) use ($checkins, $checkinKeys, $tier, $cycleDay): array {
            $reward = $checkins->get($checkinKeys[$day - 1]);

            return ['day' => $day, 'amount_cents' => $reward?->amount_cents ?? $tier->checkin_cents * $day, 'collected' => $reward?->status === 'claimed', 'is_today' => $day === $cycleDay];
        });
        $quests = $user ? VipReward::where('user_id', $user->id)->where('kind', 'quest')->where('key', 'like', '%:'.$now->toDateString())->get()->keyBy('key') : collect();
        $enrolledIds = $quests->keys()->map(fn ($key) => (int) explode(':', $key)[1])->all();
        $questCards = VipQuest::where(fn ($query) => $query->where('enabled', true)->orWhereIn('id', $enrolledIds))->get()->map(function ($quest) use ($quests, $now): array {
            $reward = $quests->get('quest:'.$quest->id.':'.$now->toDateString());

            return ['id' => $reward?->id, 'quest_id' => $quest->id, 'rule' => $reward?->snapshot ?? $quest->only(['name', 'kind', 'target', 'min_bet_cents', 'provider_code', 'game_code']), 'progress' => $reward?->progress ?? 0, 'reward_cents' => $reward?->amount_cents ?? $quest->reward_cents, 'status' => $reward?->status ?? 'active'];
        });
        $ready = $user ? VipReward::where('user_id', $user->id)->where('amount_cents', '>', 0)->where(function ($query) use ($now): void {
            $query->where('status', 'available')->orWhere(fn ($query) => $query->where('kind', 'rakeback')->where('status', 'active')->where('ends_at', '<=', $now));
        })->orderBy('id')->paginate(12)->withQueryString() : null;

        return [
            'launchRakeback' => LaunchSetting::find(1)?->only(['rakeback_enabled', 'rakeback_basis', 'rakeback_bps', 'rakeback_starts_at', 'rakeback_ends_at']),
            'tiers' => $tiers, 'membership' => ['level' => $account?->level ?? 1, 'wager_cents' => $account?->wager_cents ?? 0, 'streak' => $account?->streak ?? 0, 'reward_debt_cents' => $account?->reward_debt_cents ?? 0, 'checkin_today' => $todayClaimed, 'next_checkin_day' => $todayClaimed ? $account->streak : $nextCheckin, 'checkin_base_cents' => $tier->checkin_cents],
            'checkinDays' => $checkinDays,
            'quests' => $questCards, 'readyRewards' => $ready,
            'accruing' => $user ? VipReward::where('user_id', $user->id)->where('kind', 'rakeback')->where('ends_at', '>', $now)->get() : [],
            'rewardHistory' => $user ? VipReward::where('user_id', $user->id)->where('status', 'claimed')->latest('id')->limit(15)->get(['id', 'kind', 'amount_cents', 'snapshot', 'claimed_at']) : [],
            'resets_at' => $now->startOfDay()->addDay()->toIso8601String(),
        ];
    }
}

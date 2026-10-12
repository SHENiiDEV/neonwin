<?php

namespace App\Services;

use App\Models\GameTransaction;
use App\Models\LaunchSetting;
use App\Models\User;
use App\Models\VipAccount;
use App\Models\VipBet;
use App\Models\VipReward;
use App\Models\WalletEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LaunchRakebackService
{
    /** A daily bucket fixes its terms; later admin edits never change accrued rewards. */
    public function daily(User $user): ?VipReward
    {
        $start = CarbonImmutable::now('UTC')->startOfDay();
        $existing = VipReward::where('user_id', $user->id)->where('key', 'launch:daily:'.$start->toDateString())->first();
        if ($existing) {
            return $existing;
        }
        $settings = LaunchSetting::find(1);
        if (! $settings?->rakeback_enabled || ! $settings->rakeback_starts_at || ! $settings->rakeback_ends_at || $start->lt($settings->rakeback_starts_at) || $start->gte($settings->rakeback_ends_at)) {
            return null;
        }

        return VipReward::create(['user_id' => $user->id, 'key' => 'launch:daily:'.$start->toDateString(), 'kind' => 'rakeback', 'ends_at' => $start->addDay(), 'snapshot' => [
            'name' => 'Launch daily rakeback · '.$start->toDateString(), 'period' => 'daily', 'launch' => true, 'basis' => $settings->rakeback_basis,
            'bps' => $settings->rakeback_bps, 'starts_at' => $start->toIso8601String(), 'credited_cents' => 0,
        ]]);
    }

    /** Called inside the settlement transaction while the user's wallet is locked. */
    public function record(User $user, GameTransaction $transaction, int $bet, int $win): void
    {
        $eligible = VipBet::where('game_transaction_id', $transaction->id)->exists();
        $paidRound = $eligible || GameTransaction::where('user_id', $user->id)->where('agent_code', $transaction->agent_code)
            ->where('provider_code', $transaction->provider_code)->where('game_code', $transaction->game_code)->where('round_id', $transaction->round_id)
            ->whereIn('id', VipBet::select('game_transaction_id')->where('user_id', $user->id)->where('amount_cents', '>', 0))->exists();
        if (! $eligible && ! $paidRound) {
            return;
        }
        $reward = $this->daily($user);
        if (! $reward) {
            return;
        }
        $stake = $eligible ? $bet : 0;
        $payout = $reward->snapshot['basis'] === 'net_loss' && $paidRound ? $win : 0;
        DB::table('launch_rakeback_entries')->insertOrIgnore(['game_transaction_id' => $transaction->id, 'vip_reward_id' => $reward->id, 'stake_cents' => $stake, 'payout_cents' => $payout, 'basis_cents' => $stake - $payout]);
        $this->recalculate($user, $reward, 'event:'.$transaction->id);
    }

    public function reverseBet(User $user, int $transactionId, int $refund): void
    {
        foreach (DB::table('launch_rakeback_entries')->where('game_transaction_id', $transactionId)->get() as $entry) {
            $remaining = max(0, $entry->stake_cents - $refund);
            DB::table('launch_rakeback_entries')->where('id', $entry->id)->update(['stake_cents' => $remaining, 'basis_cents' => $remaining - $entry->payout_cents]);
            $this->recalculate($user, VipReward::findOrFail($entry->vip_reward_id), 'refund:'.$transactionId.':'.$remaining);
        }
    }

    public function reverseTransaction(User $user, int $transactionId): void
    {
        foreach (DB::table('launch_rakeback_entries')->where('game_transaction_id', $transactionId)->get() as $entry) {
            DB::table('launch_rakeback_entries')->where('id', $entry->id)->update(['stake_cents' => 0, 'payout_cents' => 0, 'basis_cents' => 0]);
            $this->recalculate($user, VipReward::findOrFail($entry->vip_reward_id), 'rollback:'.$transactionId);
        }
    }

    private function recalculate(User $user, VipReward $reward, string $reference): void
    {
        $basis = (int) DB::table('launch_rakeback_entries')->where('vip_reward_id', $reward->id)->sum('basis_cents');
        $reward->progress = max(0, $basis);
        $reward->amount_cents = intdiv(max(0, $basis) * $reward->snapshot['bps'], 10000);
        $snapshot = $reward->snapshot;
        $credited = $snapshot['credited_cents'];
        if ($reward->amount_cents < $credited) {
            $owed = $credited - $reward->amount_cents;
            $account = VipAccount::firstOrCreate(['user_id' => $user->id]);
            $recover = min($owed, $user->eur_balance_cents);
            $before = $user->eur_balance_cents;
            $user->eur_balance_cents -= $recover;
            $user->save();
            $account->reward_debt_cents += $owed - $recover;
            $account->save();
            WalletEntry::create(['user_id' => $user->id, 'reference' => 'launch-adjust:'.$reward->id.':'.$reference, 'kind' => 'vip_reversal', 'delta_cents' => -$recover, 'balance_before_cents' => $before, 'balance_after_cents' => $user->eur_balance_cents]);
            $snapshot['credited_cents'] = $reward->amount_cents;
        } elseif ($reward->amount_cents > $credited && $reward->status === 'claimed') {
            $reward->status = 'active';
            $snapshot['claim_version'] = ($snapshot['claim_version'] ?? 0) + 1;
        }
        if (($credited > 0 || $reward->claimed_at) && $reward->amount_cents <= $snapshot['credited_cents']) {
            $reward->status = 'claimed';
        }
        $reward->snapshot = $snapshot;
        $reward->save();
    }
}

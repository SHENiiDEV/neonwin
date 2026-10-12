<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\NexusOperation;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\User;
use App\Models\WalletEntry;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    /** Must run in the account creation transaction. */
    public function reserveRegistration(User $user, ?string $code): ?PromotionRedemption
    {
        if (! $code) {
            return null;
        }
        $promotion = Promotion::where('code', strtoupper(trim($code)))->lockForUpdate()->first();
        if (! $promotion || ! $promotion->available() || $promotion->kind === 'first_deposit' || ($promotion->redemption_limit !== null && $promotion->redemptions()->count() >= $promotion->redemption_limit)) {
            throw ValidationException::withMessages(['promo_code' => 'This promo code is unavailable or has reached its limit.']);
        }
        $snapshot = $promotion->snapshot();
        if ($promotion->kind === 'free_spins') {
            $snapshot['expiration_time'] = now()->addHours($promotion->reward['expiry_hours'])->utc()->toISOString();
        }

        return PromotionRedemption::create(['promotion_id' => $promotion->id, 'user_id' => $user->id, 'request_id' => (string) Str::uuid(), 'snapshot' => $snapshot]);
    }

    /** Runs inside the verified deposit transaction, with the player already locked. */
    public function awardFirstDeposit(Deposit $deposit, User $user): void
    {
        if (Deposit::where('user_id', $user->id)->where('status', 'confirmed')->whereKeyNot($deposit->id)->exists()) {
            return;
        }
        $offers = PromotionRedemption::where('user_id', $user->id)->where('status', 'pending')->lockForUpdate()->get()->filter(fn ($redemption) => $redemption->snapshot['kind'] === 'deposit_bonus');
        $global = Promotion::where('kind', 'first_deposit')->lockForUpdate()->first();
        if ($global?->available() && ($global->redemption_limit === null || $global->redemptions()->count() < $global->redemption_limit)) {
            $offers->push(PromotionRedemption::firstOrCreate(['promotion_id' => $global->id, 'user_id' => $user->id], ['request_id' => (string) Str::uuid(), 'snapshot' => $global->snapshot()]));
        }
        $winner = null;
        $best = 0;
        foreach ($offers as $offer) {
            $terms = $offer->snapshot;
            $bonus = $deposit->amount_cents >= $terms['min_deposit_cents'] ? intdiv($deposit->amount_cents * $terms['percent_bps'] + 5000, 10000) : 0;
            if ($terms['max_bonus_cents'] !== null) {
                $bonus = min($bonus, $terms['max_bonus_cents']);
            }
            if ($bonus > $best) {
                $winner = $offer;
                $best = $bonus;
            }
        }
        foreach ($offers as $offer) {
            $offer->update(['deposit_id' => $deposit->id, 'status' => $winner?->id === $offer->id ? 'awarded' : 'not_applied', 'bonus_cents' => $winner?->id === $offer->id ? $best : 0, 'message' => $winner?->id === $offer->id ? 'Credited after verified payment.' : 'A higher bonus applied, or the first deposit did not meet this offer’s minimum.']);
        }
        if ($best > 0) {
            $before = $user->eur_balance_cents;
            $user->eur_balance_cents += $best;
            $user->save();
            WalletEntry::create(['user_id' => $user->id, 'reference' => 'deposit-bonus:'.$deposit->reference, 'kind' => 'deposit_bonus', 'delta_cents' => $best, 'balance_before_cents' => $before, 'balance_after_cents' => $user->eur_balance_cents]);
        }
    }

    public function issueFreeSpins(PromotionRedemption $redemption, User $actor): PromotionRedemption
    {
        abort_unless($redemption->snapshot['kind'] === 'free_spins', 422);
        if (config('gaming.provider') === 'flexrix') {
            $redemption->update(['message' => 'This reserved offer uses retired Nexus game and bet-level identifiers. Configure a Flexrix offer before issuing FreeSpins.']);

            return $redemption;
        }
        $existing = NexusOperation::where('external_id', $redemption->request_id)->first();
        if ($existing) {
            return $this->recordOperation($redemption, $existing);
        }
        if (now()->gte($redemption->snapshot['expiration_time'])) {
            $redemption->update(['status' => 'expired', 'message' => 'The reserved FreeSpins delivery window expired. No Nexus request was sent.']);

            return $redemption;
        }
        if (! config('nexus.eur_agent_confirmed') || ! config('nexus.rewards_enabled')) {
            $redemption->update(['message' => 'FreeSpins are reserved. Nexus reward capability must be enabled before delivery.']);

            return $redemption;
        }
        PromotionRedemption::whereKey($redemption->id)->where('status', 'issuing')->where('updated_at', '<', now()->subMinutes(2))->update(['status' => 'pending']);
        $claimed = PromotionRedemption::whereKey($redemption->id)->where('status', 'pending')->update(['status' => 'issuing']);
        if (! $claimed) {
            return $redemption->refresh();
        }
        $reward = $redemption->snapshot['reward'];
        try {
            $operation = app(NexusOperations::class)->create($actor, [
                'request_id' => $redemption->request_id, 'method' => 'tour_create', 'user_codes' => [$redemption->user->user_code], 'provider_code' => 'PRAGMATIC',
                'game_code' => $reward['game_code'], 'amount' => $reward['amount'], 'bet_level' => $reward['bet_level'], 'spin_count' => $reward['spin_count'], 'expiration_time' => $redemption->snapshot['expiration_time'],
            ]);
        } catch (ValidationException $exception) {
            $redemption->update(['status' => 'pending', 'message' => collect($exception->errors())->flatten()->first()]);

            return $redemption;
        }

        return $this->recordOperation($redemption, $operation);
    }

    private function recordOperation(PromotionRedemption $redemption, NexusOperation $operation): PromotionRedemption
    {
        $redemption->update(['status' => in_array($operation->status, ['active', 'running', 'finished'], true) ? 'issued' : $operation->status, 'message' => 'Nexus request '.$operation->external_id.': '.$operation->status]);

        return $redemption;
    }
}

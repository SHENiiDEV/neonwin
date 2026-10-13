<?php

namespace App\Services;

use App\Models\AdminAudit;
use App\Models\User;
use App\Models\WalletEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminWalletAdjustment
{
    public function adjust(User $actor, User $target, int $deltaCents, string $reason, string $requestId): void
    {
        $reference = 'admin-wallet:'.$target->id.':'.$requestId;

        DB::transaction(function () use ($actor, $target, $deltaCents, $reason, $reference): void {
            $user = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $existing = WalletEntry::where('reference', $reference)->first();

            if ($existing) {
                $audit = AdminAudit::where('action', 'admin_wallet_adjusted')->where('target', $reference)->first();
                if ($existing->user_id !== $user->id || $existing->delta_cents !== $deltaCents || $audit?->actor_id !== $actor->id || ($audit?->after['reason'] ?? null) !== $reason) {
                    throw ValidationException::withMessages(['amount' => 'This request was already used for a different adjustment.']);
                }

                return;
            }

            $before = $user->eur_balance_cents;
            $after = $before + $deltaCents;
            if ($after < 0) {
                throw ValidationException::withMessages(['amount' => 'The debit exceeds the available EUR balance.']);
            }

            $user->eur_balance_cents = $after;
            $user->save();

            WalletEntry::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'kind' => $deltaCents > 0 ? 'admin_credit' : 'admin_debit',
                'delta_cents' => $deltaCents,
                'balance_before_cents' => $before,
                'balance_after_cents' => $after,
            ]);
            AdminAudit::create([
                'actor_id' => $actor->id,
                'action' => 'admin_wallet_adjusted',
                'target' => $reference,
                'before' => ['balance_cents' => $before],
                'after' => ['target_user_id' => $user->id, 'balance_cents' => $after, 'delta_cents' => $deltaCents, 'reason' => $reason],
            ]);
        }, 3);
    }
}

<?php

namespace App\Services;

use App\Jobs\SendDepositConfirmation;
use App\Models\Deposit;
use App\Models\User;
use App\Models\WalletEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

/** Called only by a trusted server-side payment adapter after verifying the provider event. */
class VerifiedDepositService
{
    public function confirm(string $reference, string $provider, string $eventId, int $amountCents, string $currency): Deposit
    {
        if ($provider === '' || $eventId === '' || $currency !== 'EUR') {
            throw new LogicException('Invalid payment confirmation.');
        }

        [$deposit, $newlyConfirmed] = DB::transaction(function () use ($reference, $provider, $eventId, $amountCents): array {
            $deposit = Deposit::where('reference', $reference)->lockForUpdate()->firstOrFail();
            if ($deposit->amount_cents !== $amountCents || $deposit->payment_provider !== $provider) {
                throw new LogicException('Payment does not match the deposit.');
            }
            if ($deposit->status === 'confirmed') {
                if ($deposit->payment_event_id !== $eventId) {
                    throw new LogicException('Payment confirmation conflict.');
                }

                return [$deposit, null];
            }
            if ($deposit->status !== 'pending') {
                throw new LogicException('This deposit is not awaiting payment.');
            }
            $user = User::whereKey($deposit->user_id)->lockForUpdate()->firstOrFail();
            $before = $user->eur_balance_cents;
            $user->eur_balance_cents += $amountCents;
            $user->save();
            WalletEntry::create(['user_id' => $user->id, 'reference' => 'deposit:'.$deposit->reference, 'kind' => 'deposit', 'delta_cents' => $amountCents, 'balance_before_cents' => $before, 'balance_after_cents' => $user->eur_balance_cents]);
            $deposit->update(['status' => 'confirmed', 'payment_event_id' => $eventId, 'confirmed_at' => now()]);
            app(PromotionService::class)->awardFirstDeposit($deposit, $user);

            return [$deposit, true];
        }, 3);

        if ($newlyConfirmed) {
            try {
                SendDepositConfirmation::dispatch($deposit->id)->afterCommit();
            } catch (Throwable $exception) {
                Log::warning('Deposit confirmation email could not be queued.', ['deposit_id' => $deposit->id, 'exception' => $exception->getMessage()]);
            }
        }

        return $deposit;
    }
}

<?php

namespace App\Services;

use App\Models\AdminAudit;
use App\Models\LaunchSetting;
use App\Models\User;
use App\Models\WalletEntry;
use App\Models\WheelFundEntry;
use App\Models\WheelSpin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WheelService
{
    public function spin(User $user, string $requestId): WheelSpin
    {
        return DB::transaction(function () use ($user, $requestId): WheelSpin {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = WheelSpin::where('user_id', $user->id)->where('request_id', $requestId)->first();
            if ($existing) {
                return $existing;
            }
            $settings = LaunchSetting::lockForUpdate()->findOrFail(1);
            $last = WheelSpin::where('user_id', $user->id)->latest('id')->first();
            if ($last && $last->created_at->addHours(24)->isFuture()) {
                throw ValidationException::withMessages(['wheel' => 'Your next spin is available 24 hours after your last spin.']);
            }
            if (! $settings->wheel_enabled || $settings->wheel_ends_at?->isPast() || $settings->wheel_budget_cents < max(array_column($settings->wheel_prizes, 'cents'))) {
                throw ValidationException::withMessages(['wheel' => 'The daily wheel is currently paused. Please check back later.']);
            }
            $ticket = random_int(1, array_sum(array_column($settings->wheel_prizes, 'weight')));
            $index = 0;
            foreach ($settings->wheel_prizes as $i => $prize) {
                $ticket -= $prize['weight'];
                if ($ticket <= 0) {
                    $index = $i;
                    break;
                }
            }
            $amount = $settings->wheel_prizes[$index]['cents'];
            $spin = WheelSpin::create(['user_id' => $user->id, 'request_id' => $requestId, 'prize_index' => $index, 'amount_cents' => $amount, 'prizes' => $settings->wheel_prizes]);
            $before = $user->eur_balance_cents;
            $user->eur_balance_cents += $amount;
            $user->save();
            $settings->wheel_budget_cents -= $amount;
            $settings->save();
            WalletEntry::create(['user_id' => $user->id, 'reference' => 'wheel:'.$spin->id, 'kind' => 'daily_wheel', 'delta_cents' => $amount, 'balance_before_cents' => $before, 'balance_after_cents' => $user->eur_balance_cents]);
            WheelFundEntry::create(['user_id' => $user->id, 'reference' => 'spin:'.$spin->id, 'delta_cents' => -$amount, 'balance_after_cents' => $settings->wheel_budget_cents]);

            return $spin;
        }, 3);
    }

    public function fund(User $admin, int $amount, string $requestId): void
    {
        DB::transaction(function () use ($admin, $amount, $requestId): void {
            $admin = User::whereKey($admin->id)->lockForUpdate()->firstOrFail();
            $reference = 'fund:'.$admin->id.':'.$requestId;
            $existing = WheelFundEntry::where('reference', $reference)->first();
            if ($existing) {
                if ($existing->delta_cents !== $amount) {
                    throw ValidationException::withMessages(['amount' => 'This request was already used with a different amount.']);
                }

                return;
            }
            $settings = LaunchSetting::lockForUpdate()->findOrFail(1);
            if ($amount <= 0 || $amount > $admin->eur_balance_cents) {
                throw ValidationException::withMessages(['amount' => 'Enter an amount within your available EUR wallet balance.']);
            }
            $before = $admin->eur_balance_cents;
            $admin->eur_balance_cents -= $amount;
            $admin->save();
            $settings->wheel_budget_cents += $amount;
            $settings->save();
            WalletEntry::create(['user_id' => $admin->id, 'reference' => $reference, 'kind' => 'wheel_funding', 'delta_cents' => -$amount, 'balance_before_cents' => $before, 'balance_after_cents' => $admin->eur_balance_cents]);
            WheelFundEntry::create(['user_id' => $admin->id, 'reference' => $reference, 'delta_cents' => $amount, 'balance_after_cents' => $settings->wheel_budget_cents]);
            AdminAudit::create(['actor_id' => $admin->id, 'action' => 'wheel_funded', 'target' => $reference, 'after' => ['amount_cents' => $amount]]);
        }, 3);
    }
}

<?php

namespace App\Http\Controllers;

use App\Mail\TopUpInvoiceMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DepositController extends Controller
{
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:1000000000',
            'method' => 'required|string', // card, daily_sc_bonus
            'pack_name' => 'nullable|string|max:100',
            'price' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Please log in to claim or add SC.'], 401);
        }

        if ($validated['method'] === 'daily_sc_bonus') {
            if (! $user->canClaimDailyBonus()) {
                $nextAvailable = $user->nextDailyBonusAt();
                $diff = $nextAvailable ? $nextAvailable->diffForHumans(null, true) : 'a few hours';

                return response()->json([
                    'status' => 'error',
                    'message' => "You have already claimed your daily free 1.00 SC. Next claim available in {$diff}.",
                ], 422);
            }

            $dailySc = 1.00;
            $user->increment('game_balance', $dailySc);
            $user->update(['last_daily_bonus_at' => now()]);
            $user->awardVipXp(10);

            return response()->json([
                'status' => 'success',
                'new_balance' => (float) $user->game_balance,
                'can_claim_daily_bonus' => false,
                'next_daily_bonus_at' => $user->nextDailyBonusAt()?->toIso8601String(),
                'message' => 'Successfully claimed your free 1.00 SC Daily Bonus! Next claim available in 24 hours.',
            ]);
        }

        $scCredited = (float) $validated['amount'];
        $user->increment('game_balance', $scCredited);
        $user->awardVipXp($scCredited * 10);

        $price = (float) ($validated['price'] ?? round($scCredited * 2, 2));
        $packName = $validated['pack_name'] ?? 'Sweeps Coins Pack';
        $currency = $validated['currency'] ?? 'EUR';

        // Send Top-Up Confirmation with PDF Invoice
        try {
            Mail::to($user->email)->send(
                new TopUpInvoiceMail(
                    user: $user,
                    coins: $scCredited,
                    price: $price,
                    currency: $currency,
                    packName: $packName,
                    newBalance: (float) $user->game_balance
                )
            );
        } catch (\Throwable $e) {
            Log::warning('TopUp invoice email could not be sent: '.$e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'new_balance' => (float) $user->game_balance,
            'message' => 'Successfully credited '.number_format($scCredited, 2).' SC to your wallet (1 EUR = 0.5 SC). Receipt sent to your email.',
        ]);
    }
}

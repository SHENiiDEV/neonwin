<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000000',
            'method' => 'required|string', // card, daily_sc_bonus
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Please log in to claim or add Coins.'], 401);
        }

        if ($validated['method'] === 'daily_sc_bonus') {
            if (! $user->canClaimDailyBonus()) {
                $nextAvailable = $user->nextDailyBonusAt();
                $diff = $nextAvailable ? $nextAvailable->diffForHumans(null, true) : 'a few hours';

                return response()->json([
                    'status' => 'error',
                    'message' => "You have already claimed your daily free 100,000 Coins. Next claim available in {$diff}.",
                ], 422);
            }

            $dailyCoins = 100000;
            $user->increment('game_balance', $dailyCoins);
            $user->update(['last_daily_bonus_at' => now()]);
            $user->awardVipXp(1);

            return response()->json([
                'status' => 'success',
                'new_balance' => (float) $user->game_balance,
                'can_claim_daily_bonus' => false,
                'next_daily_bonus_at' => $user->nextDailyBonusAt()?->toIso8601String(),
                'message' => 'Successfully claimed your free 100,000 Coins Daily Bonus! Next claim available in 24 hours.',
            ]);
        }

        $user->increment('game_balance', $validated['amount']);
        $user->awardVipXp($validated['amount'] / 100000);

        return response()->json([
            'status' => 'success',
            'new_balance' => (float) $user->game_balance,
            'message' => 'Successfully credited '.number_format($validated['amount']).' Coins to your wallet (Coin Pack Top-Up).',
        ]);
    }
}

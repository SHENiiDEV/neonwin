<?php

namespace App\Http\Controllers;

use App\Models\GameTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('home');
        }

        // Aggregate statistics from transactions
        $transactionsQuery = GameTransaction::where('user_id', $user->id);

        $totalBets = (float) ($transactionsQuery->sum('bet_amount') ?? 0);
        $totalWins = (float) ($transactionsQuery->sum('win_amount') ?? 0);
        $totalRounds = $transactionsQuery->count();

        $biggestWinTx = GameTransaction::where('user_id', $user->id)
            ->where('win_amount', '>', 0)
            ->orderByDesc('win_amount')
            ->first();

        $biggestWin = [
            'amount' => (float) ($biggestWinTx?->win_amount ?? 0),
            'game_code' => $biggestWinTx?->game_code ?? null,
            'multiplier' => ($biggestWinTx && $biggestWinTx->bet_amount > 0)
                ? round($biggestWinTx->win_amount / $biggestWinTx->bet_amount, 1)
                : 0,
            'created_at' => $biggestWinTx?->created_at?->toIso8601String(),
        ];

        // Fetch recent transactions
        $recentTransactions = GameTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'transaction_id' => $tx->transaction_id,
                    'game_code' => $tx->game_code,
                    'provider_code' => $tx->provider_code,
                    'bet_amount' => (float) $tx->bet_amount,
                    'win_amount' => (float) $tx->win_amount,
                    'balance_after' => (float) $tx->balance_after,
                    'type' => $tx->type,
                    'created_at' => $tx->created_at?->toIso8601String(),
                ];
            });

        // VIP tiers configuration
        $vipTiers = [
            ['level' => 1, 'name' => 'Bronze Recruit', 'min_xp' => 0, 'max_xp' => 5000, 'cashback' => '2%', 'rakeback' => '5%', 'level_bonus' => '25 SC', 'icon' => '🥉'],
            ['level' => 2, 'name' => 'Silver Shadow', 'min_xp' => 5000, 'max_xp' => 20000, 'cashback' => '5%', 'rakeback' => '8%', 'level_bonus' => '100 SC', 'icon' => '🥈'],
            ['level' => 3, 'name' => 'Gold Ronin', 'min_xp' => 20000, 'max_xp' => 50000, 'cashback' => '8%', 'rakeback' => '12%', 'level_bonus' => '300 SC', 'icon' => '🥇'],
            ['level' => 4, 'name' => 'Platinum Cyber', 'min_xp' => 50000, 'max_xp' => 100000, 'cashback' => '12%', 'rakeback' => '15%', 'level_bonus' => '1,000 SC', 'icon' => '💎'],
            ['level' => 5, 'name' => 'Neon Overlord', 'min_xp' => 100000, 'max_xp' => 500000, 'cashback' => '18%', 'rakeback' => '20%', 'level_bonus' => '5,000 SC', 'icon' => '👑'],
        ];

        // Determine current and next tier
        $currentTier = $vipTiers[0];
        $nextTier = $vipTiers[1] ?? null;
        foreach ($vipTiers as $idx => $tier) {
            if ($user->vip_level >= $tier['level'] || $user->vip_xp >= $tier['min_xp']) {
                $currentTier = $tier;
                $nextTier = $vipTiers[$idx + 1] ?? null;
            }
        }

        $stats = [
            'balance' => (float) $user->game_balance,
            'vip_xp' => (int) $user->vip_xp,
            'vip_level' => (int) ($user->vip_level ?: 1),
            'total_bets' => $totalBets,
            'total_wins' => $totalWins,
            'net_profit' => round($totalWins - $totalBets, 2),
            'rtp' => $totalBets > 0 ? round(($totalWins / $totalBets) * 100, 2) : 96.5,
            'total_rounds' => $totalRounds,
            'biggest_win' => $biggestWin,
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
        ];

        return Inertia::render('Profile', [
            'stats' => $stats,
            'transactions' => $recentTransactions,
            'vipTiers' => $vipTiers,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('home');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile information updated successfully!');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('home');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully!');
    }
}

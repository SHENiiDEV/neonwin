<?php

namespace App\Http\Controllers;

use App\Models\GameTransaction;
use App\Models\PromotionRedemption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $transactionsQuery = GameTransaction::where('user_id', $user->id)->where('currency', 'EUR');

        $totalBets = (float) ($transactionsQuery->sum('bet_amount') ?? 0);
        $totalWins = (float) ($transactionsQuery->sum('win_amount') ?? 0);
        $rounds = (clone $transactionsQuery)->whereNotIn('type', ['refund', 'rollback'])->selectRaw('agent_code, provider_code, game_code, COALESCE(round_id, transaction_id) as round_key')->groupBy('agent_code', 'provider_code', 'game_code')->groupByRaw('COALESCE(round_id, transaction_id)');
        $totalRounds = DB::query()->fromSub($rounds, 'player_rounds')->count();

        $biggestWinTx = GameTransaction::where('user_id', $user->id)->where('currency', 'EUR')
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
        $recentTransactions = GameTransaction::where('user_id', $user->id)->where('currency', 'EUR')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends(['section' => 'activity'])
            ->through(function ($tx) {
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

        $stats = [
            'balance' => $user->eur_balance_cents / 100,
            'vip_xp' => (int) $user->vip_xp,
            'vip_level' => (int) ($user->vip_level ?: 1),
            'total_bets' => $totalBets,
            'total_wins' => $totalWins,
            'net_profit' => round($totalWins - $totalBets, 2),
            'rtp' => $totalBets > 0 ? round(($totalWins / $totalBets) * 100, 2) : null,
            'total_rounds' => $totalRounds,
            'biggest_win' => $biggestWin,
        ];

        return Inertia::render('Profile', [
            'offers' => PromotionRedemption::where('user_id', $user->id)->latest()->limit(20)->get()->map(fn ($redemption) => [
                'id' => $redemption->id, 'name' => $redemption->snapshot['name'], 'terms' => $redemption->snapshot['terms'],
                'status' => $redemption->status, 'bonus_cents' => $redemption->bonus_cents,
                'spin_count' => $redemption->snapshot['reward']['spin_count'] ?? null,
            ]),
            'stats' => $stats,
            'transactions' => $recentTransactions->items(),
            'history' => ['previous' => $recentTransactions->previousPageUrl(), 'next' => $recentTransactions->nextPageUrl(), 'page' => $recentTransactions->currentPage(), 'total' => $recentTransactions->total()],
            'verification' => ($submission = $user->kyc()->first()) ? array_merge($submission->only(['id', 'status', 'version', 'reason', 'reviewed_at']), ['has_document' => (bool) $submission->document_path, 'has_selfie' => (bool) $submission->selfie_path]) : null,
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

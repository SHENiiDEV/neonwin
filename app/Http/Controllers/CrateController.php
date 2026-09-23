<?php

namespace App\Http\Controllers;

use App\Models\UserCrate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CrateController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['crates' => []]);
        }

        $crates = UserCrate::where('user_id', $user->id)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'crates' => $crates,
            'pending_count' => $crates->where('status', 'pending')->count(),
        ]);
    }

    public function open(int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Please log in to open crates.'], 401);
        }

        $crate = UserCrate::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $crate) {
            return response()->json(['status' => 'error', 'message' => 'Crate not found.'], 404);
        }

        if ($crate->status === 'opened') {
            return response()->json(['status' => 'error', 'message' => 'This crate has already been opened.'], 422);
        }

        // Weighted random prizes based on crate tier
        $rewardCoins = 0.00;
        $rewardXp = 0;

        switch ($crate->tier) {
            case 'mythic':
                $rewardCoins = (float) fake()->randomElement([5000000, 7500000, 10000000, 25000000, 50000000]);
                $rewardXp = fake()->numberBetween(1000, 5000);
                break;
            case 'platinum':
                $rewardCoins = (float) fake()->randomElement([2000000, 3500000, 5000000, 10000000]);
                $rewardXp = fake()->numberBetween(500, 2000);
                break;
            case 'gold':
                $rewardCoins = (float) fake()->randomElement([1000000, 1500000, 2500000, 5000000]);
                $rewardXp = fake()->numberBetween(250, 1000);
                break;
            case 'silver':
                $rewardCoins = (float) fake()->randomElement([500000, 800000, 1200000, 2000000]);
                $rewardXp = fake()->numberBetween(100, 500);
                break;
            case 'bronze':
            default:
                $rewardCoins = (float) fake()->randomElement([200000, 300000, 500000, 1000000]);
                $rewardXp = fake()->numberBetween(50, 250);
                break;
        }

        $crate->update([
            'status' => 'opened',
            'reward_sc' => $rewardCoins,
            'reward_xp' => $rewardXp,
            'opened_at' => now(),
        ]);

        $user->increment('game_balance', $rewardCoins);
        $user->awardVipXp($rewardCoins / 100000);

        return response()->json([
            'status' => 'success',
            'reward_sc' => $rewardCoins,
            'reward_xp' => $rewardXp,
            'new_balance' => (float) $user->game_balance,
            'message' => 'Unboxed '.number_format($rewardCoins).' Coins and '.$rewardXp.' VIP XP!',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\UserCrate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
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
        $rewardSc = 0.00;
        $rewardXp = 0;

        switch ($crate->tier) {
            case 'mythic':
                $rewardSc = (float) Arr::random([5.00, 7.50, 10.00, 15.00]);
                $rewardXp = random_int(100, 500);
                break;
            case 'platinum':
                $rewardSc = (float) Arr::random([2.50, 4.00, 6.00, 8.00]);
                $rewardXp = random_int(50, 200);
                break;
            case 'gold':
                $rewardSc = (float) Arr::random([1.00, 2.00, 3.50, 5.00]);
                $rewardXp = random_int(30, 100);
                break;
            case 'silver':
                $rewardSc = (float) Arr::random([0.50, 1.00, 1.50, 2.50]);
                $rewardXp = random_int(15, 50);
                break;
            case 'bronze':
            default:
                $rewardSc = (float) Arr::random([0.20, 0.40, 0.60, 1.00]);
                $rewardXp = random_int(5, 20);
                break;
        }

        $crate->update([
            'status' => 'opened',
            'reward_sc' => $rewardSc,
            'reward_xp' => $rewardXp,
            'opened_at' => now(),
        ]);

        $user->increment('game_balance', $rewardSc);
        $user->awardVipXp($rewardXp);

        $formattedSc = number_format($rewardSc, 2);

        return response()->json([
            'status' => 'success',
            'reward_sc' => $rewardSc,
            'reward_xp' => $rewardXp,
            'new_balance' => (float) $user->game_balance,
            'message' => "Unboxed {$formattedSc} SC and {$rewardXp} VIP XP!",
        ]);
    }
}

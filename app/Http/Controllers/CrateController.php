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

        $rewardSc = 0.00;
        $rewardXp = random_int(5, 100);

        $crate->update([
            'status' => 'opened',
            'reward_sc' => $rewardSc,
            'reward_xp' => $rewardXp,
            'opened_at' => now(),
        ]);

        $user->awardVipXp($rewardXp);

        return response()->json([
            'status' => 'success',
            'reward_sc' => $rewardSc,
            'reward_xp' => $rewardXp,
            'new_balance' => $user->eur_balance_cents / 100,
            'message' => "Unboxed {$rewardXp} VIP XP. Crates do not award EUR.",
        ]);
    }
}

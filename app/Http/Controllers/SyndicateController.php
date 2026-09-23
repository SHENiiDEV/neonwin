<?php

namespace App\Http\Controllers;

use App\Models\ReferralEarning;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SyndicateController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['referrals' => [], 'stats' => []]);
        }

        $referrals = User::where('referred_by_id', $user->id)
            ->select('id', 'name', 'user_code', 'created_at', 'vip_level')
            ->orderByDesc('created_at')
            ->get();

        $totalEarned = (float) ReferralEarning::where('referrer_id', $user->id)->sum('reward_sc');

        $earningsHistory = ReferralEarning::where('referrer_id', $user->id)
            ->with('referredUser:id,name,user_code')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'referral_code' => $user->referral_code,
            'referral_url' => url('/?ref='.$user->referral_code),
            'total_referrals' => $referrals->count(),
            'total_earned_sc' => $totalEarned,
            'referrals' => $referrals,
            'earnings_history' => $earningsHistory,
        ]);
    }
}

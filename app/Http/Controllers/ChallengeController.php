<?php

namespace App\Http\Controllers;

use App\Models\LiveChallenge;
use Illuminate\Http\JsonResponse;

class ChallengeController extends Controller
{
    public function index(): JsonResponse
    {
        // Auto-seed default challenges if empty
        if (LiveChallenge::count() === 0) {
            LiveChallenge::create([
                'title' => 'Sugar Rush Multiplier Hunt',
                'game_code' => 'vs20sugarush',
                'game_name' => 'Sugar Rush',
                'target_multiplier' => 500.00,
                'min_bet' => 0.20,
                'prize_sc' => 250.00,
                'status' => 'active',
                'expires_at' => now()->addDays(7),
            ]);

            LiveChallenge::create([
                'title' => 'Sweet Bonanza Mega Hit',
                'game_code' => 'vs20sweetbonz',
                'game_name' => 'Sweet Bonanza',
                'target_multiplier' => 250.00,
                'min_bet' => 0.20,
                'prize_sc' => 150.00,
                'status' => 'active',
                'expires_at' => now()->addDays(5),
            ]);

            LiveChallenge::create([
                'title' => 'Buffalo King Stampede',
                'game_code' => 'vs40buffking',
                'game_name' => 'Buffalo King',
                'target_multiplier' => 300.00,
                'min_bet' => 0.40,
                'prize_sc' => 200.00,
                'status' => 'active',
                'expires_at' => now()->addDays(6),
            ]);
        }

        $challenges = LiveChallenge::orderBy('status')
            ->orderByDesc('prize_sc')
            ->get();

        return response()->json([
            'challenges' => $challenges,
        ]);
    }
}

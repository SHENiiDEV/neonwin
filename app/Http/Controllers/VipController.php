<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VipController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $vipTiers = [
            ['level' => 1, 'name' => 'Bronze Recruit', 'min_xp' => 0, 'max_xp' => 5000, 'cashback' => '2%', 'rakeback' => '5%', 'level_bonus' => '$25', 'icon' => '🥉'],
            ['level' => 2, 'name' => 'Silver Shadow', 'min_xp' => 5000, 'max_xp' => 20000, 'cashback' => '5%', 'rakeback' => '8%', 'level_bonus' => '$100', 'icon' => '🥈'],
            ['level' => 3, 'name' => 'Gold Ronin', 'min_xp' => 20000, 'max_xp' => 50000, 'cashback' => '8%', 'rakeback' => '12%', 'level_bonus' => '$300', 'icon' => '🥇'],
            ['level' => 4, 'name' => 'Platinum Cyber', 'min_xp' => 50000, 'max_xp' => 100000, 'cashback' => '12%', 'rakeback' => '15%', 'level_bonus' => '$1,000', 'icon' => '💎'],
            ['level' => 5, 'name' => 'Neon Overlord', 'min_xp' => 100000, 'max_xp' => 500000, 'cashback' => '18%', 'rakeback' => '20%', 'level_bonus' => '$5,000', 'icon' => '👑'],
        ];

        return Inertia::render('VipClub', [
            'vipTiers' => $vipTiers,
        ]);
    }
}

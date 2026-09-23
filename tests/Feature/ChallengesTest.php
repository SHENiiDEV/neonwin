<?php

namespace Tests\Feature;

use App\Models\LiveChallenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fetch_active_challenges(): void
    {
        $user = User::factory()->create();

        LiveChallenge::create([
            'title' => 'Sweet Sugar Hunt',
            'game_code' => 'sweet-bonanza-vs20fruitsw',
            'game_name' => 'Sweet Bonanza',
            'target_multiplier' => 500.00,
            'min_bet' => 0.50,
            'prize_sc' => 250.00,
            'status' => 'active',
            'expires_at' => now()->addHours(12),
        ]);

        $response = $this->actingAs($user)->getJson('/api/challenges');
        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'game_name' => 'Sweet Bonanza',
            'target_multiplier' => '500.00',
            'prize_sc' => '250.00',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_claim_daily_bonus_once(): void
    {
        $user = User::factory()->create([
            'game_balance' => 0.00,
            'last_daily_bonus_at' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/deposit', [
            'amount' => 1.0,
            'method' => 'daily_sc_bonus',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'new_balance' => 1.0,
            'can_claim_daily_bonus' => false,
        ]);

        $user->refresh();
        $this->assertEquals(1.0, $user->game_balance);
        $this->assertNotNull($user->last_daily_bonus_at);
        $this->assertFalse($user->canClaimDailyBonus());
    }

    public function test_user_cannot_claim_daily_bonus_twice_within_24_hours(): void
    {
        $user = User::factory()->create([
            'game_balance' => 1.00,
            'last_daily_bonus_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user)->postJson('/deposit', [
            'amount' => 1.0,
            'method' => 'daily_sc_bonus',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);

        $user->refresh();
        $this->assertEquals(1.00, $user->game_balance);
    }

    public function test_user_can_claim_daily_bonus_after_24_hours(): void
    {
        $user = User::factory()->create([
            'game_balance' => 1.00,
            'last_daily_bonus_at' => now()->subHours(25),
        ]);

        $this->assertTrue($user->canClaimDailyBonus());

        $response = $this->actingAs($user)->postJson('/deposit', [
            'amount' => 1.0,
            'method' => 'daily_sc_bonus',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'new_balance' => 2.0,
        ]);

        $user->refresh();
        $this->assertEquals(2.00, $user->game_balance);
    }
}


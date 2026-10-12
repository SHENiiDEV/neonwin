<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_bonus_cannot_mint_eur(): void
    {
        $user = User::factory()->create(['game_balance' => 10, 'eur_balance_cents' => 0]);
        $this->actingAs($user)->postJson('/deposit', ['amount' => 0.02, 'method' => 'daily_sc_bonus'])->assertUnprocessable();
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
        $this->assertSame('10.00', $user->fresh()->game_balance);
        $this->assertDatabaseCount('wallet_entries', 0);
    }

    public function test_guest_cannot_create_deposit_request(): void
    {
        $this->postJson('/deposit', ['package' => 'eur_25'])->assertUnauthorized();
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_invalid_package_cannot_create_a_payment_or_credit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_999999'])->assertUnprocessable();
        $this->assertDatabaseCount('deposits', 0);
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
    }
}

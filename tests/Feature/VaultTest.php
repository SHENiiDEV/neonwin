<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_deposit_to_vault(): void
    {
        $user = User::factory()->create([
            'eur_balance_cents' => 1000000000,
            'eur_vault_cents' => 0,
        ]);

        $response = $this->actingAs($user)->postJson('/vault/deposit', [
            'amount' => 4000000.00,
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'game_balance' => 6000000.00,
            'vault_balance' => 4000000.00,
        ]);

        $user->refresh();
        $this->assertEquals(600000000, $user->eur_balance_cents);
        $this->assertEquals(400000000, $user->eur_vault_cents);
    }

    public function test_user_can_withdraw_from_vault_with_pin(): void
    {
        $user = User::factory()->create([
            'eur_balance_cents' => 200000000,
            'eur_vault_cents' => 800000000,
            'vault_pin' => Hash::make('1234'),
        ]);

        $response = $this->actingAs($user)->postJson('/vault/withdraw', [
            'amount' => 5000000.00,
            'pin' => '1234',
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertEquals(700000000, $user->eur_balance_cents);
        $this->assertEquals(300000000, $user->eur_vault_cents);
    }

    public function test_withdrawal_fails_with_invalid_pin(): void
    {
        $user = User::factory()->create([
            'eur_balance_cents' => 200000000,
            'eur_vault_cents' => 800000000,
            'vault_pin' => Hash::make('1234'),
        ]);

        $response = $this->actingAs($user)->postJson('/vault/withdraw', [
            'amount' => 5000000.00,
            'pin' => '9999',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_set_vault_pin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/vault/set-pin', [
            'pin' => '5678',
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertTrue(Hash::check('5678', $user->vault_pin));
    }
}

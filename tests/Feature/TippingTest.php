<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_tip_another_player_by_user_code_with_fee(): void
    {
        $sender = User::factory()->create([
            'eur_balance_cents' => 1000000000,
        ]);

        $recipient = User::factory()->create([
            'eur_balance_cents' => 500000000,
            'user_code' => 'NW-LUCKY99',
            'name' => 'LuckyPlayer',
        ]);

        $response = $this->actingAs($sender)->postJson('/tipping/send', [
            'recipient_code' => 'NW-LUCKY99',
            'amount' => 2000000.00,
            'note' => 'Good luck in Sugar Rush!',
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'fee' => 60000.00,
            'net_delivered' => 1940000.00,
            'recipient_name' => 'LuckyPlayer',
        ]);

        $sender->refresh();
        $this->assertEquals(800000000, $sender->eur_balance_cents);

        $recipient->refresh();
        $this->assertEquals(694000000, $recipient->eur_balance_cents);

        $this->assertDatabaseCount('wallet_entries', 2);
        $this->assertDatabaseHas('wallet_entries', ['user_id' => $sender->id, 'delta_cents' => -200000000, 'kind' => 'tip_sent']);
        $this->assertDatabaseHas('wallet_entries', ['user_id' => $recipient->id, 'delta_cents' => 194000000, 'kind' => 'tip_received']);
    }

    public function test_user_cannot_tip_themselves(): void
    {
        $user = User::factory()->create([
            'eur_balance_cents' => 1000000000,
            'user_code' => 'NW-MYSELF',
        ]);

        $response = $this->actingAs($user)->postJson('/tipping/send', [
            'recipient_code' => 'NW-MYSELF',
            'amount' => 1000000.00,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_tip_more_than_balance(): void
    {
        $sender = User::factory()->create([
            'eur_balance_cents' => 10000000,
        ]);

        $recipient = User::factory()->create([
            'user_code' => 'NW-OTHER',
        ]);

        $response = $this->actingAs($sender)->postJson('/tipping/send', [
            'recipient_code' => 'NW-OTHER',
            'amount' => 5000000.00,
        ]);

        $response->assertStatus(422);
    }
}

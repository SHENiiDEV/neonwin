<?php

namespace Tests\Feature;

use App\Models\TippingTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_tip_another_player_by_user_code_with_fee(): void
    {
        $sender = User::factory()->create([
            'game_balance' => 10000000.00,
        ]);

        $recipient = User::factory()->create([
            'game_balance' => 5000000.00,
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
        $this->assertEquals(8000000.00, $sender->game_balance);

        $recipient->refresh();
        $this->assertEquals(6940000.00, $recipient->game_balance);

        $transaction = TippingTransaction::where('sender_id', $sender->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(2000000.00, $transaction->amount_sc);
        $this->assertEquals(60000.00, $transaction->fee_sc);
        $this->assertEquals(1940000.00, $transaction->net_amount_sc);
        $this->assertEquals('Good luck in Sugar Rush!', $transaction->note);
    }

    public function test_user_cannot_tip_themselves(): void
    {
        $user = User::factory()->create([
            'game_balance' => 10000000.00,
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
            'game_balance' => 100000.00,
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

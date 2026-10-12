<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NexusGgrCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nexus.agent_code' => 'test-agent', 'nexus.agent_secret' => 'test-secret', 'nexus.eur_agent_confirmed' => true]);
    }

    public function test_user_balance_callback(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 2800]);
        GameSession::create(['user_id' => $user->id, 'provider_code' => 'PRAGMATIC', 'game_code' => 'game', 'token' => 'session-token', 'token_hash' => hash('sha256', 'session-token')]);
        $this->postJson('/gold_api', ['method' => 'user_balance', 'user_code' => $user->user_code, 'user_token' => 'session-token', 'agent_code' => 'test-agent', 'agent_secret' => 'test-secret'])->assertOk()->assertJson(['status' => 1, 'user_balance' => 28]);
    }

    public function test_slot_transaction_callback_with_txn_id_v2_deduplication(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 1000]);
        $payload = ['method' => 'transaction', 'agent_code' => 'test-agent', 'agent_secret' => 'test-secret', 'user_code' => $user->user_code, 'game_type' => 'slot', 'slot' => ['provider_code' => 'PRAGMATIC', 'game_code' => 'game', 'bet_money' => 1, 'win_money' => 2.5, 'round_id' => 123, 'txn_id' => '123', 'txn_id_v2' => 'unique', 'txn_type' => 'debit_credit']];
        $this->postJson('/gold_api', $payload)->assertOk()->assertJson(['status' => 1, 'user_balance' => 11.5]);
        $this->postJson('/gold_api', $payload)->assertOk()->assertJson(['status' => 1, 'user_balance' => 11.5, 'msg' => 'TRANSACTION_ALREADY_PROCESSED']);
        $this->assertDatabaseCount('game_transactions', 1);
    }

    public function test_insufficient_funds_response(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 50000]);
        $this->postJson('/gold_api', ['method' => 'transaction', 'agent_code' => 'test-agent', 'agent_secret' => 'test-secret', 'user_code' => $user->user_code, 'game_type' => 'slot', 'slot' => ['provider_code' => 'PRAGMATIC', 'game_code' => 'game', 'bet_money' => 1000, 'round_id' => 123, 'txn_id' => '123', 'txn_id_v2' => 'unique', 'txn_type' => 'debit']])->assertOk()->assertJson(['status' => 0, 'msg' => 'INSUFFICIENT_USER_FUNDS']);
        $this->assertSame(50000, $user->fresh()->eur_balance_cents);
    }
}

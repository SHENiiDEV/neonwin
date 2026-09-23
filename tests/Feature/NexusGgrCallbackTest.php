<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NexusGgrCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_balance_callback(): void
    {
        $user = User::create([
            'name' => 'TestPlayer',
            'email' => 'test@neonwin.com',
            'password' => bcrypt('secret'),
            'user_code' => 'ZP-123456',
            'game_balance' => 28.00,
        ]);

        $response = $this->postJson('/gold_api', [
            'method' => 'user_balance',
            'user_code' => 'ZP-123456',
            'agent_secret' => config('nexus.agent_secret'),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 1,
            'user_balance' => 28.00,
        ]);
    }

    public function test_slot_transaction_callback_with_txn_id_v2_deduplication(): void
    {
        $user = User::create([
            'name' => 'SocialPlayer',
            'email' => 'social@neonwin.com',
            'password' => bcrypt('secret'),
            'user_code' => 'ZP-777777',
            'game_balance' => 10.00,
        ]);

        $roundId = 63792613432127;
        $txnIdV2 = '64a83f2fc597acc9004eec52c3f84c30';

        // 1. Debit/Credit Slot transaction (Bet 1.00 SC, Win 2.50 SC)
        $response = $this->postJson('/gold_api', [
            'method' => 'transaction',
            'agent_code' => 'zenithplay',
            'agent_secret' => config('nexus.agent_secret'),
            'user_code' => 'ZP-777777',
            'game_type' => 'slot',
            'slot' => [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20midas',
                'type' => 'BASE',
                'bet_money' => 1.00,
                'win_money' => 2.50,
                'round_id' => $roundId,
                'txn_id' => (string) $roundId,
                'txn_id_v2' => $txnIdV2,
                'txn_type' => 'debit_credit',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 1,
            'user_balance' => 11.50,
        ]);

        // 2. Test Deduplication on txn_id_v2
        $duplicateResponse = $this->postJson('/gold_api', [
            'method' => 'transaction',
            'agent_code' => 'zenithplay',
            'agent_secret' => config('nexus.agent_secret'),
            'user_code' => 'ZP-777777',
            'game_type' => 'slot',
            'slot' => [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20midas',
                'bet_money' => 1.00,
                'win_money' => 2.50,
                'txn_id_v2' => $txnIdV2,
            ],
        ]);

        $duplicateResponse->assertStatus(200);
        $duplicateResponse->assertJson([
            'status' => 1,
            'user_balance' => 11.50,
            'msg' => 'TRANSACTION_ALREADY_PROCESSED',
        ]);
    }

    public function test_insufficient_funds_response(): void
    {
        $user = User::create([
            'name' => 'BrokePlayer',
            'email' => 'broke@neonwin.com',
            'password' => bcrypt('secret'),
            'user_code' => 'ZP-000001',
            'game_balance' => 500.00,
        ]);

        $response = $this->postJson('/gold_api', [
            'method' => 'transaction',
            'agent_code' => 'zenithplay',
            'agent_secret' => config('nexus.agent_secret'),
            'user_code' => 'ZP-000001',
            'game_type' => 'slot',
            'slot' => [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20midas',
                'bet_money' => 1000.00,
                'win_money' => 0.00,
                'txn_id_v2' => 'txn_insufficient_test',
                'txn_type' => 'debit',
            ],
        ]);

        $response->assertJson([
            'status' => 0,
            'msg' => 'INSUFFICIENT_USER_FUNDS',
        ]);
    }
}

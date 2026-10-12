<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NexusConcurrencyTest extends TestCase
{
    public function test_simultaneous_callbacks_and_retries_apply_one_wallet_entry(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is needed for the concurrent callback test.');
        }
        $database = tempnam(sys_get_temp_dir(), 'neon-concurrency-');
        $original = config('database.connections.sqlite.database');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'nexus.agent_code' => 'test-agent', 'nexus.agent_secret' => 'test-secret', 'nexus.eur_agent_confirmed' => true]);
        DB::purge('sqlite');
        try {
            Artisan::call('migrate', ['--force' => true]);
            DB::statement('PRAGMA journal_mode=WAL');
            $user = User::factory()->create(['eur_balance_cents' => 100]);
            $payload = ['method' => 'transaction', 'agent_code' => 'test-agent', 'agent_secret' => 'test-secret', 'user_code' => $user->user_code, 'game_type' => 'slot', 'slot' => ['provider_code' => 'PRAGMATIC', 'game_code' => 'game', 'round_id' => 123, 'txn_id' => '123', 'txn_id_v2' => 'concurrent-id', 'txn_type' => 'debit_credit', 'bet_money' => 0.10, 'win_money' => 0.03]];
            $children = [];
            DB::disconnect('sqlite');
            for ($i = 0; $i < 2; $i++) {
                $pid = pcntl_fork();
                if ($pid === 0) {
                    DB::purge('sqlite');
                    $response = $this->postJson('/gold_api', $payload);
                    exit(in_array($response->status(), [200, 503], true) ? 0 : 1);
                }
                $children[] = $pid;
            }
            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
            DB::purge('sqlite');
            $this->postJson('/gold_api', $payload)->assertOk()->assertJsonPath('user_balance', 0.93);
            $this->assertSame(93, $user->fresh()->eur_balance_cents);
            $this->assertDatabaseCount('wallet_entries', 1);
            $this->assertDatabaseCount('game_transactions', 1);
        } finally {
            DB::disconnect('sqlite');
            config(['database.connections.sqlite.database' => $original]);
            DB::purge('sqlite');
            @unlink($database);
            @unlink($database.'-wal');
            @unlink($database.'-shm');
        }
    }
}

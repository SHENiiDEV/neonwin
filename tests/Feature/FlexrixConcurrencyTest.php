<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FlexrixWallet;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

class FlexrixConcurrencyTest extends TestCase
{
    public function test_simultaneous_debits_and_retry_change_wallet_and_vip_once(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for concurrent callbacks.');
        }
        $database = tempnam(sys_get_temp_dir(), 'flexrix-concurrency-');
        $original = config('database.connections.sqlite.database');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'gaming.flexrix.sportsbook_key' => 'FLX_concurrency']);
        DB::purge('sqlite');
        try {
            Artisan::call('migrate', ['--force' => true]);
            DB::statement('PRAGMA journal_mode=WAL');
            $user = User::factory()->create(['eur_balance_cents' => 10000]);
            $body = ['external_player_id' => $user->user_code, 'amount' => '50.00', 'currency' => 'EUR', 'transaction_id' => 'concurrent-bet', 'bet_id' => 42];
            DB::disconnect('sqlite');
            $children = [];
            for ($i = 0; $i < 2; $i++) {
                $pid = pcntl_fork();
                if ($pid === 0) {
                    DB::purge('sqlite');
                    try {
                        $response = app(FlexrixWallet::class)->handle('sportsbook', $body, 'debit');
                        exit($response['ok'] ? 0 : 1);
                    } catch (Throwable) {
                        exit(0);
                    }
                }
                $children[] = $pid;
            }
            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
            DB::purge('sqlite');
            $this->assertSame(['ok' => true, 'balance' => 50, 'currency' => 'EUR'], app(FlexrixWallet::class)->handle('sportsbook', $body, 'debit'));
            $this->assertSame(5000, $user->fresh()->eur_balance_cents);
            $this->assertDatabaseCount('wallet_entries', 1);
            $this->assertDatabaseCount('game_transactions', 1);
            $this->assertDatabaseCount('gaming_callbacks', 1);
            $this->assertDatabaseCount('vip_bets', 1);
            $this->assertDatabaseHas('vip_accounts', ['user_id' => $user->id, 'wager_cents' => 5000]);
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

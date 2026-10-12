<?php

namespace Tests\Feature;

use App\Models\LaunchSetting;
use App\Models\User;
use App\Services\WheelService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Throwable;

class WheelConcurrencyTest extends TestCase
{
    public function test_simultaneous_spin_requests_and_fund_exhaustion_cannot_duplicate_or_overdraw(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required for concurrent requests.');
        }
        $database = tempnam(sys_get_temp_dir(), 'wheel-concurrency-');
        $original = config('database.connections.sqlite.database');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database]);
        DB::purge('sqlite');
        try {
            Artisan::call('migrate', ['--force' => true]);
            DB::statement('PRAGMA journal_mode=WAL');
            LaunchSetting::find(1)->forceFill(['wheel_budget_cents' => 100, 'wheel_prizes' => [['cents' => 100, 'weight' => 1]]])->save();
            $users = [User::factory()->create(), User::factory()->create()];
            $requestId = (string) Str::uuid();
            DB::disconnect('sqlite');
            $children = [];
            foreach ([0, 0, 1] as $index) {
                $pid = pcntl_fork();
                if ($pid === 0) {
                    DB::purge('sqlite');
                    try {
                        app(WheelService::class)->spin($users[$index], $requestId);
                        exit(0);
                    } catch (ValidationException) {
                        exit(0);
                    } catch (Throwable $exception) {
                        if ($exception instanceof QueryException && str_contains($exception->getMessage(), 'locked')) {
                            exit(0);
                        }
                        exit(1);
                    }
                }
                if ($pid === -1) {
                    $this->fail('Unable to fork concurrency test.');
                }
                $children[] = $pid;
            }
            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
            DB::purge('sqlite');
            $this->assertSame(100, (int) User::sum('eur_balance_cents'));
            $this->assertSame(0, LaunchSetting::find(1)->wheel_budget_cents);
            $this->assertDatabaseCount('wheel_spins', 1);
            $this->assertDatabaseCount('wallet_entries', 1);
            $this->assertDatabaseCount('wheel_fund_entries', 1);
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

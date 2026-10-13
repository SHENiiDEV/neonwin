<?php

namespace Tests\Feature;

use App\Models\LaunchSetting;
use App\Models\User;
use App\Models\WalletEntry;
use App\Services\FlexrixWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlexrixTestPlayerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepared_player_can_complete_wallet_callbacks_and_cleanup_removes_test_activity(): void
    {
        config(['gaming.provider' => 'flexrix', 'gaming.flexrix.casino_key' => 'test-merchant']);
        LaunchSetting::findOrFail(1)->forceFill(['rakeback_enabled' => true, 'rakeback_starts_at' => now()->startOfDay(), 'rakeback_ends_at' => now()->addDays(2)])->save();

        $this->artisan('flexrix:test-player prepare')->assertSuccessful();

        $user = User::where('user_code', 'test_player_001')->firstOrFail();
        $this->assertSame(100000, $user->eur_balance_cents);
        $this->assertFalse($user->is_admin);
        $this->assertDatabaseHas('games', ['game_code' => 'flexrix-integration-test', 'status' => false]);
        $result = app(FlexrixWallet::class)->handle('casino', ['player_id' => 'test_player_001', 'currency' => 'EUR', 'game_uuid' => 'flexrix-integration-test', 'amount' => '10.00', 'transaction_id' => 'fixture-bet', 'round_id' => 'fixture-round'], 'bet');
        $this->assertSame(['ok' => true, 'balance' => 990, 'currency' => 'EUR'], $result);
        $this->assertDatabaseHas('wallet_entries', ['user_id' => $user->id, 'kind' => 'gaming_bet', 'delta_cents' => -1000]);
        $this->assertDatabaseCount('launch_rakeback_entries', 1);

        $this->artisan('flexrix:test-player cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('games', ['game_code' => 'flexrix-integration-test']);
        $this->assertDatabaseMissing('providers', ['code' => 'FLEXRIX_TEST']);
        $this->assertDatabaseMissing('wallet_entries', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('game_transactions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('gaming_callbacks', ['user_id' => $user->id]);
        $this->assertDatabaseCount('launch_rakeback_entries', 0);
        $this->assertDatabaseHas('admin_audits', ['action' => 'flexrix_test_fixture_cleaned', 'target' => 'test_player_001']);
    }

    public function test_prepare_never_overwrites_an_existing_player(): void
    {
        $user = User::factory()->create(['user_code' => 'test_player_001', 'eur_balance_cents' => 1234]);

        $this->artisan('flexrix:test-player prepare')->assertFailed();

        $this->assertSame(1234, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseMissing('games', ['game_code' => 'flexrix-integration-test']);
    }

    public function test_cleanup_stops_if_test_player_has_unexpected_wallet_activity(): void
    {
        $this->artisan('flexrix:test-player prepare')->assertSuccessful();
        $user = User::where('user_code', 'test_player_001')->firstOrFail();
        WalletEntry::create(['user_id' => $user->id, 'reference' => 'unexpected-activity', 'kind' => 'deposit', 'delta_cents' => 0, 'balance_before_cents' => 100000, 'balance_after_cents' => 100000]);

        $this->artisan('flexrix:test-player cleanup')->assertFailed();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('games', ['game_code' => 'flexrix-integration-test']);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\AdminAudit;
use App\Models\Game;
use App\Models\Provider;
use App\Models\User;
use App\Models\WalletEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FlexrixTestPlayerCommand extends Command
{
    private const PLAYER_CODE = 'test_player_001';

    private const PLAYER_EMAIL = 'flexrix.integration.test@neonwin.invalid';

    private const GAME_CODE = 'flexrix-integration-test';

    private const PROVIDER_CODE = 'FLEXRIX_TEST';

    protected $signature = 'flexrix:test-player {action : prepare or cleanup} {--confirm-production : Acknowledge the temporary EUR test credit on production}';

    protected $description = 'Prepare or remove the temporary Flexrix callback integration-test player and game';

    public function handle(): int
    {
        $action = $this->argument('action');
        if (! in_array($action, ['prepare', 'cleanup'], true)) {
            $this->error('Action must be prepare or cleanup.');

            return self::FAILURE;
        }
        if (app()->isProduction() && ! $this->option('confirm-production')) {
            $this->error('Production test funds require --confirm-production.');

            return self::FAILURE;
        }

        try {
            DB::transaction(fn () => $action === 'prepare' ? $this->prepare() : $this->cleanup(), 3);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($action === 'prepare'
            ? 'Created test_player_001 with a temporary EUR 1000.00 wallet and hidden Flexrix integration-test game.'
            : 'Removed the Flexrix test player, wallet activity and hidden game.');

        return self::SUCCESS;
    }

    private function prepare(): void
    {
        if (User::where('user_code', self::PLAYER_CODE)->exists() || User::where('email', self::PLAYER_EMAIL)->exists()) {
            throw new RuntimeException('The test player already exists. Inspect or clean it up before preparing again.');
        }
        if (Provider::where('code', self::PROVIDER_CODE)->exists() || Game::where('game_code', self::GAME_CODE)->orWhere('slug', self::GAME_CODE)->exists()) {
            throw new RuntimeException('The test provider or game code is already in use.');
        }

        Provider::create(['code' => self::PROVIDER_CODE, 'name' => 'Flexrix Integration Test', 'integration' => 'flexrix', 'status' => false, 'type' => 'slot']);
        Game::create(['provider_code' => self::PROVIDER_CODE, 'game_code' => self::GAME_CODE, 'external_code' => self::GAME_CODE, 'integration' => 'flexrix', 'name' => 'Flexrix Integration Test', 'slug' => self::GAME_CODE, 'category' => 'slots', 'status' => false]);
        $user = User::create(['name' => 'Flexrix Integration Test', 'email' => self::PLAYER_EMAIL, 'password' => Str::random(80), 'user_code' => self::PLAYER_CODE, 'currency' => 'EUR', 'game_balance' => 0]);
        $user->forceFill(['eur_balance_cents' => 100000])->save();
        WalletEntry::create(['user_id' => $user->id, 'reference' => 'flexrix:test-fixture:credit:'.$user->id, 'kind' => 'integration_test_credit', 'delta_cents' => 100000, 'balance_before_cents' => 0, 'balance_after_cents' => 100000]);
        AdminAudit::create(['action' => 'flexrix_test_fixture_prepared', 'target' => self::PLAYER_CODE, 'after' => ['user_id' => $user->id, 'test_credit_cents' => 100000]]);
    }

    private function cleanup(): void
    {
        $user = User::where('user_code', self::PLAYER_CODE)->lockForUpdate()->first();
        if (! $user || $user->email !== self::PLAYER_EMAIL || $user->name !== 'Flexrix Integration Test') {
            throw new RuntimeException('The expected test player was not found; no records were deleted.');
        }
        if (DB::table('deposits')->where('user_id', $user->id)->exists()
            || DB::table('game_sessions')->where('user_id', $user->id)->exists()
            || DB::table('wallet_entries')->where('user_id', $user->id)->whereNotIn('kind', ['integration_test_credit', 'gaming_bet', 'gaming_win', 'gaming_refund', 'gaming_rollback', 'vip_reversal'])->exists()) {
            throw new RuntimeException('The test player has unexpected activity; cleanup was stopped.');
        }
        $game = Game::where('game_code', self::GAME_CODE)->where('provider_code', self::PROVIDER_CODE)->where('integration', 'flexrix')->where('status', false)->first();
        $provider = Provider::where('code', self::PROVIDER_CODE)->where('integration', 'flexrix')->where('status', false)->first();
        if (! $game || ! $provider || Game::where('provider_code', self::PROVIDER_CODE)->count() !== 1) {
            throw new RuntimeException('The test game or provider changed; cleanup was stopped.');
        }

        DB::table('launch_rakeback_entries')->whereIn('game_transaction_id', DB::table('game_transactions')->select('id')->where('user_id', $user->id))->delete();
        DB::table('vip_bets')->where('user_id', $user->id)->delete();
        DB::table('gaming_callbacks')->where('user_id', $user->id)->delete();
        DB::table('vip_rewards')->where('user_id', $user->id)->delete();
        DB::table('vip_accounts')->where('user_id', $user->id)->delete();
        DB::table('wallet_entries')->where('user_id', $user->id)->delete();
        DB::table('game_transactions')->where('user_id', $user->id)->delete();
        $balance = $user->eur_balance_cents;
        $user->delete();
        $game->delete();
        $provider->delete();
        AdminAudit::create(['action' => 'flexrix_test_fixture_cleaned', 'target' => self::PLAYER_CODE, 'before' => ['user_id' => $user->id, 'remaining_balance_cents' => $balance]]);
    }
}

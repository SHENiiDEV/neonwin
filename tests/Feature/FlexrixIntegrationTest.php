<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameTransaction;
use App\Models\LaunchSetting;
use App\Models\Provider;
use App\Models\User;
use App\Models\VipAccount;
use App\Models\VipReward;
use App\Models\WalletEntry;
use App\Services\FlexrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FlexrixIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['gaming.provider' => 'flexrix', 'gaming.flexrix.casino_key' => 'FXC_test', 'gaming.flexrix.casino_secret' => 'casino-test-secret', 'gaming.flexrix.sportsbook_key' => 'FLX_test', 'gaming.flexrix.sportsbook_secret' => 'sports-test-secret', 'gaming.flexrix.sportsbook_url' => 'https://sports.example.test']);
        Http::preventStrayRequests();
    }

    private function game(): Game
    {
        $provider = Provider::factory()->create(['integration' => 'flexrix', 'code' => FlexrixService::providerCode('Pragmatic')]);

        return Game::factory()->create(['integration' => 'flexrix', 'provider_code' => $provider->code, 'game_code' => 'flexrix-test-game', 'external_code' => 'test-game']);
    }

    private function casino(User $user, string $action, array $extra = []): TestResponse
    {
        $body = array_merge(['player_id' => $user->user_code, 'action' => $action, 'amount' => '10.00', 'currency' => 'EUR', 'transaction_id' => 'bet-1', 'round_id' => 'round-1', 'game_uuid' => 'flexrix/test-game'], $extra);
        $headers = ['X-Merchant-Id' => 'FXC_test', 'X-Timestamp' => (string) now()->timestamp, 'X-Nonce' => 'test-random-nonce'];
        $headers['X-Sign'] = hash_hmac('sha1', FlexrixService::canonical(array_merge($body, $headers)), 'casino-test-secret');

        return $this->withHeaders($headers)->post('/api/flexrix/casino/callback', $body);
    }

    private function sports(User $user, string $action, array $extra = []): TestResponse
    {
        $body = array_merge(['external_player_id' => $user->user_code, 'merchant_key' => 'FLX_test', 'amount' => '10.00', 'currency' => 'EUR', 'transaction_id' => 'sports-bet', 'bet_id' => 100], $extra);
        $raw = json_encode($body, JSON_THROW_ON_ERROR);

        return $this->call('POST', '/api/flexrix/sportsbook/'.$action, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_FLEXRIX_MERCHANT' => 'FLX_test', 'HTTP_X_FLEXRIX_SIGNATURE' => hash_hmac('sha256', $raw, 'sports-test-secret')], $raw);
    }

    public function test_casino_auth_and_timestamp_are_required(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->post('/api/flexrix/casino/callback', ['action' => 'bet'])->assertUnauthorized();
        $this->travelTo(now()->subMinutes(10));
        $headers = ['X-Merchant-Id' => 'FXC_test', 'X-Timestamp' => (string) now()->timestamp, 'X-Nonce' => 'nonce'];
        $body = ['action' => 'balance', 'player_id' => $user->user_code];
        $headers['X-Sign'] = hash_hmac('sha1', FlexrixService::canonical(array_merge($body, $headers)), 'casino-test-secret');
        $this->travelBack();
        $this->withHeaders($headers)->post('/api/flexrix/casino/callback', $body)->assertUnauthorized();
        $this->assertSame(10000, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 0);
    }

    public function test_casino_bet_win_retry_and_conflicting_id(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->casino($user, 'bet')->assertOk()->assertJsonPath('balance', 90);
        $this->casino($user, 'win', ['transaction_id' => 'win-1', 'amount' => '25.35'])->assertOk()->assertJsonPath('balance', 115.35);
        $this->casino($user, 'bet', ['casino_request_retry_count' => '2'])->assertOk()->assertJsonPath('balance', 90);
        $this->casino($user, 'bet', ['amount' => '9.00'])->assertStatus(400);
        $this->assertSame(11535, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 2);
        $this->assertDatabaseCount('vip_bets', 1);
    }

    public function test_casino_retries_with_a_changed_round_id_return_original_results_without_new_wallet_entries(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 100000]);

        $this->casino($user, 'bet', ['amount' => '10.00', 'round_id' => 'first-bet-round'])->assertOk()->assertJsonPath('balance', 990);
        $this->casino($user, 'bet', ['amount' => '10.00', 'round_id' => 'retry-bet-round'])->assertOk()->assertJsonPath('balance', 990);
        $this->casino($user, 'win', ['amount' => '150.00', 'transaction_id' => 'win-1', 'round_id' => 'first-win-round'])->assertOk()->assertJsonPath('balance', 1140);
        $this->casino($user, 'win', ['amount' => '150.00', 'transaction_id' => 'win-1', 'round_id' => 'retry-win-round'])->assertOk()->assertJsonPath('balance', 1140);

        $this->assertSame(114000, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 2);
        $this->assertDatabaseCount('gaming_callbacks', 2);
    }

    public function test_existing_round_sensitive_receipts_remain_idempotent_after_updating(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 100000]);
        $legacyFingerprint = hash('sha256', json_encode([$user->id, 'bet', 1000, 1000, 'flexrix/test-game', 'legacy-round', [], null, null], JSON_THROW_ON_ERROR));
        $this->casino($user, 'bet', ['amount' => '10.00', 'round_id' => 'legacy-round'])->assertOk()->assertJsonPath('balance', 990);
        DB::table('gaming_callbacks')->where('remote_id', 'bet-1')->update(['fingerprint' => $legacyFingerprint]);

        $this->casino($user, 'bet', ['amount' => '10.00', 'round_id' => 'retry-round'])->assertOk()->assertJsonPath('balance', 990);

        $this->assertSame(99000, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 1);
    }

    public function test_refund_and_rollback_reverse_each_original_only_once_and_remove_vip_stake(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->casino($user, 'bet')->assertOk();
        $this->casino($user, 'win', ['transaction_id' => 'win-1', 'amount' => '25.00'])->assertOk();
        $this->casino($user, 'refund', ['transaction_id' => 'refund-1', 'ref_transaction_id' => 'bet-1'])->assertOk()->assertJsonPath('balance', 125);
        $this->casino($user, 'rollback', ['transaction_id' => 'rollback-1', 'amount' => '25.00', 'rollback_transactions' => [['transaction_id' => 'bet-1', 'action' => 'bet', 'amount' => '10.00'], ['transaction_id' => 'win-1', 'action' => 'win', 'amount' => '25.00']]])->assertOk()->assertJsonPath('balance', 100);
        $this->casino($user, 'refund', ['transaction_id' => 'refund-2', 'ref_transaction_id' => 'bet-1'])->assertOk()->assertJsonPath('balance', 100);
        $this->assertSame(0, VipAccount::where('user_id', $user->id)->first()->wager_cents);
        $this->assertSame(0, (int) VipReward::where('user_id', $user->id)->where('kind', 'rakeback')->sum('amount_cents'));
        $this->assertSame(0.0, (float) GameTransaction::sum('bet_amount'));
        $this->assertSame(0.0, (float) GameTransaction::sum('win_amount'));
        $this->assertSame(10000, $user->fresh()->eur_balance_cents);
    }

    public function test_casino_rollback_json_form_field_and_late_original_are_safe(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->casino($user, 'rollback', ['transaction_id' => 'cancel-before-bet', 'rollback_transactions' => json_encode([['action' => 'bet', 'transaction_id' => 'bet-1', 'amount' => '10.00']])])->assertOk()->assertJsonPath('balance', 100);
        $this->casino($user, 'bet')->assertOk()->assertJsonPath('error_code', 'TRANSACTION_CANCELED');
        $this->assertSame(10000, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('vip_bets', 0);
    }

    public function test_currency_fractional_cents_and_insufficient_balance_do_not_move_money(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 5]);
        $this->casino($user, 'bet', ['currency' => 'USD'])->assertOk()->assertJsonPath('error_code', 'CURRENCY_NOT_SUPPORTED');
        $this->casino($user, 'bet', ['amount' => '0.001'])->assertStatus(400);
        $this->casino($user, 'bet')->assertOk()->assertJsonPath('error_code', 'INSUFFICIENT_FUNDS');
        $user->eur_balance_cents = 2000;
        $user->save();
        $this->casino($user, 'bet')->assertOk()->assertJsonPath('error_code', 'INSUFFICIENT_FUNDS');
        $this->assertSame(2000, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 0);
    }

    public function test_unsupported_currency_is_reported_before_unknown_player(): void
    {
        $user = User::factory()->make(['user_code' => 'missing-player']);

        $this->casino($user, 'balance', ['currency' => 'XYZ'])->assertOk()->assertJsonPath('error_code', 'CURRENCY_NOT_SUPPORTED');

        $this->assertDatabaseCount('wallet_entries', 0);
    }

    public function test_successful_casino_transaction_returns_eur_currency(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 100000]);

        $this->casino($user, 'bet', ['amount' => '10.00'])->assertOk()->assertJsonPath('balance', 990)->assertJsonPath('currency', 'EUR');

        $this->assertSame(99000, $user->fresh()->eur_balance_cents);
    }

    public function test_sports_raw_body_hmac_is_required_without_timestamp(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->postJson('/api/flexrix/sportsbook/debit', ['merchant_key' => 'FLX_test'])->assertUnauthorized();
        $this->sports($user, 'balance')->assertOk()->assertJsonPath('balance', 100)->assertJsonPath('currency', 'EUR');
        $this->sports($user, 'debit')->assertOk()->assertJsonPath('balance', 90);
        $this->assertSame(9000, $user->fresh()->eur_balance_cents);
    }

    public function test_sports_settlement_corrections_apply_adjustment_and_cache_replies(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->sports($user, 'debit')->assertOk();
        $this->sports($user, 'credit', ['transaction_id' => 'win-1', 'amount' => '15.70'])->assertOk()->assertJsonPath('balance', 105.7);
        $correction = ['transaction_id' => 'corr-v2', 'amount' => '0.00', 'settlement' => ['is_correction' => true, 'adjustment' => '-15.70', 'settlement_version' => 2]];
        $this->sports($user, 'credit', $correction)->assertOk()->assertJsonPath('balance', 90);
        $this->sports($user, 'credit', ['transaction_id' => 'corr-v3', 'amount' => '10.00', 'settlement' => ['is_correction' => true, 'adjustment' => '10.00', 'settlement_version' => 3]])->assertOk()->assertJsonPath('balance', 100);
        $this->sports($user, 'credit', $correction)->assertOk()->assertJsonPath('balance', 90);
        $this->assertSame(10000, $user->fresh()->eur_balance_cents);
        $this->assertSame(10.0, (float) GameTransaction::sum('win_amount'));
        $this->assertDatabaseCount('wallet_entries', 4);
    }

    public function test_sports_rollback_without_transaction_id_is_idempotent_and_late_debit_is_blocked(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->sports($user, 'debit')->assertOk();
        $rollback = ['original_transaction_id' => 'sports-bet'];
        $this->sports($user, 'rollback', $rollback)->assertOk()->assertJsonPath('balance', 100);
        $this->sports($user, 'rollback', $rollback)->assertOk()->assertJsonPath('balance', 100);
        $this->sports($user, 'rollback', ['original_transaction_id' => 'late-debit'])->assertOk();
        $this->sports($user, 'debit', ['transaction_id' => 'late-debit'])->assertOk()->assertJsonPath('error_code', 'TRANSACTION_CANCELED');
        $this->assertSame(10000, $user->fresh()->eur_balance_cents);
        $this->assertSame(0, VipAccount::where('user_id', $user->id)->first()->wager_cents);
    }

    public function test_a_reversal_cannot_refund_another_player_or_a_different_amount(): void
    {
        $this->game();
        $first = User::factory()->create(['eur_balance_cents' => 10000]);
        $other = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->casino($first, 'bet')->assertOk();
        $this->casino($other, 'refund', ['transaction_id' => 'refund-other', 'ref_transaction_id' => 'bet-1'])->assertStatus(400);
        $this->casino($first, 'refund', ['transaction_id' => 'refund-wrong', 'ref_transaction_id' => 'bet-1', 'amount' => '11.00'])->assertStatus(400);
        $this->assertSame(9000, $first->fresh()->eur_balance_cents);
        $this->assertSame(10000, $other->fresh()->eur_balance_cents);
    }

    public function test_negative_adjustment_without_funds_is_retryable_and_atomic(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 0]);
        $this->sports($user, 'credit', ['transaction_id' => 'correction', 'amount' => '0', 'settlement' => ['is_correction' => true, 'adjustment' => '-15.70']])->assertStatus(503)->assertJsonPath('error_code', 'INTERNAL');
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('gaming_callbacks', 0);
        $this->assertDatabaseCount('wallet_entries', 0);
        $this->assertDatabaseCount('operational_alerts', 1);
    }

    public function test_free_spin_campaign_winnings_credit_once_without_vip_stake(): void
    {
        $this->game();
        $user = User::factory()->create();
        $this->casino($user, 'win', ['transaction_id' => 'fs-total', 'amount' => '12.50', 'type' => 'freespin', 'freespin_total' => 1, 'freespin_id' => 'promo-one'])->assertOk()->assertJsonPath('balance', 12.5);
        $this->casino($user, 'win', ['transaction_id' => 'fs-total', 'amount' => '12.50', 'type' => 'freespin', 'freespin_total' => 1, 'freespin_id' => 'promo-one', 'casino_request_retry_count' => 3])->assertOk();
        $this->assertDatabaseCount('vip_bets', 0);
        $this->assertSame(1250, $user->fresh()->eur_balance_cents);
    }

    public function test_launch_is_signed_and_sports_jwt_has_correct_issuer_eur_and_short_expiry(): void
    {
        $game = $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 1234]);
        Http::fake(['api.upaflex.online/*' => Http::response(['url' => 'https://launch.example.test/?sessionId=private-session'])]);
        $this->actingAs($user)->get('/game/'.$game->slug)->assertOk()->assertInertia(fn ($page) => $page->where('launchUrl', 'https://launch.example.test/?sessionId=private-session'));
        Http::assertSent(function ($request): bool {
            $this->assertSame('EUR', $request['currency']);
            $this->assertSame(12.34, $request['balance']);
            $headers = ['X-Merchant-Id' => $request->header('X-Merchant-Id')[0], 'X-Timestamp' => $request->header('X-Timestamp')[0], 'X-Nonce' => $request->header('X-Nonce')[0]];
            $this->assertSame(hash_hmac('sha1', FlexrixService::canonical(array_merge($request->data(), $headers)), 'casino-test-secret'), $request->header('X-Sign')[0]);

            return str_contains($request->url(), '/v1/native/test-game/launch');
        });
        $result = app(FlexrixService::class)->sportsbook($user);
        parse_str(parse_url($result['launch_url'], PHP_URL_QUERY), $query);
        [$header, $payload, $signature] = explode('.', $query['token']);
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $this->assertSame('EUR', $claims['currency']);
        $this->assertSame('FLX_test', $claims['iss']);
        $this->assertSame($user->user_code, $claims['sub']);
        $this->assertSame(300, $claims['exp'] - $claims['iat']);
        $this->assertSame(rtrim(strtr(base64_encode(hash_hmac('sha256', $header.'.'.$payload, 'sports-test-secret', true)), '+/', '-_'), '='), $signature);
    }

    public function test_catalogue_pagination_preserves_legacy_and_disables_only_missing_flexrix_games(): void
    {
        $legacy = Game::factory()->create();
        $missing = $this->game();
        Http::fake(['api.upaflex.online/v1/native/providers' => Http::response(['items' => [['code' => 'Pragmatic', 'label' => 'Pragmatic Play']]]), 'api.upaflex.online/v1/native/games*' => function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) $query['page'];

            return Http::response(['items' => [['uuid' => 'flexrix-new-'.$page, 'slug' => 'new-'.$page, 'name' => 'New game '.$page, 'provider' => 'Pragmatic', 'image' => 'https://images.example.test/game.jpg', 'active' => $page === 1, 'has_freespins' => 1, 'rtp' => 96]], '_meta' => ['pageCount' => 2, 'totalCount' => 2, 'currentPage' => $page]]);
        }]);
        $result = app(FlexrixService::class)->sync();
        $this->assertSame(0, $result['errors']);
        $this->assertSame(2, $result['games']);
        $this->assertTrue($legacy->fresh()->status);
        $this->assertFalse($missing->fresh()->status);
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->has('allGames', 1)->where('allGames.0.game_code', 'flexrix-new-1'));
        Http::assertSentCount(3);
    }

    public function test_admin_imports_the_complete_flexrix_catalogue_without_a_provider_code(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        Http::fake([
            'api.upaflex.online/v1/native/providers' => Http::response(['items' => [['code' => 'Pragmatic', 'label' => 'Pragmatic Play']]]),
            'api.upaflex.online/v1/native/games*' => Http::response(['items' => [['uuid' => 'flexrix-admin-game', 'slug' => 'admin-game', 'name' => 'Admin game', 'provider' => 'Pragmatic', 'active' => true]], '_meta' => ['pageCount' => 1, 'totalCount' => 1, 'currentPage' => 1]]),
        ]);

        $this->actingAs($admin)->post('/admin/sync')->assertRedirect()->assertSessionHas('success', 'Provider catalog synchronized.');

        $this->assertDatabaseHas('games', ['game_code' => 'flexrix-admin-game', 'integration' => 'flexrix', 'status' => true]);
        $this->assertDatabaseHas('admin_audits', ['actor_id' => $admin->id, 'action' => 'catalog_sync', 'target' => 'flexrix']);
        Http::assertSentCount(2);
    }

    public function test_failed_catalogue_does_not_disable_previous_games(): void
    {
        $game = $this->game();
        Http::fake(['api.upaflex.online/v1/native/providers' => Http::response(['items' => []]), 'api.upaflex.online/v1/native/games*' => Http::response(['items' => [], '_meta' => ['pageCount' => 2, 'currentPage' => 1, 'totalCount' => 100]])]);
        $this->assertSame(1, app(FlexrixService::class)->sync()['errors']);
        $this->assertTrue($game->fresh()->status);
        $this->assertDatabaseCount('games', 1);
        Http::assertSentCount(3);
    }

    public function test_missing_configuration_and_retired_game_do_not_launch_or_call_nexus(): void
    {
        $user = User::factory()->create();
        $legacy = Game::factory()->create();
        $this->actingAs($user)->get('/game/'.$legacy->slug)->assertNotFound();
        config(['gaming.flexrix.casino_key' => '', 'gaming.flexrix.sportsbook_url' => '']);
        $this->get('/sportsbook')->assertOk()->assertInertia(fn ($page) => $page->where('launchUrl', null));
        $this->assertSame(1, app(FlexrixService::class)->sync()['errors']);
        Http::assertNothingSent();
    }

    public function test_refunded_wager_recovers_claimed_rakeback_and_preserves_wallet_ledger(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->casino($user, 'bet', ['amount' => '50.00'])->assertOk();
        $reward = VipReward::where('user_id', $user->id)->where('kind', 'rakeback')->where('key', 'like', 'rakeback:daily:%')->firstOrFail();
        $this->travel(1)->days();
        $this->actingAs($user)->post('/vip/rewards/'.$reward->id.'/claim')->assertRedirect();
        $this->assertSame(5005, $user->fresh()->eur_balance_cents);
        $this->casino($user, 'refund', ['transaction_id' => 'refund-claimed', 'ref_transaction_id' => 'bet-1', 'amount' => '50.00'])->assertOk()->assertJsonPath('balance', 100);
        $this->assertSame(10000, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseHas('wallet_entries', ['user_id' => $user->id, 'kind' => 'vip_reversal', 'delta_cents' => -5]);
        $this->assertDatabaseHas('vip_accounts', ['user_id' => $user->id, 'wager_cents' => 0, 'reward_debt_cents' => 0]);
        $this->assertSame(0, (int) WalletEntry::where('user_id', $user->id)->sum('delta_cents'));
    }

    public function test_void_after_partial_cashout_excludes_only_returned_stake_from_vip(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->sports($user, 'debit', ['amount' => '50.00'])->assertOk();
        $this->sports($user, 'credit', ['transaction_id' => 'partial', 'reason' => 'partial_cashout', 'amount' => '10.00'])->assertOk();
        $this->sports($user, 'credit', ['transaction_id' => 'void-remaining', 'reason' => 'void_after_partial_cashout', 'amount' => '25.00'])->assertOk()->assertJsonPath('balance', 85);
        $this->assertDatabaseHas('vip_accounts', ['user_id' => $user->id, 'wager_cents' => 2500]);
        $daily = VipReward::where('user_id', $user->id)->where('key', 'like', 'rakeback:daily:%')->firstOrFail();
        $this->assertSame(25000, $daily->numerator);
        $this->assertSame(2, $daily->amount_cents);
    }

    public function test_failed_ledger_insert_rolls_back_wallet_receipt_and_vip(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $scope = hash('sha256', 'casino:FXC_test');
        WalletEntry::factory()->create(['user_id' => $user->id, 'reference' => 'flexrix:'.hash('sha256', $scope.':bet-1')]);
        $this->casino($user, 'bet')->assertStatus(503);
        $this->assertSame(10000, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('game_transactions', 0);
        $this->assertDatabaseCount('gaming_callbacks', 0);
        $this->assertDatabaseCount('vip_bets', 0);
    }

    public function test_form_signature_preserves_spaces_empty_fields_and_exact_raw_hmac(): void
    {
        $this->game();
        $user = User::factory()->create(['eur_balance_cents' => 10000]);
        $this->casino($user, 'bet', ['note' => ' signed text ', 'optional' => ''])->assertOk();
        $this->assertSame('X-Merchant-Id=FXC_test&note=%20signed%20text%20&optional=', FlexrixService::canonical(['optional' => '', 'note' => ' signed text ', 'X-Merchant-Id' => 'FXC_test']));
        $raw = '{"merchant_key":"FLX_test","external_player_id":"'.$user->user_code.'","amount":"1.00","currency":"EUR","transaction_id":"tampered"}';
        $this->call('POST', '/api/flexrix/sportsbook/debit', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_FLEXRIX_MERCHANT' => 'FLX_test', 'HTTP_X_FLEXRIX_SIGNATURE' => hash_hmac('sha256', $raw.' ', 'sports-test-secret')], $raw)->assertUnauthorized();
        $this->assertSame(9000, $user->fresh()->eur_balance_cents);
    }

    public function test_launch_rakeback_uses_authenticated_game_activity_and_reversals_in_both_modes(): void
    {
        $this->game();
        foreach (['stakes' => 200, 'net_loss' => 120] as $basis => $expected) {
            LaunchSetting::find(1)->update(['rakeback_enabled' => true, 'rakeback_basis' => $basis, 'rakeback_bps' => 2000, 'rakeback_starts_at' => now()->startOfDay(), 'rakeback_ends_at' => now()->addDay()->startOfDay()]);
            $user = User::factory()->create(['eur_balance_cents' => 10000]);
            $this->casino($user, 'bet', ['transaction_id' => $basis.'-bet'])->assertOk();
            $this->casino($user, 'win', ['transaction_id' => $basis.'-win', 'amount' => '4.00'])->assertOk();
            $reward = VipReward::where('user_id', $user->id)->where('key', 'like', 'launch:daily:%')->firstOrFail();
            $this->assertSame($expected, $reward->amount_cents);
            $this->assertDatabaseMissing('vip_rewards', ['user_id' => $user->id, 'key' => 'rakeback:daily:'.now()->toDateString()]);
            $this->casino($user, 'refund', ['transaction_id' => $basis.'-refund', 'ref_transaction_id' => $basis.'-bet'])->assertOk();
            $this->casino($user, 'refund', ['transaction_id' => $basis.'-refund', 'ref_transaction_id' => $basis.'-bet'])->assertOk();
            $this->assertSame(0, $reward->fresh()->amount_cents);
        }
    }
}

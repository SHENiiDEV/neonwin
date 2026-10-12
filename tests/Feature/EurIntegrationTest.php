<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\GameSession;
use App\Models\GameTransaction;
use App\Models\NexusOperation;
use App\Models\Provider;
use App\Models\User;
use App\Services\NexusGgrService;
use App\Services\VerifiedDepositService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EurIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nexus.agent_code' => 'test-agent', 'nexus.agent_secret' => 'test-secret', 'nexus.agent_token' => 'test-api-token', 'nexus.eur_agent_confirmed' => true]);
        Http::preventStrayRequests();
        $this->travelTo(now('UTC')->setDate(2026, 10, 12));
    }

    private function payload(User $user, array $detail = [], string $type = 'slot'): array
    {
        return ['method' => 'transaction', 'agent_code' => 'test-agent', 'agent_secret' => 'test-secret', 'user_code' => $user->user_code, 'user_balance' => 999999,
            'game_type' => $type, $type => array_merge(['provider_code' => 'PRAGMATIC', 'game_code' => 'game', 'round_id' => 123, 'txn_id' => '123', 'txn_id_v2' => 'unique-spin', 'txn_type' => 'debit_credit', 'bet_money' => 0.10, 'win_money' => 0.20], $detail)];
    }

    public static function gameTypes(): array
    {
        return array_map(fn ($type) => [$type], ['slot', 'live', 'SB', 'MN', 'FH']);
    }

    #[DataProvider('gameTypes')]
    public function test_all_game_types_settle_exact_eur_once(string $type): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 30, 'game_balance' => 555]);
        $payload = $this->payload($user, [], $type);
        $payload['info'] = json_encode(['couponCode' => '123', 'status' => 'won', 'totalOdds' => 2, 'stake' => 0.1, 'payout' => 0.2, 'betslips' => []]);

        $this->postJson('/gold_api', $payload)->assertOk()->assertJsonPath('user_balance', 0.4);
        $this->postJson('/gold_api', $payload)->assertOk()->assertJsonPath('msg', 'TRANSACTION_ALREADY_PROCESSED');

        $this->assertSame(40, $user->fresh()->eur_balance_cents);
        $this->assertSame('555.00', $user->fresh()->game_balance);
        $this->assertDatabaseCount('wallet_entries', 1);
        $this->assertDatabaseCount('game_transactions', 1);
        $this->assertSame('won', GameTransaction::first()->raw_payload['info']['status']);
        $this->assertArrayNotHasKey('agent_secret', GameTransaction::first()->raw_payload);
    }

    public function test_multiple_inner_credits_and_transition_retry_are_not_lost_or_duplicated(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 100]);
        $this->postJson('/gold_api', $this->payload($user, ['txn_type' => 'debit', 'bet_money' => 0.10, 'win_money' => 200]))->assertJsonPath('user_balance', 0.9);
        $this->postJson('/gold_api', $this->payload($user, ['txn_type' => 'credit', 'bet_money' => 900, 'win_money' => 0.20, 'txn_id_v2' => 'credit-one']))->assertJsonPath('user_balance', 1.1);
        $second = $this->payload($user, ['txn_type' => 'credit', 'win_money' => 0.03, 'txn_id_v2' => 'credit-two']);
        $this->postJson('/gold_api', $second)->assertJsonPath('user_balance', 1.13);
        $this->travelTo(now('UTC')->setDate(2026, 10, 31));
        $second['slot']['txn_id'] = 'credit-two';
        unset($second['slot']['txn_id_v2']);
        $this->postJson('/gold_api', $second)->assertJsonPath('msg', 'TRANSACTION_ALREADY_PROCESSED');
        $this->assertSame(113, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('game_transactions', 3);
    }

    public function test_legacy_round_identifier_is_never_a_unique_transaction_fallback(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 100]);
        $payload = $this->payload($user);
        unset($payload['slot']['txn_id_v2']);
        $this->postJson('/gold_api', $payload)->assertUnprocessable()->assertJsonPath('msg', 'UNIQUE_TRANSACTION_ID_REQUIRED');
        $this->travelTo(now('UTC')->setDate(2026, 10, 15));
        $this->postJson('/gold_api', $payload)->assertUnprocessable();
        $payload['slot']['txn_id'] = 'new-unique';
        $this->postJson('/gold_api', $payload)->assertOk()->assertJsonPath('user_balance', 1.1);
    }

    public function test_missing_auth_wrong_agent_and_supplied_wrong_token_fail(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 100]);
        $payload = $this->payload($user);
        unset($payload['agent_secret']);
        $this->postJson('/gold_api', $payload)->assertForbidden();
        $payload = $this->payload($user);
        $payload['agent_code'] = 'another-agent';
        $this->postJson('/gold_api', $payload)->assertForbidden();
        $payload = $this->payload($user);
        $payload['user_token'] = 'wrong';
        $this->postJson('/gold_api', $payload)->assertForbidden();
        $this->assertDatabaseCount('wallet_entries', 0);
        $this->assertSame(100, $user->fresh()->eur_balance_cents);
    }

    public function test_balance_requires_private_launch_token_and_returns_numeric_zero(): void
    {
        $user = User::factory()->create();
        GameSession::create(['user_id' => $user->id, 'provider_code' => 'SPORTSBOOK', 'game_code' => '', 'token' => 'private-launch-token', 'token_hash' => hash('sha256', 'private-launch-token')]);
        $payload = ['method' => 'user_balance', 'agent_code' => 'test-agent', 'agent_secret' => 'test-secret', 'user_code' => $user->user_code];
        $this->postJson('/gold_api', $payload)->assertUnprocessable();
        $payload['user_token'] = 'private-launch-token';
        $payload['game_code'] = '';
        $this->postJson('/gold_api', $payload)->assertOk()->assertJsonPath('user_balance', 0);
        $this->assertArrayNotHasKey('token', GameSession::first()->toArray());
    }

    public function test_insufficient_funds_rejects_bet_but_conflicts_and_invalid_amounts_return_non_2xx(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 9]);
        $this->postJson('/gold_api', $this->payload($user))->assertOk()->assertJsonPath('msg', 'INSUFFICIENT_USER_FUNDS');
        $this->postJson('/gold_api', $this->payload($user, ['txn_type' => 'credit', 'win_money' => '0.001']))->assertUnprocessable();
        $payload = $this->payload($user, ['txn_type' => 'credit', 'win_money' => '0.01']);
        $this->postJson('/gold_api', $payload)->assertOk();
        $payload['slot']['win_money'] = 0.02;
        $this->postJson('/gold_api', $payload)->assertConflict();
        $this->assertSame(10, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseHas('operational_alerts', ['kind' => 'callback_conflict']);
    }

    public function test_database_constraint_prevents_duplicate_scoped_transactions_even_when_inserted_directly(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 30]);
        $this->postJson('/gold_api', $this->payload($user))->assertOk();
        $record = GameTransaction::first()->getAttributes();
        unset($record['id']);
        $record['transaction_id'] = 'different-global-key';
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('game_transactions')->insert($record);
    }

    public function test_sportsbook_launch_persists_token_privately_and_failure_never_becomes_demo_success(): void
    {
        $user = User::factory()->create();
        Provider::create(['code' => 'SPORTSBOOK', 'name' => 'Sportsbook', 'status' => true]);
        Http::fake(['api.nexusggr.eu' => Http::sequence()->push(['status' => 1, 'code' => 7777777, 'launch_url' => 'https://games.example.test/play', 'token' => 'private-token', 'fc_code' => 'private-fc'])->push(['status' => 0, 'code' => 7770407, 'msg' => 'Provider disabled', 'launch_url' => 'https://fake.test'])]);
        $this->actingAs($user)->get('/sportsbook')->assertInertia(fn ($page) => $page->component('Sportsbook')->where('launchUrl', 'https://games.example.test/play'));
        Http::assertSent(fn ($request) => $request['method'] === 'game_launch' && $request['provider_code'] === 'SPORTSBOOK' && $request['game_code'] === '' && $request['lang'] === 'en' && ! isset($request['currency']));
        $session = GameSession::first();
        $this->assertSame('private-token', $session->token);
        $this->assertStringNotContainsString('private-token', $session->getRawOriginal('token'));
        $result = app(NexusGgrService::class)->launchGame($user, '', 'SPORTSBOOK');
        $this->assertSame(0, $result['status']);
        $this->assertArrayNotHasKey('launch_url', $result);
    }

    public function test_deposit_request_uses_server_price_and_never_self_credits(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 0, 'game_balance' => 55]);
        $this->actingAs($user)->postJson('/deposit', ['amount' => 9000000, 'method' => 'card', 'price' => 1])->assertUnprocessable();
        $this->postJson('/deposit', ['package' => 'eur_25', 'amount' => 9000000, 'price' => 1])->assertStatus(503)->assertJsonPath('status', 'unavailable');
        $this->assertDatabaseHas('deposits', ['amount_cents' => 2500, 'status' => 'unavailable']);
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 0);
    }

    public function test_trusted_payment_confirmation_credits_exactly_once_and_rejects_unavailable_requests(): void
    {
        $user = User::factory()->create();
        $deposit = Deposit::create(['user_id' => $user->id, 'reference' => (string) Str::uuid(), 'package' => 'eur_25', 'amount_cents' => 2500, 'status' => 'pending', 'payment_provider' => 'fake-provider']);
        $service = app(VerifiedDepositService::class);
        $service->confirm($deposit->reference, 'fake-provider', 'verified-event-1', 2500, 'EUR');
        $service->confirm($deposit->reference, 'fake-provider', 'verified-event-1', 2500, 'EUR');
        $this->assertSame(3000, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 2);
        $deposit->update(['status' => 'unavailable']);
        $this->expectException(\LogicException::class);
        $service->confirm($deposit->reference, 'fake-provider', 'verified-event-2', 2500, 'EUR');
    }

    public function test_reward_cumulative_totals_are_progress_only_and_late_events_do_not_reopen_a_finished_tour(): void
    {
        $user = User::factory()->create();
        $operation = NexusOperation::factory()->create(['user_id' => $user->id, 'method' => 'tour_create', 'provider_code' => 'PRAGMATIC', 'game_code' => 'game', 'status' => 'active', 'active_tour_key' => $user->id.':PRAGMATIC:game']);
        $detail = ['txn_type' => 'credit', 'win_money' => '0.20', 'tour_id' => $operation->external_id, 'tour_total_win' => 9, 'tour_rest_count' => 2, 'tour_end' => false];
        $this->postJson('/gold_api', $this->payload($user, $detail))->assertOk();
        $detail = array_merge($detail, ['txn_id_v2' => 'tour-final', 'win_money' => '0.03', 'tour_total_win' => 10, 'tour_rest_count' => 0, 'tour_end' => true]);
        $this->postJson('/gold_api', $this->payload($user, $detail))->assertOk();
        $this->postJson('/gold_api', $this->payload($user, $detail))->assertJsonPath('msg', 'TRANSACTION_ALREADY_PROCESSED');
        $detail = array_merge($detail, ['txn_id_v2' => 'tour-late', 'win_money' => '0.01', 'tour_total_win' => 9, 'tour_rest_count' => 1, 'tour_end' => false]);
        $this->postJson('/gold_api', $this->payload($user, $detail))->assertOk();
        $this->assertSame(24, $user->fresh()->eur_balance_cents);
        $this->assertSame('finished', $operation->fresh()->status);
        $this->assertSame(10, $operation->fresh()->progress['tour_total_win']);
        $this->assertSame(0, $operation->fresh()->progress['tour_rest_count']);
        $this->assertTrue($operation->fresh()->progress['tour_end']);
        $this->assertNull($operation->fresh()->active_tour_key);
        $this->assertDatabaseCount('wallet_entries', 3);
    }
}

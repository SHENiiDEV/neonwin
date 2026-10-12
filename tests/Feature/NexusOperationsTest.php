<?php

namespace Tests\Feature;

use App\Models\AdminAudit;
use App\Models\Game;
use App\Models\NexusOperation;
use App\Models\Provider;
use App\Models\User;
use App\Services\NexusGgrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class NexusOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['nexus.agent_code' => 'test-agent', 'nexus.agent_secret' => 'test-secret', 'nexus.agent_token' => 'test-token', 'nexus.eur_agent_confirmed' => true, 'nexus.rtp_enabled' => true, 'nexus.rewards_enabled' => true, 'nexus.rtp_providers' => ['PRAGMATIC'], 'nexus.agent_type' => 'normal']);
        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_bulk_rtp_encodes_user_codes_as_json_string_and_audits_without_secrets(): void
    {
        $user = User::factory()->create();
        Http::fake(['api.nexusggr.eu' => Http::response(['status' => 1, 'code' => 7777777, 'msg' => 'SUCCESS'])]);
        $data = ['request_id' => (string) Str::uuid(), 'method' => 'control_users_rtp', 'user_codes' => [$user->user_code], 'rtp' => 90];
        $this->actingAs($this->admin())->post('/admin/operations', $data)->assertSessionHas('success');
        $this->post('/admin/operations', $data)->assertSessionHas('success');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => is_string($request['user_codes']) && json_decode($request['user_codes'], true) === [$user->user_code] && $request['rtp'] === 90);
        $this->assertDatabaseHas('nexus_operations', ['status' => 'succeeded']);
        $this->assertDatabaseCount('admin_audits', 2);
        $this->assertStringNotContainsString('test-token', NexusOperation::first()->toJson());
    }

    public function test_rtp_enforces_agent_ranges_and_capabilities_without_http_mutation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->admin());
        $base = ['request_id' => (string) Str::uuid(), 'method' => 'control_rtp', 'user_codes' => [$user->user_code], 'provider_code' => 'PRAGMATIC', 'rtp' => 100];
        $this->postJson('/admin/operations', $base)->assertUnprocessable();
        config(['nexus.agent_type' => 'test']);
        $base['rtp'] = 119;
        $this->postJson('/admin/operations', $base)->assertUnprocessable();
        config(['nexus.agent_type' => 'normal', 'nexus.rtp_enabled' => false]);
        $base['rtp'] = 90;
        $this->postJson('/admin/operations', $base)->assertUnprocessable();
        Http::assertNothingSent();
        $this->assertDatabaseCount('nexus_operations', 0);
    }

    private function reward(User $user, string $method): array
    {
        Provider::firstOrCreate(['code' => 'PRAGMATIC'], ['name' => 'Pragmatic', 'status' => true]);
        Game::firstOrCreate(['provider_code' => 'PRAGMATIC', 'game_code' => 'game'], ['name' => 'Game', 'status' => true, 'bet_levels' => ['1' => 0.20, '2' => 0.40]]);

        return ['request_id' => (string) Str::uuid(), 'method' => $method, 'user_codes' => [$user->user_code], 'provider_code' => 'PRAGMATIC', 'game_code' => 'game', 'amount' => '10.01', 'max_bet' => 2, 'bet_level' => 1, 'spin_count' => 10, 'expiration_time' => now()->addDay()->toISOString()];
    }

    public function test_bonus_timeout_is_durable_unknown_and_same_id_is_not_resent(): void
    {
        $user = User::factory()->create();
        $data = $this->reward($user, 'bonus_create');
        Http::fake(['api.nexusggr.eu' => Http::failedConnection()]);
        $this->actingAs($this->admin())->post('/admin/operations', $data)->assertSessionHas('error');
        $this->post('/admin/operations', $data)->assertSessionHas('error');
        Http::assertSentCount(1);
        $this->assertDatabaseHas('nexus_operations', ['external_id' => $data['request_id'], 'status' => 'unknown']);
        $this->assertDatabaseHas('operational_alerts', ['reference' => $data['request_id']]);
        $this->assertSame(0, $user->fresh()->eur_balance_cents);
    }

    public function test_tour_uses_catalog_level_and_cancel_refund_never_credits_player(): void
    {
        $user = User::factory()->create(['eur_balance_cents' => 17]);
        $data = $this->reward($user, 'tour_create');
        Http::fake(['api.nexusggr.eu' => Http::sequence()->push(['status' => 1, 'code' => 7777777, 'msg' => 'SUCCESS', 'bet' => 0.2, 'total_bet' => 4, 'real_win' => 9.99])->push(['status' => 1, 'code' => 7777777, 'msg' => 'SUCCESS', 'rest_count' => 10, 'total_win' => 0, 'canceled_money' => 9.99])]);
        $this->actingAs($this->admin())->post('/admin/operations', $data)->assertSessionHas('success');
        $operation = NexusOperation::first();
        $this->postJson('/admin/operations', array_merge($data, ['request_id' => (string) Str::uuid()]))->assertUnprocessable();
        $this->post('/admin/operations/'.$operation->id.'/cancel')->assertSessionHas('success');
        $this->assertSame('canceled', $operation->fresh()->status);
        $this->assertNull($operation->fresh()->active_tour_key);
        $this->assertSame(17, $user->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 0);
        Http::assertSent(fn ($request) => $request['method'] === 'tour_cancel' && $request['tour_id'] === $data['request_id'] && ! isset($request['user_code']));
    }

    public function test_game_catalog_preserves_maintenance_and_limits_calls_and_v2_capabilities(): void
    {
        Http::fake(['api.nexusggr.eu' => Http::sequence()->push(['status' => 1, 'code' => 7777777, 'providers' => [['code' => 'PRAGMATIC', 'name' => 'Pragmatic', 'type' => 'slot', 'status' => 0]]])->push(['status' => 1, 'code' => 7777777, 'games' => [['game_code' => 'game', 'game_name' => ['fr' => 'Jeu', 'en' => 'English game'], 'status' => 0, 'banner' => 'https://cdn.example.test/game.png', 'bet_levels' => ['1' => 0.2]]]])]);
        $service = app(NexusGgrService::class);
        $stats = $service->syncGames('PRAGMATIC');
        $this->assertSame(1, $stats['games_created']);
        $this->assertFalse(Provider::first()->status);
        $this->assertFalse(Game::first()->status);
        $this->assertSame('English game', Game::first()->name);
        $this->assertSame(7770429, $service->request('provider_list')['code']);
        Http::assertSentCount(2);
        Cache::flush();
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['api.nexusggr.eu' => Http::response(['status' => 1, 'code' => 7777777, 'games' => []])]);
        $service->fetchGameList('EVOLUTION');
        Http::assertSent(fn ($request) => $request['method'] === 'game_list');
    }

    public function test_game_logs_require_date_range_use_zero_page_and_are_rate_limited(): void
    {
        Http::fake(['api.nexusggr.eu' => Http::response(['status' => 1, 'code' => 7777777, 'SB' => [], 'page' => 0])]);
        $service = app(NexusGgrService::class);
        $this->assertSame(0, $service->getGameLog()['status']);
        $this->assertSame(1, $service->getGameLog(null, 'SB', '2026-10-12 00:00:00', '2026-10-12 01:00:00')['status']);
        $this->assertSame(7770429, $service->getGameLog(null, 'SB', '2026-10-12 00:00:00', '2026-10-12 01:00:00')['code']);
        Http::assertSent(fn ($request) => $request['page'] === 0 && $request['game_type'] === 'SB');
        Http::assertSentCount(1);
    }

    public function test_bulk_rtp_audit_does_not_reuse_an_unrelated_groups_previous_value(): void
    {
        $players = User::factory()->count(4)->create();
        $admin = $this->admin();
        Http::fake(['api.nexusggr.eu' => Http::response(['status' => 1, 'code' => 7777777])]);
        $this->actingAs($admin);
        foreach ([$players->take(2), $players->skip(2)] as $group) {
            $this->post('/admin/operations', ['request_id' => (string) Str::uuid(), 'method' => 'control_users_rtp', 'user_codes' => $group->pluck('user_code')->all(), 'rtp' => 90])->assertSessionHas('success');
        }
        $this->assertNull(AdminAudit::where('action', 'control_users_rtp_requested')->latest('id')->first()->before);
        $this->post('/admin/operations', ['request_id' => (string) Str::uuid(), 'method' => 'control_users_rtp', 'user_codes' => $players->take(2)->reverse()->pluck('user_code')->all(), 'rtp' => 91])->assertSessionHas('success');
        $this->assertSame(90, AdminAudit::where('action', 'control_users_rtp_requested')->latest('id')->first()->before['rtp']);
    }

    public function test_reconciliation_requires_admin_and_evidence_and_never_changes_player_funds(): void
    {
        $player = User::factory()->create(['eur_balance_cents' => 123]);
        $operation = NexusOperation::factory()->create(['user_id' => $player->id, 'method' => 'tour_create', 'active_tour_key' => $player->id.':PRAGMATIC:game']);
        $url = '/admin/operations/'.$operation->id.'/reconcile';
        $this->actingAs($player)->postJson($url, ['status' => 'finished', 'evidence' => 'Verified with Nexus back office.'])->assertForbidden();
        $admin = $this->admin();
        $this->actingAs($admin)->postJson($url, ['status' => 'finished', 'evidence' => 'short'])->assertUnprocessable();
        $this->post($url, ['status' => 'finished', 'evidence' => 'Verified with Nexus back office.'])->assertRedirect();
        $this->assertSame('finished', $operation->fresh()->status);
        $this->assertNull($operation->fresh()->active_tour_key);
        $this->assertSame(123, $player->fresh()->eur_balance_cents);
        $this->assertDatabaseCount('wallet_entries', 0);
        $this->assertDatabaseHas('admin_audits', ['actor_id' => $admin->id, 'action' => 'nexus_reconciled', 'target' => $operation->external_id]);
        $this->postJson($url, ['status' => 'active', 'evidence' => 'Verified with Nexus back office.'])->assertConflict();
        Http::assertNothingSent();
    }
}

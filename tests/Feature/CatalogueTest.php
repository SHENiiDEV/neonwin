<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Provider;
use App\Services\NexusGgrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_paginates_all_available_games_and_excludes_disabled_providers(): void
    {
        $provider = Provider::factory()->create();
        Game::factory()->count(40)->create(['provider_code' => $provider->code]);
        $disabled = Provider::factory()->create(['status' => false]);
        Game::factory()->create(['provider_code' => $disabled->code]);
        Game::factory()->create(['provider_code' => $provider->code, 'status' => false]);

        $this->get('/?browse=all&page=2')->assertInertia(fn ($page) => $page
            ->component('Home')->has('allGames', 4)->where('catalogue.total', 40)
            ->where('catalogue.page', 2)->has('providers', 1)->where('providers.0.games_count', 40));
    }

    public function test_sync_retires_missing_games_without_deleting_history_or_using_invented_artwork(): void
    {
        config(['nexus.agent_code' => 'test', 'nexus.agent_token' => 'test', 'nexus.base_url' => 'https://api.nexusggr.eu']);
        Cache::flush();
        Http::preventStrayRequests();
        $provider = Provider::factory()->create(['code' => 'PRAGMATIC']);
        $old = Game::factory()->create(['provider_code' => $provider->code, 'game_code' => 'old-game']);
        Http::fake(['api.nexusggr.eu' => fn ($request) => Http::response($request['method'] === 'provider_list'
            ? ['status' => 1, 'code' => 7777777, 'providers' => [['code' => 'PRAGMATIC', 'name' => 'Pragmatic', 'type' => 'slot', 'status' => 1]]]
            : ['status' => 1, 'code' => 7777777, 'games' => [['game_code' => 'current', 'game_name' => ['en' => 'Current game'], 'status' => 1]]])]);

        $result = app(NexusGgrService::class)->syncGames();

        $this->assertSame(0, $result['errors']);
        $this->assertDatabaseHas('games', ['id' => $old->id, 'status' => false]);
        $this->assertDatabaseHas('games', ['game_code' => 'current', 'status' => true, 'banner_url' => '']);
        Http::assertSentCount(2);
    }

    public function test_sync_command_reports_failure_when_connection_is_not_confirmed(): void
    {
        $this->mock(NexusGgrService::class)->shouldReceive('syncGames')->once()->andReturn(['providers_synced' => 0, 'games_created' => 0, 'games_updated' => 0, 'errors' => 1]);

        $this->artisan('nexus:sync')->assertFailed();
    }
}

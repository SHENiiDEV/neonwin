<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_search_endpoint_returns_games(): void
    {
        Game::create([
            'game_code' => 'vs20sweetbonz',
            'name' => 'Sweet Bonanza',
            'slug' => 'sweet-bonanza',
            'provider_code' => 'PRAGMATIC',
            'category' => 'slots',
            'status' => true,
            'is_popular' => true,
            'rtp' => 96.50,
        ]);

        $response = $this->getJson('/api/games/search?q=sweet');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'games' => [
                    '*' => ['id', 'name', 'slug', 'game_code', 'provider_code', 'category', 'rtp'],
                ],
                'count',
            ]);
    }

    public function test_short_query_returns_empty_array(): void
    {
        $response = $this->getJson('/api/games/search?q=s');

        $response->assertStatus(200)
            ->assertJson([
                'games' => [],
                'count' => 0,
            ]);
    }
}

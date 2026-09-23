<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamePreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_favorite_game(): void
    {
        $user = User::factory()->create();

        // Add to favorites
        $response = $this->actingAs($user)->postJson('/api/games/favorite', [
            'game_code' => 'sweet-bonanza-xmas-vs20sbxmas',
        ]);
        $response->assertOk();
        $response->assertJson(['is_favorite' => true]);
        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $user->id,
            'game_code' => 'sweet-bonanza-xmas-vs20sbxmas',
        ]);

        // Toggle off
        $response2 = $this->actingAs($user)->postJson('/api/games/favorite', [
            'game_code' => 'sweet-bonanza-xmas-vs20sbxmas',
        ]);
        $response2->assertOk();
        $response2->assertJson(['is_favorite' => false]);
        $this->assertDatabaseMissing('user_favorites', [
            'user_id' => $user->id,
            'game_code' => 'sweet-bonanza-xmas-vs20sbxmas',
        ]);
    }

    public function test_user_can_record_recently_played_game(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/games/recent', [
            'game_code' => 'sweet-bonanza-vs20fruitsw',
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('user_recent_games', [
            'user_id' => $user->id,
            'game_code' => 'sweet-bonanza-vs20fruitsw',
        ]);
    }

    public function test_user_can_toggle_sound_preference(): void
    {
        $user = User::factory()->create(['sound_enabled' => true]);

        $response = $this->actingAs($user)->postJson('/api/user/sound-toggle');
        $response->assertOk();
        $response->assertJson(['sound_enabled' => false]);

        $user->refresh();
        $this->assertFalse((bool) $user->sound_enabled);
    }
}

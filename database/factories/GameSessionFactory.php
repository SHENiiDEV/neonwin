<?php

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 'provider_code' => 'PRAGMATIC', 'game_code' => 'test-game', 'token' => 'test-session-token', 'token_hash' => hash('sha256', 'test-session-token'), 'status' => 'launched',
        ];
    }
}

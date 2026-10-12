<?php

namespace Database\Factories;

use App\Models\NexusOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NexusOperation>
 */
class NexusOperationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(), 'user_id' => User::factory(), 'external_id' => (string) Str::uuid(), 'method' => 'bonus_create', 'status' => 'unknown', 'request' => ['provider_code' => 'PRAGMATIC', 'game_code' => 'test-game', 'amount' => 1], 'result' => ['status' => 0, 'code' => 0, 'msg' => 'Test fixture; no remote request was sent.'],
        ];
    }
}

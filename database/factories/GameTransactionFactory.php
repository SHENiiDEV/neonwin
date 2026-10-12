<?php

namespace Database\Factories;

use App\Models\GameTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<GameTransaction> */
class GameTransactionFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'transaction_id' => (string) Str::uuid(), 'agent_code' => 'test-agent', 'provider_code' => 'PRAGMATIC', 'game_code' => 'test-slot', 'game_type' => 'slot', 'round_id' => (string) fake()->unique()->numberBetween(1, 100000000), 'currency' => 'EUR', 'type' => 'debit', 'bet_amount' => '1.00', 'win_amount' => '0.00', 'balance_before' => '100.00', 'balance_after' => '99.00'];
    }
}

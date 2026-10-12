<?php

namespace Database\Factories;

use App\Models\GameTransaction;
use App\Models\User;
use App\Models\VipBet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VipBet> */
class VipBetFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'game_transaction_id' => fn (array $attributes) => GameTransaction::factory()->create(['user_id' => $attributes['user_id']])->id, 'amount_cents' => 100, 'paid_round_key' => null];
    }
}

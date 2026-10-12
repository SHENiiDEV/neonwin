<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WheelSpin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WheelSpin>
 */
class WheelSpinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 'request_id' => fake()->uuid(), 'prize_index' => 0, 'amount_cents' => 10, 'prizes' => [['cents' => 10, 'weight' => 1]],
        ];
    }
}

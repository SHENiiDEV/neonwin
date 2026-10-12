<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WheelFundEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WheelFundEntry>
 */
class WheelFundEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 'reference' => 'fixture:'.fake()->uuid(), 'delta_cents' => 100, 'balance_after_cents' => 100,
        ];
    }
}

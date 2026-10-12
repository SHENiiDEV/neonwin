<?php

namespace Database\Factories;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 'reference' => (string) Str::uuid(), 'package' => 'eur_25', 'amount_cents' => 2500, 'currency' => 'EUR', 'status' => 'unavailable',
        ];
    }
}

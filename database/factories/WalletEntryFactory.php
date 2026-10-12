<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WalletEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WalletEntry>
 */
class WalletEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 'reference' => 'test:'.Str::uuid(), 'kind' => 'test_zero_entry', 'delta_cents' => 0, 'balance_before_cents' => 0, 'balance_after_cents' => 0,
        ];
    }
}

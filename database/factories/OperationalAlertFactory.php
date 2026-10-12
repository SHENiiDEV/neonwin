<?php

namespace Database\Factories;

use App\Models\OperationalAlert;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OperationalAlert>
 */
class OperationalAlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => 'test_fixture', 'reference' => (string) Str::uuid(), 'message' => 'Test alert requiring reconciliation.', 'status' => 'open',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\AdminAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdminAudit>
 */
class AdminAuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(), 'action' => 'test_fixture', 'target' => 'test-only', 'after' => ['status' => 'test'],
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Provider> */
class ProviderFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->lexify('PROVIDER_????')), 'name' => fake()->company(), 'type' => 'slot', 'status' => true];
    }
}

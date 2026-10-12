<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new \LogicException('Promotion fixtures may only be seeded in an isolated test environment.');
        }

        Promotion::factory()->create();
    }
}

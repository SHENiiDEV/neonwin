<?php

namespace Database\Seeders;

use App\Models\PromotionRedemption;
use Illuminate\Database\Seeder;

class PromotionRedemptionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new \LogicException('Promotion fixtures may only be seeded in an isolated test environment.');
        }

        PromotionRedemption::factory()->create();
    }
}

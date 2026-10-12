<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Promotion> */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Registration deposit offer', 'code' => strtoupper(fake()->unique()->bothify('PROMO-????##')), 'kind' => 'deposit_bonus', 'enabled' => true, 'percent_bps' => 3000, 'min_deposit_cents' => 0, 'terms' => '30% on the first confirmed deposit. Offers do not stack.'];
    }
}

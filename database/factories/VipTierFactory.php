<?php

namespace Database\Factories;

use App\Models\VipTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VipTier> */
class VipTierFactory extends Factory
{
    public function definition(): array
    {
        return ['level' => 1, 'name' => 'Test tier', 'threshold_cents' => 0, 'bonus_cents' => 0, 'daily_bps' => 10, 'weekly_bps' => 5, 'monthly_bps' => 5, 'checkin_cents' => 10];
    }
}

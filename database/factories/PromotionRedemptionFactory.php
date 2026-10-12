<?php

namespace Database\Factories;

use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PromotionRedemption> */
class PromotionRedemptionFactory extends Factory
{
    public function definition(): array
    {
        return ['promotion_id' => Promotion::factory(), 'user_id' => User::factory(), 'request_id' => (string) Str::uuid(), 'snapshot' => ['name' => 'Deposit offer', 'kind' => 'deposit_bonus', 'percent_bps' => 3000, 'min_deposit_cents' => 0, 'max_bonus_cents' => null, 'terms' => 'First verified deposit only.']];
    }
}

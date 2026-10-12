<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VipReward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<VipReward> */
class VipRewardFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'key' => 'test:'.Str::uuid(), 'kind' => 'quest', 'status' => 'available', 'amount_cents' => 10, 'snapshot' => ['name' => 'Test reward']];
    }
}

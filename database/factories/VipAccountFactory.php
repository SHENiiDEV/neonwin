<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VipAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VipAccount> */
class VipAccountFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'wager_cents' => 0, 'level' => 1, 'streak' => 0];
    }
}

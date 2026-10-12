<?php

namespace Database\Factories;

use App\Models\VipQuest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VipQuest> */
class VipQuestFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Test daily quest', 'kind' => 'wager', 'target' => 100, 'min_bet_cents' => 10, 'reward_cents' => 10, 'enabled' => true];
    }
}

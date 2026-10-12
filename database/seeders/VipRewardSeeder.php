<?php

namespace Database\Seeders;

use App\Models\VipReward;
use Illuminate\Database\Seeder;

class VipRewardSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            return;
        }
        VipReward::factory()->create();
    }
}

<?php

namespace Database\Seeders;

use App\Models\VipBet;
use Illuminate\Database\Seeder;

class VipBetSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            return;
        }
        VipBet::factory()->create();
    }
}

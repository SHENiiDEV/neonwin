<?php

namespace Database\Seeders;

use App\Models\VipTier;
use Illuminate\Database\Seeder;

class VipTierSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            return;
        }
        VipTier::firstOrCreate(['level' => 1], VipTier::factory()->raw());
    }
}

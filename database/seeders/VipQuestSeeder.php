<?php

namespace Database\Seeders;

use App\Models\VipQuest;
use Illuminate\Database\Seeder;

class VipQuestSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            return;
        }
        VipQuest::factory()->create();
    }
}

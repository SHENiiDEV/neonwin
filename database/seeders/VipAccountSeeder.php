<?php

namespace Database\Seeders;

use App\Models\VipAccount;
use Illuminate\Database\Seeder;

class VipAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            return;
        }
        VipAccount::factory()->create();
    }
}

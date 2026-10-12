<?php

namespace Database\Seeders;

use App\Models\LaunchSetting;
use Illuminate\Database\Seeder;

class LaunchSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new \LogicException('Operational fixtures may only be seeded in an isolated test environment.');
        }
        LaunchSetting::factory()->create();
    }
}

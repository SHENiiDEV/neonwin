<?php

namespace Database\Seeders;

use App\Models\AdminAudit;
use Illuminate\Database\Seeder;

class AdminAuditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new \LogicException('Operational fixtures may only be seeded in an isolated test environment.');
        }

        AdminAudit::factory()->create();
    }
}

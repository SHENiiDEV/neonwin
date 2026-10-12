<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('launch_rakeback_entries', function (Blueprint $table): void {
            $table->index('vip_reward_id');
        });
    }

    public function down(): void
    {
        Schema::table('launch_rakeback_entries', function (Blueprint $table): void {
            $table->dropIndex(['vip_reward_id']);
        });
    }
};

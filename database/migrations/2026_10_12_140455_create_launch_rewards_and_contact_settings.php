<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launch_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('wheel_enabled')->default(true);
            $table->timestamp('wheel_ends_at')->nullable();
            $table->unsignedBigInteger('wheel_budget_cents')->default(0);
            $table->json('wheel_prizes');
            $table->boolean('rakeback_enabled')->default(false);
            $table->string('rakeback_basis')->default('stakes');
            $table->unsignedInteger('rakeback_bps')->default(2000);
            $table->timestamp('rakeback_starts_at')->nullable();
            $table->timestamp('rakeback_ends_at')->nullable();
            $table->boolean('telegram_enabled')->default(false);
            $table->text('telegram_token')->nullable();
            $table->text('telegram_group_id')->nullable();
            $table->timestamps();
        });
        DB::table('launch_settings')->insert(['id' => 1, 'wheel_prizes' => json_encode([
            ['cents' => 10, 'weight' => 35], ['cents' => 20, 'weight' => 25], ['cents' => 50, 'weight' => 15], ['cents' => 100, 'weight' => 10],
            ['cents' => 150, 'weight' => 7], ['cents' => 200, 'weight' => 4], ['cents' => 300, 'weight' => 3], ['cents' => 500, 'weight' => 1],
        ]), 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('wheel_spins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->uuid('request_id');
            $table->unsignedInteger('prize_index');
            $table->unsignedInteger('amount_cents');
            $table->json('prizes');
            $table->timestamps();
            $table->unique(['user_id', 'request_id']);
            $table->index(['user_id', 'created_at']);
        });
        Schema::create('wheel_fund_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('reference')->unique();
            $table->bigInteger('delta_cents');
            $table->unsignedBigInteger('balance_after_cents');
            $table->timestamps();
        });
        Schema::create('launch_rakeback_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_transaction_id')->constrained();
            $table->foreignId('vip_reward_id')->constrained();
            $table->bigInteger('basis_cents');
            $table->bigInteger('stake_cents');
            $table->bigInteger('payout_cents');
            $table->unique(['game_transaction_id', 'vip_reward_id']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->string('contact_channel')->nullable();
            $table->text('contact_handle')->nullable();
            $table->timestamp('contact_consent_at')->nullable();
        });
        Schema::create('registration_notices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained();
            $table->string('status')->default('pending');
            $table->string('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['registration_notices', 'launch_rakeback_entries', 'wheel_fund_entries', 'wheel_spins', 'launch_settings'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['contact_channel', 'contact_handle', 'contact_consent_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vip_tiers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('level')->unique();
            $table->string('name');
            $table->unsignedBigInteger('threshold_cents');
            $table->unsignedInteger('bonus_cents')->default(0);
            $table->unsignedInteger('daily_bps');
            $table->unsignedInteger('weekly_bps');
            $table->unsignedInteger('monthly_bps');
            $table->unsignedInteger('checkin_cents')->default(10);
            $table->timestamps();
        });
        Schema::create('vip_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained();
            $table->unsignedBigInteger('wager_cents')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->date('last_checkin_date')->nullable();
            $table->unsignedInteger('streak')->default(0);
            $table->timestamps();
        });
        Schema::create('vip_quests', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('kind');
            $table->string('provider_code')->nullable();
            $table->string('game_code')->nullable();
            $table->unsignedBigInteger('target');
            $table->unsignedInteger('min_bet_cents')->default(10);
            $table->unsignedInteger('reward_cents');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
        Schema::create('vip_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('key');
            $table->string('kind');
            $table->string('status')->default('active');
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->unsignedBigInteger('numerator')->default(0);
            $table->unsignedBigInteger('progress')->default(0);
            $table->json('snapshot');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'key']);
            $table->index(['user_id', 'status', 'ends_at']);
        });
        Schema::create('vip_bets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('game_transaction_id')->unique()->constrained();
            $table->unsignedBigInteger('amount_cents');
            $table->string('paid_round_key')->nullable()->unique();
            $table->timestamps();
        });
        $names = ['Neon Spark', 'Bronze Pulse', 'Silver Signal', 'Golden Frequency', 'Platinum Circuit', 'Diamond Drive', 'Emerald Elite', 'Ruby Royal', 'Obsidian Circle', 'Neon Legend'];
        $thresholds = [0, 10000, 50000, 150000, 500000, 1500000, 4000000, 10000000, 25000000, 50000000];
        $bonuses = [0, 100, 300, 500, 1000, 2000, 5000, 10000, 25000, 50000];
        $daily = [10, 12, 15, 18, 20, 25, 30, 35, 40, 50];
        $other = [5, 6, 8, 9, 10, 12, 15, 18, 20, 25];
        foreach ($names as $i => $name) {
            DB::table('vip_tiers')->insert(['level' => $i + 1, 'name' => $name, 'threshold_cents' => $thresholds[$i], 'bonus_cents' => $bonuses[$i], 'daily_bps' => $daily[$i], 'weekly_bps' => $other[$i], 'monthly_bps' => $other[$i], 'checkin_cents' => 10 + $i, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ([['Daily explorer', 'wager', 2500, 10, null, null], ['Daily milestone', 'wager', 10000, 50, null, null], ['25 paid rounds', 'spins', 25, 15, null, null], ['Gates of Olympus · 20 rounds', 'spins', 20, 20, 'PRAGMATIC', 'vs20olympgate']] as [$name, $kind, $target, $reward, $provider, $game]) {
            DB::table('vip_quests')->insert(['name' => $name, 'kind' => $kind, 'target' => $target, 'reward_cents' => $reward, 'min_bet_cents' => 10, 'provider_code' => $provider, 'game_code' => $game, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (['vip_bets', 'vip_rewards', 'vip_quests', 'vip_accounts', 'vip_tiers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

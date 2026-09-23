<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add gamification, vault & referral fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('vault_balance', 12, 2)->default(0.00)->after('game_balance');
            $table->string('vault_pin')->nullable()->after('vault_balance');
            $table->foreignId('referred_by_id')->nullable()->constrained('users')->nullOnDelete()->after('vault_pin');
            $table->string('referral_code', 32)->nullable()->unique()->after('user_code');
            $table->boolean('sound_enabled')->default(true)->after('avatar');
        });

        // 2. User Crates (Mystery Loot Boxes)
        Schema::create('user_crates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // recruit, shadow, ronin, cyber, overlord, wager_100
            $table->string('name', 100);
            $table->string('tier', 20)->default('bronze'); // bronze, silver, gold, platinum, mythic
            $table->string('status', 20)->default('pending'); // pending, opened
            $table->decimal('reward_sc', 10, 2)->default(0.00);
            $table->integer('reward_xp')->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });

        // 3. User Favorites
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game_code', 100);
            $table->timestamps();
            $table->unique(['user_id', 'game_code']);
        });

        // 4. User Recent Games
        Schema::create('user_recent_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game_code', 100);
            $table->timestamp('last_played_at');
            $table->timestamps();
            $table->unique(['user_id', 'game_code']);
        });

        // 5. Multiplier Hunt & Live Challenges
        Schema::create('live_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('game_code');
            $table->string('game_name');
            $table->decimal('target_multiplier', 8, 2);
            $table->decimal('min_bet', 8, 2)->default(0.50);
            $table->decimal('prize_sc', 10, 2);
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('winner_name')->nullable();
            $table->decimal('winner_multiplier', 8, 2)->nullable();
            $table->string('status', 20)->default('active'); // active, completed, expired
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // 6. Player-to-Player Tipping
        Schema::create('tipping_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount_sc', 10, 2);
            $table->decimal('fee_sc', 10, 2);
            $table->decimal('net_amount_sc', 10, 2);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        // 7. Referral Earnings
        Schema::create('referral_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('reward_sc', 10, 2);
            $table->string('type', 50)->default('signup_bonus'); // signup_bonus, rakeback_share
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_earnings');
        Schema::dropIfExists('tipping_transactions');
        Schema::dropIfExists('live_challenges');
        Schema::dropIfExists('user_recent_games');
        Schema::dropIfExists('user_favorites');
        Schema::dropIfExists('user_crates');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn([
                'vault_balance',
                'vault_pin',
                'referred_by_id',
                'referral_code',
                'sound_enabled',
            ]);
        });
    }
};

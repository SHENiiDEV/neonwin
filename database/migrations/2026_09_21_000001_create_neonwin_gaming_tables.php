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
        // 1. Update users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_code')->unique()->nullable()->after('id');
            $table->decimal('game_balance', 16, 2)->default(1000.00)->after('email');
            $table->unsignedBigInteger('vip_xp')->default(0)->after('game_balance');
            $table->unsignedInteger('vip_level')->default(1)->after('vip_xp');
            $table->string('currency', 10)->default('USD')->after('vip_level');
            $table->string('avatar')->nullable()->after('currency');
            $table->boolean('is_admin')->default(false)->after('avatar');
        });

        // 2. Providers table
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. PRAGMATIC, HACKSAW, PGSOFT
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // 3. Games table
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code')->index();
            $table->string('game_code')->index();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('banner_url')->nullable();
            $table->string('category')->default('slots')->index(); // slots, live, crash, fishing, sports, fast, diamonds
            $table->decimal('rtp', 5, 2)->nullable();
            $table->boolean('is_popular')->default(false)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['provider_code', 'game_code']);
        });

        // 4. Game transactions table (idempotent tracking)
        Schema::create('game_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('game_code')->nullable();
            $table->string('provider_code')->nullable();
            $table->decimal('bet_amount', 16, 2)->default(0);
            $table->decimal('win_amount', 16, 2)->default(0);
            $table->decimal('balance_before', 16, 2)->default(0);
            $table->decimal('balance_after', 16, 2)->default(0);
            $table->string('type')->default('bet_win'); // bet, win, bet_win, refund
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 5. Live Community Wins table
        Schema::create('live_community_wins', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('user_avatar')->nullable();
            $table->string('game_name');
            $table->string('game_image')->nullable();
            $table->decimal('bet_amount', 16, 2);
            $table->decimal('multiplier', 8, 2);
            $table->decimal('win_amount', 16, 2);
            $table->timestamps();
        });

        // 6. Tournaments table
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('prize_pool', 16, 2)->default(800.00);
            $table->unsignedInteger('freespins')->default(350);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('active'); // active, upcoming, ended
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        // 7. Chat messages table
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('user_avatar')->nullable();
            $table->unsignedInteger('vip_level')->default(1);
            $table->text('message');
            $table->string('room')->default('global');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('tournaments');
        Schema::dropIfExists('live_community_wins');
        Schema::dropIfExists('game_transactions');
        Schema::dropIfExists('games');
        Schema::dropIfExists('providers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_code', 'game_balance', 'vip_xp', 'vip_level', 'currency', 'avatar', 'is_admin']);
        });
    }
};

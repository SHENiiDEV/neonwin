<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['games', 'providers'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->string('integration')->default('nexus')->index();
                $table->string('external_code')->nullable();
            });
        }
        Schema::table('games', function (Blueprint $table): void {
            $table->boolean('has_freespins')->default(false);
        });
        Schema::table('vip_accounts', fn (Blueprint $table) => $table->unsignedBigInteger('reward_debt_cents')->default(0));
        Schema::create('gaming_callbacks', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 64);
            $table->string('remote_id');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('game_transaction_id')->nullable()->constrained();
            $table->string('action');
            $table->bigInteger('delta_cents')->default(0);
            $table->string('fingerprint', 64)->nullable();
            $table->json('response');
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            $table->unique(['scope', 'remote_id']);
        });
        Schema::table('vip_bets', function (Blueprint $table): void {
            $table->json('contributions')->nullable();
            $table->timestamp('reversed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vip_bets', fn (Blueprint $table) => $table->dropColumn(['contributions', 'reversed_at']));
        Schema::dropIfExists('gaming_callbacks');
        Schema::table('vip_accounts', fn (Blueprint $table) => $table->dropColumn('reward_debt_cents'));
        Schema::table('games', fn (Blueprint $table) => $table->dropColumn('has_freespins'));
        foreach (['games', 'providers'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['integration', 'external_code']));
        }
    }
};

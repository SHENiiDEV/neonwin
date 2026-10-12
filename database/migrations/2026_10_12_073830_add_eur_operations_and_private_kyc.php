<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('eur_balance_cents')->default(0);
            $table->unsignedBigInteger('eur_vault_cents')->default(0);
        });
        Schema::table('games', function (Blueprint $table) {
            $table->json('bet_levels')->nullable();
        });
        Schema::table('providers', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->timestamp('synced_at')->nullable();
        });
        Schema::table('game_transactions', function (Blueprint $table) {
            $table->string('agent_code')->nullable();
            $table->string('game_type')->nullable();
            $table->string('round_id')->nullable()->index();
            $table->string('remote_transaction_id')->nullable();
            $table->string('currency', 10)->default('LEGACY');
            $table->string('fingerprint', 64)->nullable();
            $table->unique(['agent_code', 'user_id', 'provider_code', 'remote_transaction_id'], 'nexus_transaction_scope');
        });
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('provider_code');
            $table->string('game_code')->default('');
            $table->text('token');
            $table->string('token_hash', 64)->index();
            $table->string('fc_code')->nullable();
            $table->string('status')->default('launched');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'provider_code']);
        });
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('user_id')->constrained();
            $table->string('package');
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('unavailable')->index();
            $table->string('payment_provider')->nullable();
            $table->string('payment_event_id')->nullable()->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('reference')->unique();
            $table->string('kind');
            $table->bigInteger('delta_cents');
            $table->unsignedBigInteger('balance_before_cents');
            $table->unsignedBigInteger('balance_after_cents');
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
            $table->index(['user_id', 'id']);
        });
        Schema::create('kyc_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained();
            $table->text('document_path')->nullable();
            $table->text('selfie_path')->nullable();
            $table->string('status')->default('incomplete')->index();
            $table->unsignedInteger('version')->default(1);
            $table->text('reason')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('nexus_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->foreignId('actor_id')->constrained('users');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('method');
            $table->string('provider_code')->nullable();
            $table->string('game_code')->nullable();
            $table->string('status')->default('pending')->index();
            $table->json('request');
            $table->json('result')->nullable();
            $table->json('progress')->nullable();
            $table->string('active_tour_key')->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('admin_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users');
            $table->string('action');
            $table->string('target');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });
        Schema::create('operational_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->string('reference')->nullable();
            $table->text('message');
            $table->string('status')->default('open')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['operational_alerts', 'admin_audits', 'nexus_operations', 'kyc_submissions', 'wallet_entries', 'deposits', 'game_sessions'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('game_transactions', function (Blueprint $table) {
            $table->dropUnique('nexus_transaction_scope');
            $table->dropColumn(['agent_code', 'game_type', 'round_id', 'remote_transaction_id', 'currency', 'fingerprint']);
        });
        Schema::table('games', fn (Blueprint $table) => $table->dropColumn('bet_levels'));
        Schema::table('providers', fn (Blueprint $table) => $table->dropColumn(['type', 'synced_at']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['eur_balance_cents', 'eur_vault_cents']));
    }
};

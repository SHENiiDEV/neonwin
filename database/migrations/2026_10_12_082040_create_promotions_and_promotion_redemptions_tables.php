<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('kind');
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('percent_bps')->default(2000);
            $table->unsignedBigInteger('min_deposit_cents')->default(0);
            $table->unsignedBigInteger('max_bonus_cents')->nullable();
            $table->unsignedInteger('redemption_limit')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('reward')->nullable();
            $table->text('terms');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('promotion_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deposit_id')->nullable()->constrained()->restrictOnDelete();
            $table->uuid('request_id')->unique();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('bonus_cents')->default(0);
            $table->json('snapshot');
            $table->text('message')->nullable();
            $table->timestamps();
            $table->unique(['promotion_id', 'user_id']);
        });
        DB::table('promotions')->insert([
            'name' => 'First deposit bonus', 'kind' => 'first_deposit', 'enabled' => true, 'percent_bps' => 2000,
            'terms' => '20% extra on your first confirmed EUR deposit. Applied once per account. Deposit offers do not stack; the highest eligible bonus applies. Payments are currently unavailable.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_redemptions');
        Schema::dropIfExists('promotions');
    }
};

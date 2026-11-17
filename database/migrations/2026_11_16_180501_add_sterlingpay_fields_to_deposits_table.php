<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('payment_invoice_id')->nullable()->unique();
            $table->boolean('payment_test_mode')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropUnique(['payment_invoice_id']);
            $table->dropColumn(['payment_invoice_id', 'payment_test_mode']);
        });
    }
};

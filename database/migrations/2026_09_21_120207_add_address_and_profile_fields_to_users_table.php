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
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('street')->nullable()->after('date_of_birth');
            $table->string('city')->nullable()->after('street');
            $table->string('country')->nullable()->after('city');
            $table->string('postcode')->nullable()->after('country');
            $table->boolean('terms_accepted')->default(false)->after('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'surname',
                'phone',
                'date_of_birth',
                'street',
                'city',
                'country',
                'postcode',
                'terms_accepted',
            ]);
        });
    }
};

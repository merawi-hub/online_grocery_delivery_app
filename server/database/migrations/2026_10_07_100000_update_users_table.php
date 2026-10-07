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
            $table->string('phone_number')->unique()->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone_number');
            $table->enum('role', ['customer', 'admin', 'store_manager', 'picker', 'driver'])
                  ->default('customer')
                  ->after('phone_verified_at');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('avatar_url')->nullable()->after('is_active');
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone_number',
                'phone_verified_at',
                'role',
                'is_active',
                'avatar_url',
            ]);
            $table->string('email')->nullable(false)->change();
        });
    }
};

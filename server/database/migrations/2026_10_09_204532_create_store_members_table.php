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
        Schema::create('store_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('branches')->cascadeOnDelete();
            
            // Role within this store
            $table->enum('role', ['manager', 'assistant_manager', 'inventory_staff'])->default('manager');
            
            // Assignment metadata
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            // Unique constraint: one user can only have one active role per store
            $table->unique(['user_id', 'store_id', 'role']);
            
            // Indexes
            $table->index(['store_id', 'is_active']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_members');
    }
};

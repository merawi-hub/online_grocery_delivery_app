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
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            
            // Can track both parent orders and store orders
            $table->foreignId('parent_order_id')->nullable()->constrained('parent_orders')->cascadeOnDelete();
            $table->foreignId('store_order_id')->nullable()->constrained('store_orders')->cascadeOnDelete();
            
            // Status change
            $table->string('old_status')->nullable();
            $table->string('new_status');
            
            // Who made the change
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_role')->nullable(); // customer, store_manager, deliverer, admin
            
            // Additional context
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Extra context data
            
            $table->timestamp('changed_at');
            $table->timestamps();

            // Indexes
            $table->index(['parent_order_id', 'changed_at']);
            $table->index(['store_order_id', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};

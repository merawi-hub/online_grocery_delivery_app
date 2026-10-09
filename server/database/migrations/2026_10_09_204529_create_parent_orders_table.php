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
        Schema::create('parent_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique()->index();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            
            // Delivery address snapshot (full JSON with all details)
            $table->json('delivery_address');
            
            // Order summary
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total_delivery_fee', 10, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            
            // Payment
            $table->enum('payment_method', ['cash_on_delivery', 'telebirr', 'cbe_birr', 'card'])->default('cash_on_delivery');
            $table->enum('payment_status', ['pending', 'authorized', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Overall status (derived from store orders)
            $table->enum('overall_status', [
                'pending',
                'confirmed',
                'preparing',
                'ready_for_pickup',
                'out_for_delivery',
                'partially_delivered',
                'delivered',
                'cancelled',
                'failed'
            ])->default('pending');
            
            // Metadata
            $table->text('customer_notes')->nullable();
            $table->integer('store_count')->default(1); // Number of stores in this order
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['customer_id', 'overall_status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_orders');
    }
};

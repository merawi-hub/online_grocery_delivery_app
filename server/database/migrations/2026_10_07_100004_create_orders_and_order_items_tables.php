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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            
            // Full immutable snapshot of customer's granular address at checkout
            $table->json('delivery_address_snapshot');
            
            $table->enum('status', [
                'placed',
                'picking',
                'substitutions_review',
                'packed',
                'driver_assigned',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ])->default('placed');

            $table->enum('delivery_type', ['express_immediate', 'scheduled_slot'])->default('express_immediate');
            $table->timestamp('scheduled_slot_start')->nullable();
            $table->timestamp('scheduled_slot_end')->nullable();

            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0.00);
            $table->decimal('estimated_total', 10, 2);
            $table->decimal('final_total', 10, 2)->nullable();

            $table->enum('payment_method', ['cash_on_delivery', 'card_online', 'wallet'])->default('cash_on_delivery');
            $table->enum('payment_status', ['pending', 'authorized', 'paid', 'refunded'])->default('pending');
            $table->string('delivery_otp', 6)->nullable(); // Yango-style handover confirmation code
            
            $table->foreignId('picker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('special_notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            
            $table->decimal('requested_quantity', 8, 2);
            $table->decimal('picked_quantity', 8, 2)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('final_item_total', 10, 2)->nullable();
            
            $table->enum('substitution_preference', [
                'best_match',
                'contact_customer',
                'do_not_substitute'
            ])->default('best_match');

            $table->boolean('is_substituted')->default(false);
            $table->foreignId('substituted_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('picker_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};

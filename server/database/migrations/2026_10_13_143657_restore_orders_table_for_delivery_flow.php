<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('address_id')->nullable();
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
                $table->string('delivery_otp', 6)->nullable();
                
                $table->unsignedBigInteger('picker_id')->nullable();
                $table->text('special_notes')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'status']);
                $table->index(['branch_id', 'status']);

                $table->foreign('customer_id', 'fk_orders_customer_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('branch_id', 'fk_orders_branch_id')->references('id')->on('branches')->cascadeOnDelete();
                $table->foreign('address_id', 'fk_orders_address_id')->references('id')->on('addresses')->nullOnDelete();
                $table->foreign('picker_id', 'fk_orders_picker_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Fix order_items foreign key so it references orders
        if (Schema::hasTable('order_items')) {
            try {
                DB::statement('ALTER TABLE order_items DROP FOREIGN KEY order_items_order_id_foreign');
            } catch (\Exception $e) {}
            
            try {
                DB::statement('ALTER TABLE order_items ADD CONSTRAINT fk_order_items_order_id FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
            } catch (\Exception $e) {}
        }

        // Fix driver_trips foreign key so it references orders
        if (Schema::hasTable('driver_trips')) {
            try {
                DB::statement('ALTER TABLE driver_trips DROP FOREIGN KEY driver_trips_order_id_foreign');
            } catch (\Exception $e) {}
            
            try {
                DB::statement('ALTER TABLE driver_trips ADD CONSTRAINT fk_driver_trips_order_id FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
            } catch (\Exception $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};

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
        // Check if table already renamed from previous partial migration
        $tableAlreadyRenamed = Schema::hasTable('store_orders');
        $hasExistingOrders = false;
        
        if (!$tableAlreadyRenamed) {
            // First, check if there are any existing orders
            $hasExistingOrders = DB::table('orders')->exists();
        
            if ($hasExistingOrders) {
            // If there are existing orders, we need to create parent orders for them first
            $existingOrders = DB::table('orders')->get();
            
            foreach ($existingOrders as $order) {
                // Create a parent order for each existing order
                $parentOrderId = DB::table('parent_orders')->insertGetId([
                    'order_number' => $order->order_number,
                    'customer_id' => $order->customer_id,
                    'delivery_address' => $order->delivery_address_snapshot,
                    'subtotal' => $order->subtotal,
                    'total_delivery_fee' => $order->delivery_fee,
                    'platform_fee' => 0,
                    'discount' => 0,
                    'grand_total' => $order->estimated_total ?? $order->subtotal,
                    'payment_method' => $order->payment_method ?? 'cash_on_delivery',
                    'payment_status' => $order->payment_status ?? 'pending',
                    'overall_status' => $order->status,
                    'store_count' => 1,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);
                
                // Store the parent_order_id temporarily
                DB::table('orders')->where('id', $order->id)->update([
                    'picker_id' => $parentOrderId // Temporarily store parent_order_id here
                ]);
            }
        }
        }
        
        // Rename existing orders table to store_orders (if not already renamed)
        if (!$tableAlreadyRenamed) {
            Schema::rename('orders', 'store_orders');
        } else {
            // If already renamed, check if we have existing data that needs parent orders
            $hasExistingOrders = DB::table('store_orders')->exists();
        }
        
        // Check if columns already added from previous partial migration
        $columnsAlreadyAdded = Schema::hasColumn('store_orders', 'parent_order_id');
        
        // Add new columns for store order functionality (if not already added)
        if (!$columnsAlreadyAdded) {
            Schema::table('store_orders', function (Blueprint $table) {
            // Add parent_order_id column first (nullable initially)
            $table->unsignedBigInteger('parent_order_id')->nullable()->after('id');
            
            // Add store_id
            $table->foreignId('store_id')->after('parent_order_id')->default(1)->constrained('branches')->cascadeOnDelete();
            
            // Store-specific order number
            $table->string('store_order_number')->nullable()->after('order_number');
            
            // Store-specific amounts
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('estimated_total');
            $table->decimal('store_commission', 10, 2)->default(0)->after('delivery_fee');
            
            // Store manager who confirmed the order
            $table->foreignId('confirmed_by')->nullable()->after('picker_id')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
        });
        }
        
        // Update parent_order_id from temporarily stored value
        if ($hasExistingOrders && !$columnsAlreadyAdded) {
            $storeOrders = DB::table('store_orders')->get();
            foreach ($storeOrders as $order) {
                DB::table('store_orders')->where('id', $order->id)->update([
                    'parent_order_id' => $order->picker_id, // Get from temporary storage
                    'store_order_number' => 'SO-' . $order->order_number,
                    'picker_id' => null, // Reset picker_id
                ]);
            }
        }
        
        // Now add the foreign key constraint (if not already added)
        if (!$columnsAlreadyAdded) {
            Schema::table('store_orders', function (Blueprint $table) {
            $table->foreign('parent_order_id')->references('id')->on('parent_orders')->cascadeOnDelete();
        });
        }
        
        // Check if old columns still exist before trying to remove them
        $hasOldColumns = Schema::hasColumn('store_orders', 'customer_id');
        
        // Remove columns that now belong to parent order (if they still exist)
        if ($hasOldColumns) {
            Schema::table('store_orders', function (Blueprint $table) {
            // Check and drop foreign keys if they exist
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'store_orders' 
                AND REFERENCED_TABLE_NAME IS NOT NULL");
            
            $foreignKeyNames = array_column($foreignKeys, 'CONSTRAINT_NAME');
            
            if (in_array('orders_customer_id_foreign', $foreignKeyNames)) {
                $table->dropForeign('orders_customer_id_foreign');
            }
            if (in_array('store_orders_customer_id_foreign', $foreignKeyNames)) {
                $table->dropForeign(['customer_id']);
            }
            if (in_array('orders_address_id_foreign', $foreignKeyNames)) {
                $table->dropForeign('orders_address_id_foreign');
            }
            if (in_array('store_orders_address_id_foreign', $foreignKeyNames)) {
                $table->dropForeign(['address_id']);
            }
            
            $table->dropColumn([
                'customer_id',
                'address_id',
                'payment_method',
                'payment_status',
                'delivery_otp'
            ]);
        });
        }
        
        // Update status enum to be store-specific (if not already updated)
        if ($hasOldColumns) {
            // First, update any existing status values that don't match new enum
            DB::statement("UPDATE store_orders SET status = 'pending' WHERE status NOT IN ('pending', 'confirmed', 'preparing', 'ready_for_pickup', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled', 'rejected')");
            
            // Now modify the column
            DB::statement("ALTER TABLE store_orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'preparing', 'ready_for_pickup', 'picked_up', 'out_for_delivery', 'delivered', 'cancelled', 'rejected') DEFAULT 'pending'");
        }
        
        // Add indexes (if columns exist)
        if (Schema::hasColumn('store_orders', 'store_id')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->index(['store_id', 'status']);
                $table->index(['parent_order_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            // Restore original columns
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->enum('payment_method', ['cash_on_delivery', 'card_online', 'wallet'])->default('cash_on_delivery');
            $table->enum('payment_status', ['pending', 'authorized', 'paid', 'refunded'])->default('pending');
            $table->string('delivery_otp', 6)->nullable();
            
            // Drop new columns
            $table->dropForeign(['parent_order_id']);
            $table->dropForeign(['store_id']);
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn([
                'parent_order_id',
                'store_id',
                'store_order_number',
                'delivery_fee',
                'store_commission',
                'confirmed_by',
                'confirmed_at'
            ]);
        });
        
        // Rename back
        Schema::rename('store_orders', 'orders');
    }
};

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
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('branches')->cascadeOnDelete();
            
            $table->string('name'); // e.g., "Downtown", "Zone A"
            $table->text('description')->nullable();
            
            // Zone type: radius (simple circle) or polygon (complex shape)
            $table->enum('type', ['radius', 'polygon'])->default('radius');
            
            // For radius zones
            $table->decimal('radius_km', 8, 2)->nullable(); // e.g., 5.00 km
            
            // For polygon zones (store as JSON array of lat/lng coordinates)
            $table->json('polygon_coordinates')->nullable();
            
            // Delivery fee for this zone
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('minimum_order', 10, 2)->default(0);
            
            // Estimated delivery time
            $table->integer('estimated_minutes')->default(30);
            
            // Active status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            // Indexes
            $table->index(['store_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};

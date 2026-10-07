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
        Schema::create('driver_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            
            $table->enum('status', [
                'assigned',
                'accepted',
                'at_store',
                'picked_up',
                'delivering',
                'completed',
                'cancelled'
            ])->default('assigned');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'status']);
        });

        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('driver_trips')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('heading', 5, 2)->nullable(); // 0 - 360 degrees for smooth marker bearing
            $table->decimal('speed', 5, 2)->nullable(); // km/h
            $table->timestamp('created_at')->useCurrent();

            $table->index(['trip_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
        Schema::dropIfExists('driver_trips');
    }
};

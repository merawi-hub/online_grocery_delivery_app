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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('label', ['home', 'work', 'other'])->default('home');
            
            // Precision Coordinates for Live Navigation
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            
            // Geographic Area & Street
            $table->string('city');
            $table->string('district_area');
            $table->string('street_name');
            
            // Granular Physical Location & Access
            $table->string('building_name_or_no')->nullable();
            $table->string('house_villa_no');
            $table->string('floor_number')->nullable();
            $table->string('apartment_unit')->nullable();
            $table->string('gate_code')->nullable();
            
            // Last-Mile Guidance
            $table->string('nearby_landmark')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->boolean('is_default')->default(false);
            
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};

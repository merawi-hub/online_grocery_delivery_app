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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->text('description')->nullable();
            $table->enum('pricing_type', ['fixed_unit', 'weighted'])->default('fixed_unit');
            $table->string('unit_of_measure')->default('piece'); // piece, kg, g, liter, pack
            $table->unsignedInteger('step_weight_grams')->nullable(); // e.g., 250g, 500g increments
            $table->string('image_url')->nullable();
            $table->boolean('is_perishable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'sku']);
        });

        Schema::create('branch_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->decimal('stock_quantity', 10, 2)->default(0.00);
            $table->boolean('is_in_stock')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_products');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};

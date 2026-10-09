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
        // Update branches table (stores) for marketplace features
        Schema::table('branches', function (Blueprint $table) {
            // Store approval and status
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'suspended'])->default('approved')->after('is_open');
            $table->text('rejection_reason')->nullable()->after('approval_status');
            
            // Store rating and reviews
            $table->decimal('rating', 3, 2)->default(0)->after('rejection_reason'); // 0.00 to 5.00
            $table->integer('total_reviews')->default(0)->after('rating');
            $table->integer('total_orders')->default(0)->after('total_reviews');
            
            // Operating days (JSON array: [0,1,2,3,4,5,6] for Sun-Sat)
            $table->json('operating_days')->nullable()->after('closing_time');
            
            // Store description and images
            $table->text('long_description')->nullable()->after('operating_days');
            $table->string('cover_image_url')->nullable()->after('long_description');
            $table->json('gallery_images')->nullable()->after('cover_image_url');
            
            // Delivery settings
            $table->decimal('minimum_order_amount', 10, 2)->default(0)->after('delivery_radius_km');
            $table->integer('average_preparation_minutes')->default(20)->after('minimum_order_amount');
            
            // Commission and fees
            $table->decimal('commission_rate', 5, 2)->default(10.00)->after('average_preparation_minutes'); // Percentage
            
            // Contact and social
            $table->string('email')->nullable()->after('contact_phone');
            $table->string('website')->nullable()->after('email');
            
            // Metadata
            $table->timestamp('approved_at')->nullable()->after('is_open');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'approval_status',
                'rejection_reason',
                'rating',
                'total_reviews',
                'total_orders',
                'operating_days',
                'long_description',
                'cover_image_url',
                'gallery_images',
                'minimum_order_amount',
                'average_preparation_minutes',
                'commission_rate',
                'email',
                'website',
                'approved_at',
                'approved_by'
            ]);
        });
    }
};

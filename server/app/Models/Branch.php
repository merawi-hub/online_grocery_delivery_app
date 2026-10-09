<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'supermarket_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'delivery_radius_km',
        'contact_phone',
        'email',
        'website',
        'is_open',
        'opening_time',
        'closing_time',
        'operating_days',
        'approval_status',
        'rejection_reason',
        'rating',
        'total_reviews',
        'total_orders',
        'long_description',
        'cover_image_url',
        'gallery_images',
        'minimum_order_amount',
        'average_preparation_minutes',
        'commission_rate',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'delivery_radius_km' => 'float',
            'rating' => 'decimal:2',
            'total_reviews' => 'integer',
            'total_orders' => 'integer',
            'minimum_order_amount' => 'decimal:2',
            'average_preparation_minutes' => 'integer',
            'commission_rate' => 'decimal:2',
            'is_open' => 'boolean',
            'operating_days' => 'array',
            'gallery_images' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function supermarket(): BelongsTo
    {
        return $this->belongsTo(Supermarket::class);
    }

    public function branchProducts(): HasMany
    {
        return $this->hasMany(BranchProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'branch_products')
            ->withPivot(['price', 'discount_price', 'stock_quantity', 'is_in_stock'])
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'store_id');
    }

    public function storeOrders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'store_id');
    }

    /**
     * Get store members (managers, staff)
     */
    public function members(): HasMany
    {
        return $this->hasMany(StoreMember::class, 'store_id');
    }

    /**
     * Get active managers
     */
    public function activeManagers()
    {
        return $this->members()
            ->where('is_active', true)
            ->where('role', 'manager')
            ->with('user');
    }

    /**
     * Get delivery zones
     */
    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class, 'store_id');
    }

    /**
     * Get active delivery zones
     */
    public function activeDeliveryZones()
    {
        return $this->deliveryZones()->where('is_active', true);
    }

    /**
     * Get admin who approved this store
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function managers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'store_manager');
    }

    public function deliverers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'driver');
    }

    /**
     * Calculate direct distance in kilometers to a given lat/lng using Haversine formula.
     */
    public function distanceTo(float $targetLat, float $targetLng): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($targetLat - $this->latitude);
        $dLng = deg2rad($targetLng - $this->longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($targetLat)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }

    /**
     * Check if a given coordinate is within this branch's delivery radius.
     */
    public function canDeliverTo(float $targetLat, float $targetLng): bool
    {
        return $this->is_open && ($this->distanceTo($targetLat, $targetLng) <= $this->delivery_radius_km);
    }
}

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
        'is_open',
        'opening_time',
        'closing_time',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'delivery_radius_km' => 'float',
            'is_open' => 'boolean',
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
        return $this->hasMany(Order::class);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'type',
        'radius_km',
        'polygon_coordinates',
        'delivery_fee',
        'minimum_order',
        'estimated_minutes',
        'is_active',
    ];

    protected $casts = [
        'radius_km' => 'decimal:2',
        'polygon_coordinates' => 'array',
        'delivery_fee' => 'decimal:2',
        'minimum_order' => 'decimal:2',
        'estimated_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the store (branch)
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'store_id');
    }

    /**
     * Check if a coordinate is within this zone
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        if ($this->type === 'radius') {
            return $this->isWithinRadius($lat, $lng);
        }
        
        return $this->isWithinPolygon($lat, $lng);
    }

    /**
     * Check if point is within radius
     */
    protected function isWithinRadius(float $lat, float $lng): bool
    {
        $store = $this->store;
        $distance = $this->calculateDistance(
            $store->latitude,
            $store->longitude,
            $lat,
            $lng
        );
        
        return $distance <= $this->radius_km;
    }

    /**
     * Check if point is within polygon
     */
    protected function isWithinPolygon(float $lat, float $lng): bool
    {
        if (!$this->polygon_coordinates) {
            return false;
        }
        
        $polygon = $this->polygon_coordinates;
        $vertices_count = count($polygon);
        $inside = false;
        
        for ($i = 0, $j = $vertices_count - 1; $i < $vertices_count; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];
            
            $intersect = (($yi > $lng) != ($yj > $lng))
                && ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);
            
            if ($intersect) {
                $inside = !$inside;
            }
        }
        
        return $inside;
    }

    /**
     * Calculate distance between two points (Haversine formula)
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km
        
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}

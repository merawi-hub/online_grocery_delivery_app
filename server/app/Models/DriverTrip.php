<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DriverTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'driver_id',
        'status',
        'started_at',
        'completed_at',
        'distance_km',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'distance_km' => 'float',
            'duration_minutes' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DriverLocation::class, 'trip_id');
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(DriverLocation::class, 'trip_id')->latestOfMany();
    }
}

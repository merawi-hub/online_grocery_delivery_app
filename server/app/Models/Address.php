<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'latitude',
        'longitude',
        'city',
        'district_area',
        'street_name',
        'building_name_or_no',
        'house_villa_no',
        'floor_number',
        'apartment_unit',
        'gate_code',
        'nearby_landmark',
        'delivery_instructions',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get a formatted one-line display address.
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->house_villa_no ? 'House/No: ' . $this->house_villa_no : null,
            $this->building_name_or_no ? 'Bldg: ' . $this->building_name_or_no : null,
            $this->floor_number ? 'Fl: ' . $this->floor_number : null,
            $this->apartment_unit ? 'Apt: ' . $this->apartment_unit : null,
            $this->street_name,
            $this->district_area,
            $this->city,
        ]);

        return implode(', ', $parts);
    }
}

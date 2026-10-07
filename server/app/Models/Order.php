<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'branch_id',
        'address_id',
        'delivery_address_snapshot',
        'status',
        'delivery_type',
        'scheduled_slot_start',
        'scheduled_slot_end',
        'subtotal',
        'delivery_fee',
        'estimated_total',
        'final_total',
        'payment_method',
        'payment_status',
        'delivery_otp',
        'picker_id',
        'special_notes',
    ];

    protected function casts(): array
    {
        return [
            'delivery_address_snapshot' => 'array',
            'scheduled_slot_start' => 'datetime',
            'scheduled_slot_end' => 'datetime',
            'subtotal' => 'float',
            'delivery_fee' => 'float',
            'estimated_total' => 'float',
            'final_total' => 'float',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picker_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function driverTrip(): HasOne
    {
        return $this->hasOne(DriverTrip::class);
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }
}

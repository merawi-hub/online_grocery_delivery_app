<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_order_id',
        'store_id',
        'order_number',
        'store_order_number',
        'branch_id',
        'delivery_address_snapshot',
        'status',
        'delivery_type',
        'scheduled_slot_start',
        'scheduled_slot_end',
        'subtotal',
        'delivery_fee',
        'estimated_total',
        'final_total',
        'store_commission',
        'picker_id',
        'confirmed_by',
        'confirmed_at',
        'special_notes',
    ];

    protected $casts = [
        'delivery_address_snapshot' => 'array',
        'scheduled_slot_start' => 'datetime',
        'scheduled_slot_end' => 'datetime',
        'confirmed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'final_total' => 'decimal:2',
        'store_commission' => 'decimal:2',
    ];

    /**
     * Get the parent order
     */
    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(ParentOrder::class);
    }

    /**
     * Get the store (branch)
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'store_id');
    }

    /**
     * Get the branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get order items
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Get the picker assigned to this order
     */
    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picker_id');
    }

    /**
     * Get the manager who confirmed this order
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get status history
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Get delivery job
     */
    public function deliveryJob()
    {
        return $this->hasOne(DriverTrip::class, 'order_id');
    }

    /**
     * Update parent order overall status when this changes
     */
    protected static function booted()
    {
        static::updated(function ($storeOrder) {
            if ($storeOrder->wasChanged('status')) {
                $storeOrder->parentOrder->updateOverallStatus();
            }
        });
    }
}

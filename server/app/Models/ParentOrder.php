<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'delivery_address',
        'subtotal',
        'total_delivery_fee',
        'platform_fee',
        'discount',
        'grand_total',
        'payment_method',
        'payment_status',
        'payment_reference',
        'paid_at',
        'overall_status',
        'customer_notes',
        'store_count',
    ];

    protected $casts = [
        'delivery_address' => 'array',
        'subtotal' => 'decimal:2',
        'total_delivery_fee' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_at' => 'datetime',
        'store_count' => 'integer',
    ];

    /**
     * Get the customer who placed this order
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get all store orders for this parent order
     */
    public function storeOrders(): HasMany
    {
        return $this->hasMany(StoreOrder::class);
    }

    /**
     * Get status history
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Check if all store orders are delivered
     */
    public function isFullyDelivered(): bool
    {
        return $this->storeOrders()
            ->where('status', '!=', 'delivered')
            ->doesntExist();
    }

    /**
     * Check if any store order is cancelled
     */
    public function hasAnyCancellation(): bool
    {
        return $this->storeOrders()
            ->where('status', 'cancelled')
            ->exists();
    }

    /**
     * Update overall status based on store orders
     */
    public function updateOverallStatus(): void
    {
        $storeOrders = $this->storeOrders;
        
        if ($storeOrders->every(fn($order) => $order->status === 'delivered')) {
            $this->overall_status = 'delivered';
        } elseif ($storeOrders->every(fn($order) => $order->status === 'cancelled')) {
            $this->overall_status = 'cancelled';
        } elseif ($storeOrders->contains(fn($order) => $order->status === 'delivered')) {
            $this->overall_status = 'partially_delivered';
        } elseif ($storeOrders->contains(fn($order) => $order->status === 'out_for_delivery')) {
            $this->overall_status = 'out_for_delivery';
        } elseif ($storeOrders->every(fn($order) => $order->status === 'preparing')) {
            $this->overall_status = 'preparing';
        } else {
            $this->overall_status = 'confirmed';
        }
        
        $this->save();
    }
}

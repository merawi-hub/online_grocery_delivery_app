<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_order_id',
        'store_order_id',
        'old_status',
        'new_status',
        'changed_by',
        'changed_by_role',
        'notes',
        'metadata',
        'changed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'changed_at' => 'datetime',
    ];

    /**
     * Get the parent order
     */
    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(ParentOrder::class);
    }

    /**
     * Get the store order
     */
    public function storeOrder(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class);
    }

    /**
     * Get the user who made the change
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Log a status change
     */
    public static function logChange(
        $orderId,
        string $orderType,
        ?string $oldStatus,
        string $newStatus,
        ?int $changedBy = null,
        ?string $changedByRole = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return self::create([
            $orderType === 'parent' ? 'parent_order_id' : 'store_order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'changed_by_role' => $changedByRole,
            'notes' => $notes,
            'metadata' => $metadata,
            'changed_at' => now(),
        ]);
    }
}

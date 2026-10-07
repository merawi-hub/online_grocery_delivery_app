<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'requested_quantity',
        'picked_quantity',
        'unit_price',
        'final_item_total',
        'substitution_preference',
        'is_substituted',
        'substituted_product_id',
        'picker_notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'float',
            'picked_quantity' => 'float',
            'unit_price' => 'float',
            'final_item_total' => 'float',
            'is_substituted' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function substitutedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'substituted_product_id');
    }
}

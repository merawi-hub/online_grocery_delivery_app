<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchProduct extends Model
{
    use HasFactory;

    protected $table = 'branch_products';

    protected $fillable = [
        'branch_id',
        'product_id',
        'price',
        'discount_price',
        'stock_quantity',
        'is_in_stock',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'discount_price' => 'float',
            'stock_quantity' => 'float',
            'is_in_stock' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->discount_price ?? $this->price;
    }
}

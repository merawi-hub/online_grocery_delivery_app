<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'description',
        'pricing_type',
        'unit_of_measure',
        'step_weight_grams',
        'image_url',
        'is_perishable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_perishable' => 'boolean',
            'is_active' => 'boolean',
            'step_weight_grams' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function branchProducts(): HasMany
    {
        return $this->hasMany(BranchProduct::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_products')
            ->withPivot(['price', 'discount_price', 'stock_quantity', 'is_in_stock'])
            ->withTimestamps();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isWeighted(): bool
    {
        return $this->pricing_type === 'weighted';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'role',
        'assigned_by',
        'assigned_at',
        'is_active',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user (store manager/staff)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the store (branch)
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'store_id');
    }

    /**
     * Get the user who assigned this member
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

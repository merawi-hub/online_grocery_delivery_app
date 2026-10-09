<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'phone_verified_at',
        'role',
        'branch_id',
        'vehicle_type',
        'approval_status',
        'is_active',
        'avatar_url',
        'assigned_by',
        'assigned_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'assigned_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function pickingOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'picker_id');
    }

    public function driverTrips(): HasMany
    {
        return $this->hasMany(DriverTrip::class, 'driver_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedStaff(): HasMany
    {
        return $this->hasMany(User::class, 'assigned_by');
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isPicker(): bool
    {
        return $this->role === 'picker';
    }

    public function isStoreManager(): bool
    {
        return $this->role === 'store_manager';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}

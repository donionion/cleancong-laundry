<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lokasi file: app/Models/User.php
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_COURIER = 'courier';
    public const ROLE_STAFF = 'staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke Order: Pesanan yang dibuat oleh user (sebagai Customer).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Relasi ke Order: Pesanan yang ditugaskan kepada user (sebagai Courier/Kurir).
     */
    public function courierOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'courier_id');
    }

    /**
     * Relasi ke OrderLog: Catatan log yang diperbarui oleh user/staf.
     */
    public function orderLogs(): HasMany
    {
        return $this->hasMany(OrderLog::class, 'updated_by');
    }

    /**
     * Helper check role
     */
    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isCourier(): bool
    {
        return $this->role === self::ROLE_COURIER;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }
}

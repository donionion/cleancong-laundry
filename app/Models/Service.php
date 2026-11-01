<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lokasi file: app/Models/Service.php
 */
class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price_per_kg',
        'duration_hours',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'decimal:2',
            'duration_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke Order: Satu layanan laundry dapat digunakan di banyak order.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'service_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lokasi file: app/Models/OrderLog.php
 */
class OrderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status_note',
        'updated_by',
    ];

    /**
     * Relasi ke Order yang dicatat.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relasi ke User/Staf/Kurir yang membuat catatan log.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lokasi file: app/Models/Order.php
 */
class Order extends Model
{
    use HasFactory;

    // Status operasional
    public const STATUS_JEMPUT = 'Jemput';
    public const STATUS_DIPROSES = 'Diproses';
    public const STATUS_SIAP_DIANTAR = 'Siap Diantar';
    public const STATUS_SELESAI = 'Selesai';

    // Status pembayaran Midtrans
    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'courier_id',
        'service_id',
        'total_weight',
        'total_price',
        'status',
        'payment_status',
        'snap_token',
        'pickup_address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    /**
     * Pelanggan pemilik pesanan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kurir yang menangani penjemputan/pengantaran pesanan.
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    /**
     * Jenis layanan laundry yang dipilih.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Riwayat log tahapan pesanan (diurutkan dari yang terbaru).
     */
    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class, 'order_id')->latest();
    }

    /**
     * Helper cepat untuk menambahkan riwayat log pesanan.
     */
    public function addLog(string $statusNote, ?int $updatedBy = null): OrderLog
    {
        return $this->logs()->create([
            'status_note' => $statusNote,
            'updated_by' => $updatedBy ?? auth()->id(),
        ]);
    }
}

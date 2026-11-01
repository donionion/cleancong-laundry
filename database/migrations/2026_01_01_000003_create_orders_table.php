<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Lokasi file: database/migrations/2026_01_01_000003_create_orders_table.php
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel users (pelanggan yang memesan)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Relasi ke kurir yang ditugaskan (nullable karena awal order belum ada kurir)
            $table->foreignId('courier_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Relasi ke jenis layanan laundry
            $table->foreignId('service_id')
                  ->constrained('services')
                  ->onDelete('restrict');

            // Berat laundry (kg) - awal bisa estimasi pelanggan, diverifikasi kurir/timbangan
            $table->decimal('total_weight', 8, 2)->default(1.00);

            // Total tagihan dalam Rupiah
            $table->decimal('total_price', 12, 2);

            // Status operasional laundry sesuai PLAN.md CleanCong
            $table->enum('status', [
                'Jemput',        // Kurir sedang menjemput atau menunggu penjemputan
                'Diproses',      // Sedang ditimbang, dicuci, dikeringkan di workshop
                'Siap Diantar',  // Pakaian selesai disetrika, di-QC, siap dikirim kurir
                'Selesai'        // Sudah diterima pelanggan
            ])->default('Jemput');

            // Status transaksi pembayaran Midtrans Payment Gateway
            $table->enum('payment_status', [
                'unpaid',        // Belum dibayar / pending
                'paid',          // Pembayaran berhasil diverifikasi via callback
                'expired'        // Waktu pembayaran habis / dibatalkan
            ])->default('unpaid');

            // Token Snap dari Midtrans untuk rendering pop-up checkout frontend
            $table->string('snap_token')->nullable();

            // Titik alamat penjemputan & catatan pelanggan
            $table->text('pickup_address');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexing untuk query pelacakan resi dan dashboard status
            $table->index(['user_id', 'status']);
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

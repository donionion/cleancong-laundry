<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Lokasi file: database/migrations/2026_01_01_000004_create_order_logs_table.php
     */
    public function up(): void
    {
        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();

            // Relasi ke order yang dicatat
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade');

            // Catatan perubahan status & tahapan QC
            // Contoh: "Kurir Ahmad telah menjemput pakaian dari Jl. Melati No. 4"
            // "Pakaian sedang dicuci dengan detergen higienis anti-bakteri"
            // "Quality Control selesai, wangi lavender, dipacking rapi"
            $table->text('status_note');

            // User/Staf/Kurir yang melakukan update
            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            // Indexing untuk riwayat log per order
            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_logs');
    }
};

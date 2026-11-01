<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Lokasi file: database/migrations/2026_01_01_000002_create_services_table.php
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Cuci Kering Setrika Reguler", "Express 6 Jam"
            $table->decimal('price_per_kg', 12, 2); // Harga per kilogram atau satuan
            $table->integer('duration_hours'); // Durasi pengerjaan dalam jam
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

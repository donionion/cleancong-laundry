<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Lokasi file: database/migrations/2026_01_01_000001_create_users_table.php
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            // Role multi-user sesuai PLAN.md: customer, courier, staff
            $table->enum('role', ['customer', 'courier', 'staff'])->default('customer');
            $table->string('phone', 20)->nullable();
            $table->rememberToken();
            $table->timestamps();

            // Indexing untuk pencarian cepat berdasarkan role & email
            $table->index('role');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

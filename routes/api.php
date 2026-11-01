<?php

use App\Http\Controllers\MidtransPaymentController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - CleanCong Online Laundry System
| Lokasi file: routes/api.php
|--------------------------------------------------------------------------
*/

// 1. Webhook Midtrans (Wajib bypass CSRF verification)
Route::post('/midtrans-callback', [MidtransPaymentController::class, 'handleCallback'])
    ->name('midtrans.callback');

// 2. Pelacakan Resi Publik (Tanpa harus login)
Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');

// 3. Endpoint yang memerlukan autentikasi (Sanctum / Session)
Route::middleware(['auth:sanctum'])->group(function () {
    // Alur Pelanggan
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/checkout', [MidtransPaymentController::class, 'createSnapToken'])->name('orders.checkout');

    // Alur Kurir: Verifikasi Timbangan Aktual di Lokasi
    Route::post('/orders/{id}/verify-weight', [OrderController::class, 'verifyWeight'])
        ->name('orders.verify-weight');

    // Alur Staf Workshop: Update Status Cuci, Kering, Setrika, QC
    Route::post('/orders/{id}/update-status', [OrderController::class, 'updateStatus'])
        ->name('orders.update-status');
});

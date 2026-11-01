<?php

use App\Models\Order;
use App\Models\Service;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - CleanCong Online Laundry System
| Lokasi file: routes/web.php
|--------------------------------------------------------------------------
*/

// Landing Page Interaktif (untuk demo, simulasi iklan & kalkulator)
Route::get('/', function () {
    $services = Service::where('is_active', true)->get();
    return view('landing', compact('services'));
})->name('home');

// Halaman Callback Sukses Midtrans Redirect
Route::get('/orders/{id}/success', function ($id) {
    $order = Order::with(['service', 'logs'])->findOrFail($id);
    return view('orders.success', compact('order'));
})->name('orders.success');

// Halaman Tracking Resi
Route::get('/track', function () {
    return view('orders.track');
})->name('orders.track');

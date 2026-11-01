# Panduan Integrasi CleanCong Laravel & Midtrans

Dokumen ini berisi petunjuk penempatan file dan instalasi backend CleanCong (Online Laundry System with Pickup & Delivery).

---

## 1. Penempatan File dalam Struktur Direktori Laravel

Salin file-file yang telah dibuat ke direktori proyek Laravel Anda:

```text
cleancong-laravel/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── MidtransPaymentController.php   --> [app/Http/Controllers/MidtransPaymentController.php]
│   │       └── OrderController.php             --> [app/Http/Controllers/OrderController.php]
│   └── Models/
│       ├── User.php                            --> [app/Models/User.php]
│       ├── Service.php                         --> [app/Models/Service.php]
│       ├── Order.php                           --> [app/Models/Order.php]
│       └── OrderLog.php                        --> [app/Models/OrderLog.php]
├── config/
│   └── midtrans.php                            --> [config/midtrans.php]
├── database/
│   └── migrations/
│       ├── 2026_01_01_000001_create_users_table.php
│       ├── 2026_01_01_000002_create_services_table.php
│       ├── 2026_01_01_000003_create_orders_table.php
│       └── 2026_01_01_000004_create_order_logs_table.php
├── resources/
│   └── views/
│       └── landing.blade.php                   --> (Atau gunakan landing-page-cleancong.html)
├── routes/
│   ├── api.php                                 --> [routes/api.php]
│   └── web.php                                 --> [routes/web.php]
└── .env                                        --> Konfigurasi DB & Midtrans
```

---

## 2. Instalasi Dependensi Midtrans PHP SDK

Jalankan perintah Composer di terminal proyek Laravel:

```bash
composer require midtrans/midtrans-php
```

---

## 3. Menjalankan Database Migration & Seeding MySQL

Pastikan MySQL menyala dan database `cleancong_laundry` telah dibuat. Lalu jalankan:

```bash
php artisan migrate
```

Untuk menyisipkan data layanan awal (Seeder):

```bash
php artisan tinker
```
Lalu masukkan:
```php
\App\Models\Service::insert([
    ['name' => 'Cuci Kering Setrika Reguler', 'price_per_kg' => 8000, 'duration_hours' => 48, 'description' => 'Cuci bersih, detergen anti-bakteri, setrika uap rapi, packing plastik bersegel.', 'is_active' => true, 'created_at' => now()],
    ['name' => 'Express Kilat 6 Jam (Cuci + Setrika)', 'price_per_kg' => 15000, 'duration_hours' => 6, 'description' => 'Layanan super kilat selesai 6 jam, prioritas mesin cuci dan kurir khusus.', 'is_active' => true, 'created_at' => now()],
    ['name' => 'Cuci Kering Lipat Saja', 'price_per_kg' => 6000, 'duration_hours' => 24, 'description' => 'Cucian wangi dan dilipat rapi tanpa setrika uap.', 'is_active' => true, 'created_at' => now()],
    ['name' => 'Bed Cover & Selimut Tebal', 'price_per_kg' => 25000, 'duration_hours' => 48, 'description' => 'Pembersihan mendalam serat kain tebal anti-tungau & kuman.', 'is_active' => true, 'created_at' => now()],
    ['name' => 'Sepatu & Tas Premium Treatment', 'price_per_kg' => 35000, 'duration_hours' => 72, 'description' => 'Deep cleaning, unyellowing, dan proteksi anti air.', 'is_active' => true, 'created_at' => now()],
]);
```

---

## 4. Konfigurasi CSRF Exception untuk Midtrans Callback

Pada Laravel 11+, buka `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/midtrans-callback',
    ]);
})
```

Pada Laravel 10 kebawah, buka `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'api/midtrans-callback',
];
```

---

## 5. Menjalankan Server Lokal & Uji Webhook dengan Ngrok

1. Jalankan server Laravel:
   ```bash
   php artisan serve --port=8000
   ```

2. Buat terowongan HTTPS dengan Ngrok untuk menerima webhook dari Midtrans Sandbox:
   ```bash
   ngrok http 8000
   ```

3. Masuk ke Dashboard Midtrans Sandbox:
   - Menu: **Settings** > **Configuration**
   - **Payment Notification URL**: `https://your-ngrok-subdomain.ngrok-free.app/api/midtrans-callback`
   - Klik **Update**.

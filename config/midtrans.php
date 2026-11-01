<?php

/**
 * Lokasi file: config/midtrans.php
 * Konfigurasi Midtrans Payment Gateway untuk CleanCong Laundry System
 */

return [
    'server_key' => env('MIDTRANS_SERVER_KEY', 'SB-Mid-server-YourSandboxServerKeyHere'),
    'client_key' => env('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-YourSandboxClientKeyHere'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds' => env('MIDTRANS_IS_3DS', true),
];

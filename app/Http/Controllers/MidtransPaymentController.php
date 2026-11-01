<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;

/**
 * Lokasi file: app/Http/Controllers/MidtransPaymentController.php
 * Controller untuk mengelola pembuatan token Snap Midtrans & penanganan Webhook Callback.
 */
class MidtransPaymentController extends Controller
{
    public function __construct()
    {
        // Inisialisasi kredensial Midtrans dari config/midtrans.php
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = (bool) config('midtrans.is_production');
        Config::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        Config::$is3ds = (bool) config('midtrans.is_3ds', true);
    }

    /**
     * Membuat Order baru sekaligus menggenerasi Snap Token dari Midtrans.
     * Endpoint: POST /api/orders/checkout
     */
    public function createSnapToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'pickup_address' => 'required|string|max:1000',
            'estimated_weight' => 'required|numeric|min:0.5|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user() ?? auth()->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Silakan login terlebih dahulu untuk membuat pesanan laundry.'
            ], 401);
        }

        $service = Service::findOrFail($validated['service_id']);
        $totalWeight = (float) $validated['estimated_weight'];
        $totalPrice = round($totalWeight * (float) $service->price_per_kg, 2);

        DB::beginTransaction();
        try {
            // 1. Simpan pesanan ke database MySQL dengan status awal 'unpaid'
            $order = Order::create([
                'user_id' => $user->id,
                'courier_id' => null,
                'service_id' => $service->id,
                'total_weight' => $totalWeight,
                'total_price' => $totalPrice,
                'status' => Order::STATUS_JEMPUT,
                'payment_status' => Order::PAYMENT_UNPAID,
                'pickup_address' => $validated['pickup_address'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // 2. Format order ID unik untuk Midtrans (Format: CC-{order_id}-{timestamp})
            $midtransOrderId = 'CC-' . $order->id . '-' . time();

            // 3. Susun parameter payload Snap Midtrans
            $payload = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => (int) $totalPrice,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '081234567890',
                    'billing_address' => [
                        'first_name' => $user->name,
                        'address' => $order->pickup_address,
                    ],
                    'shipping_address' => [
                        'first_name' => $user->name,
                        'address' => $order->pickup_address,
                    ],
                ],
                'item_details' => [
                    [
                        'id' => 'SRV-' . $service->id,
                        'price' => (int) round($service->price_per_kg),
                        'quantity' => (float) $totalWeight,
                        'name' => substr($service->name . ' (' . $totalWeight . ' kg)', 0, 50),
                    ]
                ],
                'callbacks' => [
                    'finish' => url('/orders/' . $order->id . '/success'),
                ]
            ];

            // 4. Panggil API Midtrans Snap untuk mendapatkan snap_token
            $snapToken = Snap::getSnapToken($payload);

            // 5. Simpan snap_token ke record order
            $order->update(['snap_token' => $snapToken]);

            // 6. Catat log riwayat order pertama kali
            OrderLog::create([
                'order_id' => $order->id,
                'status_note' => 'Pesanan berhasil dibuat. Menunggu pembayaran melalui Midtrans Snap Gateway.',
                'updated_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Snap Token berhasil dibuat.',
                'data' => [
                    'order_id' => $order->id,
                    'midtrans_order_id' => $midtransOrderId,
                    'snap_token' => $snapToken,
                    'total_price' => $totalPrice,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Midtrans Snap Generation Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat transaksi Midtrans: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menangani Webhook / HTTP POST Callback dari Midtrans
     * Endpoint: POST /api/midtrans-callback
     * Note: Daftarkan route ini di bootstrap/app.php atau middleware verifyCsrfToken $except.
     */
    public function handleCallback(Request $request): JsonResponse
    {
        // Tangkap seluruh request body JSON dari Midtrans
        $notif = $request->all();

        $orderIdParam = $notif['order_id'] ?? null;
        $statusCode = $notif['status_code'] ?? null;
        $grossAmount = $notif['gross_amount'] ?? null;
        $signatureKey = $notif['signature_key'] ?? null;
        $transactionStatus = $notif['transaction_status'] ?? null;
        $fraudStatus = $notif['fraud_status'] ?? null;
        $paymentType = $notif['payment_type'] ?? 'midtrans';

        Log::info('Midtrans Webhook Received:', $notif);

        // 1. Verifikasi Signature Key Midtrans (SHA512)
        // Formula: SHA512(order_id + status_code + gross_amount + ServerKey)
        $serverKey = config('midtrans.server_key');
        $expectedSignature = hash('sha512', $orderIdParam . $statusCode . $grossAmount . $serverKey);

        if ($signatureKey !== $expectedSignature) {
            Log::warning('Midtrans Invalid Signature Key', [
                'received' => $signatureKey,
                'expected' => $expectedSignature
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Signature key tidak valid.'
            ], 403);
        }

        // 2. Ekstrak Primary Key order_id dari format 'CC-{id}-{timestamp}'
        $parts = explode('-', $orderIdParam);
        $orderDbId = isset($parts[1]) ? (int)$parts[1] : null;

        $order = Order::find($orderDbId);
        if (!$order) {
            Log::error('Midtrans Callback: Order ID tidak ditemukan di DB: ' . $orderIdParam);
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan tidak ditemukan.'
            ], 404);
        }

        // 3. Logika Pembaruan Status Berdasarkan Status Transaksi Midtrans
        $oldPaymentStatus = $order->payment_status;

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $order->payment_status = Order::PAYMENT_UNPAID;
            } elseif ($fraudStatus == 'accept') {
                $order->payment_status = Order::PAYMENT_PAID;
            }
        } elseif ($transactionStatus == 'settlement') {
            // Pembayaran Berhasil (e-Wallet, QRIS, Bank Transfer VA)
            $order->payment_status = Order::PAYMENT_PAID;
        } elseif ($transactionStatus == 'pending') {
            // Menunggu pembayaran
            $order->payment_status = Order::PAYMENT_UNPAID;
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            // Kadaluarsa atau dibatalkan
            $order->payment_status = Order::PAYMENT_EXPIRED;
        }

        $order->save();

        // 4. Jika baru saja dibayar (paid), update status log
        if ($oldPaymentStatus !== Order::PAYMENT_PAID && $order->payment_status === Order::PAYMENT_PAID) {
            // Pastikan status operasional siap untuk penjemputan
            $order->update(['status' => Order::STATUS_JEMPUT]);

            OrderLog::create([
                'order_id' => $order->id,
                'status_note' => "Pembayaran sebesar Rp " . number_format($order->total_price, 0, ',', '.') . 
                                 " BERHASIL via {$paymentType} (Midtrans). Menunggu penjemputan oleh kurir.",
                'updated_by' => null, // Sistem automatisasi
            ]);
        } elseif ($order->payment_status === Order::PAYMENT_EXPIRED) {
            OrderLog::create([
                'order_id' => $order->id,
                'status_note' => "Transaksi pembayaran Midtrans telah dibatalkan atau kedaluwarsa ({$transactionStatus}).",
                'updated_by' => null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi Midtrans berhasil diproses.',
            'order_id' => $order->id,
            'payment_status' => $order->payment_status
        ], 200);
    }
}

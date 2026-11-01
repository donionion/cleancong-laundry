<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLog;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lokasi file: app/Http/Controllers/OrderController.php
 * Controller untuk alur operasional CleanCong: Pelanggan, Kurir, dan Staf Workshop.
 */
class OrderController extends Controller
{
    /**
     * Menampilkan daftar pesanan berdasarkan role pengguna.
     * Endpoint: GET /api/orders
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Order::with(['service', 'courier:id,name,phone', 'user:id,name,phone', 'logs']);

        if ($user->isCustomer()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isCourier()) {
            // Kurir dapat melihat pesanan yang belum diambil kurir ATAU yang ditugaskan padanya
            $query->where(function ($q) use ($user) {
                $q->whereNull('courier_id')
                  ->orWhere('courier_id', $user->id);
            });
        }
        // Staf dapat melihat semua pesanan

        $orders = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    /**
     * Pelacakan Resi Publik / Detail Pesanan Real-time.
     * Endpoint: GET /api/orders/{id}
     */
    public function show(string $id): JsonResponse
    {
        $order = Order::with(['service', 'courier:id,name,phone', 'user:id,name,phone', 'logs.updater:id,name,role'])
                      ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $order,
        ]);
    }

    /**
     * Fitur Khusus Kurir: Verifikasi Berat Aktual di Lokasi Pelanggan.
     * Sesuai PLAN.md: "Melakukan verifikasi berat/jumlah pakaian aktual di lokasi pelanggan."
     * Endpoint: POST /api/orders/{id}/verify-weight
     */
    public function verifyWeight(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'actual_weight' => 'required|numeric|min:0.5|max:100',
            'courier_notes' => 'nullable|string|max:500',
        ]);

        $order = Order::with('service')->findOrFail($id);
        $user = $request->user();

        $actualWeight = (float) $request->actual_weight;
        $oldWeight = (float) $order->total_weight;
        $newPrice = round($actualWeight * (float) $order->service->price_per_kg, 2);

        DB::beginTransaction();
        try {
            $order->update([
                'courier_id' => $user ? $user->id : $order->courier_id,
                'total_weight' => $actualWeight,
                'total_price' => $newPrice,
                'status' => Order::STATUS_DIPROSES,
            ]);

            $courierName = $user ? $user->name : 'Kurir CleanCong';
            $logMessage = "Kurir {$courierName} telah memverifikasi berat cucian aktual di lokasi: {$actualWeight} kg " .
                          "(Semula estimasi: {$oldWeight} kg). Total tagihan disesuaikan menjadi Rp " . number_format($newPrice, 0, ',', '.') . 
                          ". Cucian dibawa ke workshop untuk pencucian.";

            if ($request->filled('courier_notes')) {
                $logMessage .= " [Catatan Kurir: " . $request->courier_notes . "]";
            }

            OrderLog::create([
                'order_id' => $order->id,
                'status_note' => $logMessage,
                'updated_by' => $user ? $user->id : null,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berat aktual berhasil diverifikasi dan harga telah diperbarui.',
                'data' => $order->fresh(['service', 'logs']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal verifikasi berat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fitur Staf Workshop: Update status pengerjaan (Cuci, Kering, Setrika, QC, Siap Diantar, Selesai).
     * Endpoint: POST /api/orders/{id}/update-status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:Jemput,Diproses,Siap Diantar,Selesai',
            'status_note' => 'required|string|max:1000',
        ]);

        $order = Order::findOrFail($id);
        $user = $request->user();

        $order->status = $request->status;
        $order->save();

        OrderLog::create([
            'order_id' => $order->id,
            'status_note' => $request->status_note,
            'updated_by' => $user ? $user->id : null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status pesanan berhasil diperbarui.',
            'data' => $order->load(['logs.updater:id,name,role']),
        ]);
    }
}

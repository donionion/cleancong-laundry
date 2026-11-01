# PLAN.md - CleanCong Online Laundry System

## 1. Project Overview
- **Project Name:** CleanCong (Online Laundry Service with Pickup & Delivery)
- **Backend Framework:** Laravel (PHP)
- **Frontend Layer:** Blade Templating + Tailwind CSS / JavaScript (Interaktif untuk Demo Iklan)
- **Database Engine:** MySQL Database
- **Payment Gateway:** Midtrans / Xendit (Sandbox API Mode)
- **AI Integration:** Google AI Studio (Gemini API) untuk fitur asisten rekomendasi / layanan pelanggan

---

## 2. User Roles & Key Features
1. **Pelanggan (Customer):**
   - Pemesanan layanan laundry (kiloan/express).
   - Input titik lokasi penjemputan.
   - Melakukan pembayaran via Payment Gateway (e-Wallet/Bank Transfer).
   - Melacak status pesanan secara real-time.

2. **Kurir (Driver):**
   - Menerima notifikasi order penjemputan & pengantaran.
   - Melakukan verifikasi berat/jumlah pakaian aktual di lokasi pelanggan.

3. **Staf Operasional (Admin/Workshop):**
   - Mengelola proses pencucian, pengeringan, setrika, dan Quality Control (QC).
   - Memperbarui status pengerjaan pesanan.

---

## 3. Database Schema (MySQL Architecture)
- **`users`**: `id`, `name`, `email`, `password`, `role` (customer, courier, staff), `phone`, `created_at`
- **`services`**: `id`, `name`, `price_per_kg`, `duration_hours`, `created_at`
- **`orders`**: `id`, `user_id`, `courier_id`, `service_id`, `total_weight`, `total_price`, `status` (Jemput, Diproses, Siap Diantar, Selesai), `payment_status` (unpaid, paid, expired), `snap_token`, `pickup_address`, `created_at`
- **`order_logs`**: `id`, `order_id`, `status_note`, `updated_by`, `created_at`

---

## 4. Payment Gateway Integration Architecture
1. **Order Creation:** Pelanggan membuat pesanan melalui web/aplikasi CleanCong.
2. **Token Generation:** Controller Laravel memanggil API Midtrans Snap untuk menggenerasi `snap_token` berdasarkan parameter `order_id` dan `total_price`.
3. **Checkout Process:** Halaman frontend menampilkan pop-up Snap Payment Midtrans.
4. **Webhook / Notification Handling:**
   - Midtrans mengirimkan HTTP POST Callback ke endpoint Laravel `/api/midtrans-callback`.
   - Controller melakukan verifikasi signature key.
   - Status pesanan pada database MySQL diubah menjadi `paid` secara otomatis.

---

## 5. Development Roadmap
- [x] Perancangan Model POAC & BPMN Process Logic
- [x] Penyusunan Naskah Video Iklan 5 Menit
- [ ] Setup Framework Laravel & Database Migration MySQL
- [ ] Implementasi Autentikasi & Multi-Role User
- [ ] Integrasi Midtrans Payment Gateway API (Sandbox)
- [ ] Pembangunan Landing Page Interaktif (untuk Screen Recording Video Iklan)
- [ ] Pengujian End-to-End System & Video Editing   
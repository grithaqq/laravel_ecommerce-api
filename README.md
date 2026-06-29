# E-Commerce API (Laravel 11 & JWT)

Proyek ini adalah sistem backend API untuk E-Commerce sederhana. API ini dibangun menggunakan Laravel 11 dan dilindungi menggunakan autentikasi JSON Web Token (JWT).

## Fitur Utama
1. **Sistem Autentikasi** (Login, Logout, Profil) berbasis JWT.
2. **Katalog Produk** (CRUD) yang dilengkapi dengan manajemen stok. Akses *Customer* dibatasi hanya untuk melihat katalog, sementara *Admin* memiliki akses penuh.
3. **Sistem Checkout / Transaksi** (Database Transaction). Memungkinkan *Customer* untuk memesan barang, yang secara otomatis akan memvalidasi dan mengurangi stok produk.
4. **Manajemen Order** (Ubah status pesanan ke *paid* atau *shipped* sesuai Hak Akses).

## Cara Instalasi
1. Clone repositori ini:
   ```bash
   git clone https://github.com/grithaqq/laravel_ecommerce-api.git
   cd laravel_ecommerce-api
   ```
2. Install dependensi Composer:
   ```bash
   composer install
   ```
3. Ubah `.env.example` menjadi `.env` dan atur koneksi Database Anda (Ganti `SESSION_DRIVER` ke `file`).
4. Generate App Key dan JWT Secret:
   ```bash
   php artisan key:generate
   php artisan jwt:secret
   ```
5. Migrasi dan Seed database (Akun Dummy: `admin@example.com` & `customer@example.com`, password: `admin123` / `customer123`):
   ```bash
   php artisan migrate:fresh --seed
   ```
6. Jalankan server lokal:
   ```bash
   php artisan serve
   ```

## 🧪 Automated Testing
Proyek ini telah dilengkapi dengan *Feature Tests* menyeluruh untuk endpoint *Auth*, *Products*, dan *Orders*. Pengujian berjalan di dalam memori terisolasi (menggunakan SQLite `memory`), sehingga **tidak akan menghapus atau merusak data MySQL utama Anda**.

Untuk menjalankan seluruh skenario pengujian, gunakan perintah berikut di terminal:
```bash
php artisan test
```

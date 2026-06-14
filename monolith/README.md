# Zenith

Zenith adalah aplikasi manajemen keuangan berbasis Laravel untuk membantu pengguna mencatat pemasukan, pengeluaran, transfer, utang, dan melihat laporan keuangan.

## Fitur Utama

- Autentikasi pengguna (register, login, logout, reset password, Google OAuth)
- Dashboard ringkasan keuangan
- Manajemen rekening
- Manajemen pemasukan dan pengeluaran beserta kategori
- Transfer antar rekening
- Manajemen utang dan riwayat pembayaran
- Laporan keuangan
- Notifikasi pengguna
- Panel admin (manajemen user, notifikasi, kategori default, tips, backup, activity log, maintenance)
- Help / live chat antara user dan admin

## Teknologi

- Laravel 9 (PHP ^8.0.2)
- Vite
- MySQL

## Menjalankan Proyek (Local Development)

1. Clone repository
2. Install dependency backend:
   ```bash
   composer install
   ```
3. Install dependency frontend:
   ```bash
   npm install
   ```
4. Salin environment file:
   ```bash
   cp .env.example .env
   ```
5. Generate app key:
   ```bash
   php artisan key:generate
   ```
6. Atur konfigurasi database pada `.env`, lalu jalankan migrasi:
   ```bash
   php artisan migrate
   ```
7. Build aset frontend:
   ```bash
   npm run build
   ```
8. Jalankan server Laravel:
   ```bash
   php artisan serve
   ```

## Dokumentasi Tambahan

- [QUICK_START.md](QUICK_START.md)
- [DOCUMENTATION_INDEX.md](DOCUMENTATION_INDEX.md)
- [DEPLOYMENT_README.md](DEPLOYMENT_README.md)
- [DEPLOYMENT_CASAOS.md](DEPLOYMENT_CASAOS.md)
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

## Lisensi

Proyek ini menggunakan lisensi [MIT](https://opensource.org/licenses/MIT).

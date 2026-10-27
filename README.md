# Perpustakaan Digital — Laravel + Inertia + Vue 3
> **⚠️ DISCLAIMER — KERANGKA MENTAH**
>
> **Perpustakaan Digital** ini adalah **kerangka (skeleton) versi pengembangan**, **bukan** sistem yang siap produksi atau siap dipakai langsung di perpustakaan / institusi.
>
> - Harus dijalankan mengikuti petunjuk di README.
> - Sebaiknya ditinjau oleh pustakawan, admin perpustakaan, atau IT institusi.
> - Kesesuaian fitur (koleksi, peminjaman, keanggotaan, denda, laporan, API) **bergantung kebutuhan dan evaluasi klien**.
> - Keamanan (auth, data anggota, konfigurasi email/API) **belum** diklaim cukup untuk lingkungan produksi.
>
> Saya sebagai developer hanya membuat dan mengembangkan **kerangka dasar**.  
> Sesuai atau tidaknya, aman atau belum — **penilaian ada pada pengguna / klien**.

Sistem manajemen perpustakaan full-stack modern untuk portfolio.

## Perbaikan audit terbaru
- ✅ **ArgumentCountError** `BorrowingService::approve()` — controller sekarang mengirim 4 argumen (`$borrowing`, `$staff`, `$days`, `$note`)
- ✅ Kunci pengaturan diselaraskan (`max_borrow_days` / `fine_max` konsisten dengan service & model)
- ✅ `BorrowingResource` / `BookResource` — cover URL & null-safe `due_date`
- ✅ `lateDays()` aman bila `due_date` null
- ✅ ShowcaseSeeder dilengkapi **cover buku** (unduh Open Library + fallback generate GD)

## Fitur
- Auth OTP (Gmail API) · Argon2id · RBAC
- Katalog + pinjam · Rating/ulasan · Notifikasi
- Panel staf: buku, peminjaman, laporan (Chart.js), label QR, moderasi ulasan
- Super Admin: user, pengaturan, log aktivitas, backup DB
- UI: Three.js, Lucide, dark mode, komponen shadcn-style

## Instalasi
```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# isi PostgreSQL di .env
php artisan perpus:install
php artisan storage:link
php artisan db:seed --class=ShowcaseSeeder   # data demo + cover
php artisan serve
npm run dev
```

### Akun demo (password semua: `Demo@12345`)
| Username | Peran |
|----------|--------|
| `kepala` | Super Admin |
| `sari.pustaka` / `andi.pustaka` | Pustakawan |
| (lihat seeder) | Anggota |

### Cover buku
Saat `ShowcaseSeeder` jalan, cover diunduh dari Open Library (Naruto, One Piece, Solo Leveling, Atomic Habits, dll). Bila gagal, cover digenerate otomatis (GD). File di `storage/app/public/covers/` — pastikan `php artisan storage:link` sudah dijalankan.

### Gmail API
Isi credentials atau env `GMAIL_*`. Tes: `php artisan perpus:mail-test email@tujuan.com`.  
Local + debug → OTP masuk `storage/logs/laravel.log`.

### Reset data demo
```bash
php artisan migrate:fresh
php artisan db:seed --class=LibrarySeeder
php artisan db:seed --class=ShowcaseSeeder
# atau buat superadmin baru: php artisan perpus:install --fresh
```

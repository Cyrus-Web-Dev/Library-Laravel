<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder default — hanya data dasar wajib (genre, tipe buku, pengaturan sistem).
 *
 * Untuk data contoh yang realistis (buku, anggota, riwayat peminjaman, dst — cocok
 * untuk demo atau uji coba tampilan), jalankan terpisah:
 *   php artisan db:seed --class=ShowcaseSeeder
 * Lihat PANDUAN.txt bagian 3 untuk detailnya.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LibrarySeeder::class);
    }
}

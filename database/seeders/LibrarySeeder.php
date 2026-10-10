<?php

namespace Database\Seeders;

use App\Models\BookType;
use App\Models\Genre;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/** Data dasar yang dibutuhkan agar aplikasi bisa dipakai. Aman dijalankan berulang kali. */
class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Fiksi', 'Non-Fiksi', 'Sains', 'Teknologi', 'Sejarah', 'Biografi',
            'Pendidikan', 'Agama', 'Komik', 'Anak-Anak', 'Bisnis', 'Psikologi',
        ] as $name) {
            Genre::firstOrCreate(['name' => $name]);
        }

        foreach ([
            'Buku Pelajaran', 'Novel', 'Manga', 'Manhwa', 'Manhua', 'Dongeng',
            'Referensi', 'Jurnal', 'Ensiklopedia', 'Kamus', 'Majalah', 'Skripsi / Tesis',
        ] as $name) {
            BookType::firstOrCreate(['name' => $name]);
        }

        $defaults = [
            'library_name' => 'Perpustakaan Nusantara',
            'library_address' => '',
            'library_phone' => '',
            'library_email' => '',
            'max_borrow_days' => '7',
            'max_books_per_user' => '3',
            'max_extend' => '1',
            'extend_days' => '7',
            'fine_per_day' => '1000',
            'fine_max' => '20000',
            'fine_lost' => '50000',
            'block_if_fine' => '1',
            'registration_open' => '1',
            'login_otp' => '1',
            'qr_base_url' => '',
        ];
        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['setting_key' => $key], ['setting_value' => $value]);
        }
    }
}

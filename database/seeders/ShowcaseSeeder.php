<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookRequest;
use App\Models\Borrowing;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\StaffApplication;
use App\Models\User;
use App\Services\BorrowingService;
use App\Services\LibrarySettings;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DATA SHOWCASE (demo) untuk Perpustakaan Digital.
 *
 *   php artisan db:seed --class=ShowcaseSeeder
 *
 * Peminjaman dibuat dengan MENJALANKAN ALUR ASLI aplikasi (BorrowingService), bukan
 * insert mentah — jadi stok buku, status, dan notifikasi semuanya konsisten persis
 * seperti pemakaian sungguhan. Tanggal dimundurkan bertahap (sampai ±14 bulan lalu
 * untuk keanggotaan, ±2 bulan untuk peminjaman) supaya riwayatnya terlihat natural.
 *
 * Yang terisi:
 *   1. Pengguna       -> 1 Super Admin, 2 Pustakawan, 16 Anggota (siswa/mahasiswa/guru/umum)
 *   2. Katalog buku   -> 32 judul lintas semua genre & tipe, lengkap lokasi rak & stok
 *   3. Peminjaman     -> ~26 transaksi: aktif, terlambat (denda belum/sudah lunas),
 *                        dikembalikan tepat waktu, hilang, menunggu persetujuan,
 *                        ditolak, dibatalkan, menunggu konfirmasi pengembalian, diperpanjang
 *   4. Ulasan & favorit -> bintang + komentar, dan buku favorit per anggota
 *   5. Request buku   -> usulan judul baru (menunggu, disetujui, ditolak, terpenuhi)
 *   6. Pengajuan staf -> satu disetujui (anggota ini sungguhan naik jadi pustakawan),
 *                        satu menunggu, satu ditolak
 *
 * Aman dijalankan ulang: dicek lewat akun 'kepala' — bila sudah ada, seluruh proses
 * showcase dilewati (tidak membuat duplikat).
 */
class ShowcaseSeeder extends Seeder
{
    private const PASSWORD = 'Demo@12345';

    private BorrowingService $service;

    /** @var array<string,User> */
    private array $staff = [];

    /** @var array<string,User> */
    private array $members = [];

    /** @var array<string,Book> */
    private array $books = [];

    public function run(): void
    {
        $this->call(LibrarySeeder::class);
        $this->service = app(BorrowingService::class);

        if (User::where('username', 'kepala')->exists()) {
            $this->command?->warn('Data showcase sudah pernah dibuat (akun "kepala" ditemukan) — dilewati.');

            return;
        }

        $this->settings();
        $this->staff = $this->seedStaff();
        $this->members = $this->seedMembers();
        $this->books = $this->seedBooks();
        $this->seedBorrowings();
        $this->seedRatingsAndFavorites();
        $this->seedBookRequests();
        $this->seedStaffApplication();

        $this->command?->info('Data showcase Perpustakaan Digital selesai dibuat.');
        $this->command?->table(['Peran', 'Username', 'Kata sandi'], [
            ['Super Admin (Kepala Pustaka)', 'kepala', self::PASSWORD],
            ['Pustakawan', 'pustakawan1', self::PASSWORD],
            ['Pustakawan (dari pengajuan anggota)', 'rizky.hidayat', self::PASSWORD],
            ['Anggota — Mahasiswa', 'budi.s', self::PASSWORD],
            ['Anggota — Siswa', 'sari.dewi', self::PASSWORD],
            ['Anggota — Guru', 'rina.marlina', self::PASSWORD],
            ['Anggota — Umum', 'agus.pratama', self::PASSWORD],
        ]);
    }

    /* ------------------------------------------------------------ dasar */

    private function settings(): void
    {
        foreach ([
            'library_name' => 'Perpustakaan Nusantara',
            'library_address' => 'Jl. Pemuda No. 12, Padang Barat, Kota Padang, Sumatera Barat',
            'library_phone' => '0751-891234',
            'library_email' => 'info@perpustakaan-padang.test',
        ] as $key => $value) {
            LibrarySettings::set($key, $value);
        }
    }

    /** @return array<string,User> */
    private function seedStaff(): array
    {
        $rows = [
            // username => [nama, email, telepon, bulan_bergabung_lalu]
            'kepala' => ['Dr. Hendra Gunawan, M.I.Kom.', 'hendra.kepala@perpustakaan.test', '0812-7700-0001', 30],
            'pustakawan1' => ['Siti Rahmawati, A.Md.', 'siti.pustakawan@perpustakaan.test', '0812-7700-0002', 22],
        ];

        $out = [];
        foreach ($rows as $username => [$name, $email, $phone, $monthsAgo]) {
            $user = User::create([
                'username' => $username, 'full_name' => $name, 'email' => $email, 'phone' => $phone,
                'role' => $username === 'kepala' ? User::ROLE_SUPERADMIN : User::ROLE_PUSTAKAWAN,
                'member_type' => 'Umum', 'institution' => 'Perpustakaan Nusantara', 'status' => 'active',
                'password' => Hash::make(self::PASSWORD), 'email_verified_at' => now(),
            ]);
            $joined = now()->subMonths($monthsAgo)->startOfMonth()->addDays(3);
            $user->forceFill(['created_at' => $joined, 'updated_at' => $joined])->save();
            $out[$username] = $user;
        }

        return $out;
    }

    /** username => [nama, jenis, institusi, kode_anggota, telepon, bulan_bergabung_lalu, bio?, status] */
    private function memberRows(): array
    {
        return [
            'budi.s' => ['Budi Santoso', 'Mahasiswa', 'Universitas Andalas', 'NIM-2201123', '0812-5501-0001', 11, 'Suka novel sejarah dan fiksi ilmiah.', 'active'],
            'sari.dewi' => ['Sari Dewi Lestari', 'Siswa', 'SMA Negeri 1 Padang', 'NIS-23145', '0812-5501-0002', 9, null, 'active'],
            'rina.marlina' => ['Rina Marlina, S.Pd.', 'Guru', 'SMA Negeri 1 Padang', 'NIP-198502142010', '0812-5501-0003', 13, 'Guru Bahasa Indonesia, suka mengoleksi buku puisi.', 'active'],
            'agus.pratama' => ['Agus Pratama', 'Umum', null, null, '0812-5501-0004', 7, null, 'active'],
            'dewi.anggraini' => ['Dewi Anggraini', 'Mahasiswa', 'Universitas Negeri Padang', 'NIM-1901045', '0812-5501-0005', 10, null, 'active'],
            'fajar.n' => ['Fajar Nugroho', 'Siswa', 'SMA Negeri 3 Padang', 'NIS-22987', '0812-5501-0006', 5, 'Suka komik dan manga action.', 'active'],
            'maya.putri' => ['Maya Putri Utami, S.Pd.', 'Guru', 'SMP Negeri 7 Padang', 'NIP-199001202015', '0812-5501-0007', 12, null, 'active'],
            'hendra.w' => ['Hendra Wijaya', 'Umum', null, null, '0812-5501-0008', 4, null, 'active'],
            'rizky.hidayat' => ['Rizky Hidayat', 'Mahasiswa', 'Universitas Andalas', 'NIM-2105078', '0812-5501-0009', 8, 'Mahasiswa Ilmu Perpustakaan, aktif jadi relawan baca.', 'active'],
            'nadia.oktavia' => ['Nadia Oktavia', 'Siswa', 'SMA Negeri 1 Padang', 'NIS-23201', '0812-5501-0010', 3, null, 'active'],
            'bambang.hartono' => ['Bambang Hartono', 'Umum', null, null, '0812-5501-0011', 14, 'Pensiunan guru, pelanggan lama perpustakaan.', 'active'],
            'putri.ramadhani' => ['Putri Ramadhani', 'Mahasiswa', 'Universitas Bung Hatta', 'NIM-2210034', '0812-5501-0012', 2, null, 'active'],
            'yusuf.alfarizi' => ['Yusuf Alfarizi', 'Siswa', 'SMA Negeri 2 Padang', 'NIS-22765', '0812-5501-0013', 6, null, 'active'],
            'indah.permata' => ['Indah Permata Sari, S.Pd.', 'Guru', 'SMA Negeri 2 Padang', 'NIP-198811052012', '0812-5501-0014', 11, null, 'active'],
            'taufik.ismail' => ['Taufik Ismail', 'Umum', null, null, '0812-5501-0015', 1, null, 'active'],
            'anita.wulandari' => ['Anita Wulandari', 'Mahasiswa', 'Universitas Andalas', 'NIM-2305012', '0812-5501-0016', 0, 'Baru gabung, masih menjelajahi katalog.', 'active'],
            // satu akun lama yang sudah tidak aktif, supaya status "Nonaktif" ikut terwakili di data
            'maria.ulfa' => ['Maria Ulfa', 'Mahasiswa', 'Universitas Andalas', 'NIM-1805099', '0812-5501-0017', 24, 'Sudah lulus, jarang aktif lagi.', 'inactive'],
        ];
    }

    /** @return array<string,User> */
    private function seedMembers(): array
    {
        $out = [];
        foreach ($this->memberRows() as $username => [$name, $type, $inst, $code, $phone, $monthsAgo, $bio, $status]) {
            $local = str_replace('.', '', $username);
            $user = User::create([
                'username' => $username, 'full_name' => $name, 'email' => $local.'@contoh.test',
                'role' => User::ROLE_ANGGOTA, 'member_type' => $type, 'institution' => $inst, 'member_code' => $code,
                'phone' => $phone, 'bio' => $bio, 'status' => $status,
                'password' => Hash::make(self::PASSWORD), 'email_verified_at' => now(),
            ]);
            $joined = $monthsAgo === 0 ? now()->subDays(12) : now()->subMonths($monthsAgo)->subDays($monthsAgo % 5);
            $user->forceFill(['created_at' => $joined, 'updated_at' => $joined])->save();
            $out[$username] = $user;
        }

        return $out;
    }

    /* ------------------------------------------------------- 2. KATALOG */

    /** slug => [judul, penulis, penerbit, tahun, genre, tipe, lokasi, stok, halaman, harga] */
    private function bookRows(): array
    {
        return [
            'laskar-pelangi' => ['Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, 'Fiksi', 'Novel', 'Rak A-01', 4, 529, 75000],
            'bumi' => ['Bumi', 'Tere Liye', 'Gramedia', 2014, 'Fiksi', 'Novel', 'Rak A-02', 3, 440, 89000],
            'laut-bercerita' => ['Laut Bercerita', 'Leila S. Chudori', 'KPG', 2017, 'Fiksi', 'Novel', 'Rak A-03', 3, 379, 98000],
            'cantik-itu-luka' => ['Cantik Itu Luka', 'Eka Kurniawan', 'Gramedia', 2002, 'Fiksi', 'Novel', 'Rak A-04', 2, 470, 95000],
            'bidadari-bermata-bening' => ['Bidadari Bermata Bening', 'Habiburrahman El Shirazy', 'Republika', 2017, 'Fiksi', 'Novel', 'Rak A-05', 3, 350, 82000],
            'filosofi-teras' => ['Filosofi Teras', 'Henry Manampiring', 'Kompas', 2018, 'Psikologi', 'Referensi', 'Rak B-01', 4, 320, 98000],
            'atomic-habits' => ['Atomic Habits', 'James Clear', 'Gramedia', 2019, 'Bisnis', 'Referensi', 'Rak B-02', 4, 320, 108000],
            'sapiens' => ['Sapiens: Riwayat Singkat Umat Manusia', 'Yuval Noah Harari', 'KPG', 2017, 'Sejarah', 'Referensi', 'Rak B-03', 2, 512, 135000],
            'psikologi-populer' => ['Psikologi Populer untuk Pemula', 'Dr. Ratna Wijayanti', 'Erlangga', 2020, 'Psikologi', 'Referensi', 'Rak B-04', 3, 268, 78000],
            'bisnis-model-canvas' => ['Business Model Canvas untuk Pemula', 'Alexander Osterwalder', 'Elex Media', 2016, 'Bisnis', 'Referensi', 'Rak B-05', 2, 288, 92000],
            'clean-code' => ['Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, 'Teknologi', 'Referensi', 'Rak B-06', 2, 464, 350000],
            'sejarah-indonesia' => ['Sejarah Indonesia Modern', 'M.C. Ricklefs', 'Gadjah Mada University Press', 2008, 'Sejarah', 'Referensi', 'Rak B-07', 2, 720, 150000],
            'naruto-1' => ['Naruto Vol. 1', 'Masashi Kishimoto', 'Elex Media', 2003, 'Komik', 'Manga', 'Rak C-01', 3, 192, 30000],
            'one-piece-1' => ['One Piece Vol. 1', 'Eiichiro Oda', 'Elex Media', 1997, 'Komik', 'Manga', 'Rak C-02', 3, 216, 32000],
            'solo-leveling-1' => ['Solo Leveling Vol. 1', 'Chugong', 'D&C Media', 2018, 'Komik', 'Manhwa', 'Rak C-03', 3, 280, 60000],
            'solo-leveling-2' => ['Solo Leveling Vol. 2', 'Chugong', 'D&C Media', 2018, 'Komik', 'Manhwa', 'Rak C-03', 2, 280, 60000],
            'true-beauty-1' => ['True Beauty Vol. 1', 'Yaongyi', 'M&C!', 2020, 'Komik', 'Manhwa', 'Rak C-04', 2, 240, 55000],
            'fenomenal-1' => ['Fenomenal: Kisah Dunia Lain Vol. 1', 'Zhuo Fei Ran', 'Comicoo', 2021, 'Komik', 'Manhua', 'Rak C-05', 2, 200, 48000],
            'dongeng-kancil' => ['Dongeng Si Kancil', 'Tradisional', 'Balai Pustaka', 2015, 'Anak-Anak', 'Dongeng', 'Rak D-01', 5, 80, 35000],
            'dongeng-nusantara' => ['Kumpulan Dongeng Nusantara', 'Tim Penulis', 'Gramedia', 2019, 'Anak-Anak', 'Dongeng', 'Rak D-02', 4, 120, 45000],
            'ensiklopedia-anak' => ['Ensiklopedia Sains untuk Anak', 'Tim Penulis', 'Bhuana Ilmu Populer', 2020, 'Anak-Anak', 'Ensiklopedia', 'Rak D-03', 2, 180, 110000],
            'ensiklopedia-sains' => ['Ensiklopedia Sains Populer', 'Tim Penulis', 'Gramedia', 2019, 'Sains', 'Ensiklopedia', 'Rak E-01', 2, 400, 145000],
            'matematika-12' => ['Matematika Kelas 12', 'Tim Kemdikbud', 'Kemdikbud', 2021, 'Pendidikan', 'Buku Pelajaran', 'Rak F-01', 8, 250, 0],
            'bahasa-indonesia-10' => ['Bahasa Indonesia Kelas 10', 'Tim Kemdikbud', 'Kemdikbud', 2021, 'Pendidikan', 'Buku Pelajaran', 'Rak F-02', 8, 210, 0],
            'kamus-besar' => ['Kamus Besar Bahasa Indonesia', 'Badan Bahasa', 'Balai Pustaka', 2016, 'Pendidikan', 'Kamus', 'Rak F-03', 3, 1600, 185000],
            'kamus-inggris' => ['Kamus Inggris-Indonesia', 'John M. Echols', 'Gramedia', 2014, 'Pendidikan', 'Kamus', 'Rak F-04', 3, 660, 95000],
            'majalah-tempo' => ['Tempo Edisi Khusus Kemerdekaan', 'Redaksi Tempo', 'Tempo Media', 2024, 'Non-Fiksi', 'Majalah', 'Rak G-01', 2, 84, 45000],
            'jurnal-teknologi' => ['Jurnal Teknologi Informasi Vol. 12', 'Himpunan Ilmuwan Komputer', 'HIKOM Press', 2023, 'Teknologi', 'Jurnal', 'Rak G-02', 2, 96, 0],
            'jurnal-pendidikan' => ['Jurnal Pendidikan Indonesia Vol. 8', 'Asosiasi Dosen Pendidikan', 'ADP Press', 2023, 'Pendidikan', 'Jurnal', 'Rak G-03', 2, 88, 0],
            'skripsi-panduan' => ['Panduan Praktis Menulis Skripsi', 'Dr. Suwarno, M.Pd.', 'Deepublish', 2019, 'Pendidikan', 'Skripsi / Tesis', 'Rak G-04', 2, 150, 65000],
            'biografi-bj-habibie' => ['Habibie & Ainun', 'B.J. Habibie', 'THC Mandiri', 2010, 'Biografi', 'Novel', 'Rak H-01', 2, 323, 85000],
            'tafsir-ringkas' => ['Tafsir Ringkas Juz Amma', 'Tim Ulama', 'Gema Insani', 2018, 'Agama', 'Referensi', 'Rak H-02', 3, 210, 55000],
        ];
    }

    /** @return array<string,Book> */
    private function seedBooks(): array
    {
        $genreIds = \App\Models\Genre::pluck('id', 'name');
        $typeIds = \App\Models\BookType::pluck('id', 'name');
        $descriptions = [
            'Fiksi' => 'Kisah yang mengharukan dan sarat makna, ditulis dengan gaya bahasa yang memikat.',
            'Non-Fiksi' => 'Ulasan mendalam berdasarkan fakta dan data terkini.',
            'Sains' => 'Menjelaskan konsep sains secara ringan dan mudah dipahami.',
            'Teknologi' => 'Panduan praktis seputar perkembangan teknologi masa kini.',
            'Sejarah' => 'Menelusuri jejak sejarah dengan riset yang mendalam.',
            'Biografi' => 'Kisah perjalanan hidup seorang tokoh inspiratif.',
            'Pendidikan' => 'Materi pembelajaran yang disusun sesuai kurikulum terbaru.',
            'Agama' => 'Panduan keagamaan yang disusun secara ringkas dan mudah diamalkan.',
            'Komik' => 'Cerita bergambar penuh aksi dan emosi yang disukai segala usia.',
            'Anak-Anak' => 'Cerita ringan dan menghibur, cocok dibacakan untuk anak-anak.',
            'Bisnis' => 'Strategi dan wawasan praktis untuk membangun usaha.',
            'Psikologi' => 'Mengulas sisi psikologi manusia dengan bahasa yang membumi.',
        ];

        $out = [];
        $i = 0;
        $coverDir = storage_path('app/public/covers');
        if (! is_dir($coverDir)) {
            mkdir($coverDir, 0755, true);
        }

        // Mapping slug -> URL cover publik (Open Library / Wikimedia Commons / cover resmi)
        $coverUrls = [
            'naruto-1' => 'https://covers.openlibrary.org/b/id/7222246-L.jpg',
            'one-piece-1' => 'https://covers.openlibrary.org/b/id/10521366-L.jpg',
            'solo-leveling-1' => 'https://covers.openlibrary.org/b/isbn/9781975319274-L.jpg',
            'solo-leveling-2' => 'https://covers.openlibrary.org/b/isbn/9781975319298-L.jpg',
            'atomic-habits' => 'https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg',
            'sapiens' => 'https://covers.openlibrary.org/b/isbn/9780062316097-L.jpg',
            'clean-code' => 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg',
            'filosofi-teras' => 'https://covers.openlibrary.org/b/isbn/9786024246945-L.jpg',
            'bumi' => 'https://covers.openlibrary.org/b/isbn/9786020301136-L.jpg',
            'laut-bercerita' => 'https://covers.openlibrary.org/b/isbn/9786024246938-L.jpg',
            'cantik-itu-luka' => 'https://covers.openlibrary.org/b/isbn/9789792233735-L.jpg',
        ];

        foreach ($this->bookRows() as $slug => [$title, $author, $publisher, $year, $genre, $type, $loc, $stock, $pages, $price]) {
            $i++;
            $isbn = '978-602-'.str_pad((string) (100 + $i), 3, '0', STR_PAD_LEFT).'-'.str_pad((string) ($i * 7 % 900 + 10), 3, '0', STR_PAD_LEFT).'-0';

            $coverPath = null;
            // Coba unduh cover dari URL publik bila tersedia
            if (isset($coverUrls[$slug])) {
                $coverPath = $this->downloadCover($coverUrls[$slug], $slug, $coverDir);
            }
            // Fallback: generate cover sederhana dengan GD
            if (! $coverPath) {
                $coverPath = $this->generateCover($title, $author, $genre, $slug, $coverDir);
            }

            $book = Book::create([
                'title' => $title, 'author' => $author, 'publisher' => $publisher, 'publication_year' => $year,
                'genre_id' => $genreIds[$genre] ?? null, 'type_id' => $typeIds[$type] ?? null,
                'description' => $descriptions[$genre] ?? 'Koleksi pilihan perpustakaan.',
                'location' => $loc, 'total_stock' => $stock, 'available_stock' => $stock,
                'pages' => $pages, 'price' => $price ?: null, 'status' => Book::STATUS_AVAILABLE,
                'isbn' => $isbn,
                'cover_image' => $coverPath,
                'created_by' => $this->staff['kepala']->id,
            ]);
            $added = now()->subMonths(random_int(1, 20))->subDays(random_int(0, 27));
            $book->forceFill(['created_at' => $added, 'updated_at' => $added])->save();
            $out[$slug] = $book;
        }

        return $out;
    }

    /** Unduh cover dari URL publik, simpan di storage/app/public/covers. Return path relatif atau null. */
    private function downloadCover(string $url, string $slug, string $dir): ?string
    {
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 8, 'header' => "User-Agent: PerpustakaanSeeder/1.0\r\n"], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $bin = @file_get_contents($url, false, $ctx);
            if ($bin === false || strlen($bin) < 1000) {
                return null;
            }
            // Deteksi ekstensi sederhana
            $ext = str_contains($url, '.png') ? 'png' : 'jpg';
            $filename = $slug.'.'.$ext;
            file_put_contents($dir.'/'.$filename, $bin);

            return 'covers/'.$filename;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Generate cover JPEG sederhana (judul + penulis) bila unduhan gagal / tidak ada URL. */
    private function generateCover(string $title, string $author, string $genre, string $slug, string $dir): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $w = 300;
        $h = 420;
        $im = imagecreatetruecolor($w, $h);
        // Palet warna per genre
        $palettes = [
            'Komik' => [0x1a1a2e, 0xe94560],
            'Fiksi' => [0x2d3436, 0xfdcb6e],
            'Teknologi' => [0x0f3460, 0x00cec9],
            'Sejarah' => [0x3d2914, 0xd4a574],
            'Psikologi' => [0x2c3e50, 0x9b59b6],
            'Bisnis' => [0x1e3a5f, 0xf39c12],
            'default' => [0x1f4d43, 0xe0a526],
        ];
        [$bgHex, $accentHex] = $palettes[$genre] ?? $palettes['default'];
        $bg = imagecolorallocate($im, ($bgHex >> 16) & 0xFF, ($bgHex >> 8) & 0xFF, $bgHex & 0xFF);
        $accent = imagecolorallocate($im, ($accentHex >> 16) & 0xFF, ($accentHex >> 8) & 0xFF, $accentHex & 0xFF);
        $white = imagecolorallocate($im, 255, 255, 255);
        $muted = imagecolorallocate($im, 200, 200, 200);
        imagefilledrectangle($im, 0, 0, $w, $h, $bg);
        imagefilledrectangle($im, 0, $h - 80, $w, $h, $accent);

        // Judul (wrap manual sederhana)
        $words = explode(' ', $title);
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $test = trim($line.' '.$word);
            if (strlen($test) > 18 && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $test;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }
        $lines = array_slice($lines, 0, 4);
        $y = 120;
        foreach ($lines as $ln) {
            $tw = imagefontwidth(5) * strlen($ln);
            imagestring($im, 5, (int) (($w - $tw) / 2), $y, $ln, $white);
            $y += 22;
        }
        $aw = imagefontwidth(3) * strlen($author);
        imagestring($im, 3, (int) (($w - $aw) / 2), $h - 50, $author, $white);

        $filename = $slug.'.jpg';
        imagejpeg($im, $dir.'/'.$filename, 85);
        imagedestroy($im);

        return 'covers/'.$filename;
    }

    /* ---------------------------------------------------- 3. PEMINJAMAN */

    /** Setujui lalu timpa tanggal pinjam/jatuh tempo supaya riwayatnya tersebar wajar. */
    private function approveBackdated(Borrowing $b, Carbon $borrowDate, Carbon $dueDate, ?string $note = null): Borrowing
    {
        $this->service->approve($b->fresh(), $this->staff['kepala'], 7, $note);
        $b->refresh()->forceFill(['borrow_date' => $borrowDate->toDateString(), 'due_date' => $dueDate->toDateString()])->save();
        $this->backdateLatestNotification($b->user, $borrowDate->copy()->addHours(2));

        return $b->fresh();
    }

    private function backdateLatestNotification(User $user, Carbon $when): void
    {
        Notification::where('user_id', $user->id)->latest('id')->first()?->forceFill(['created_at' => $when])->save();
    }

    private function backdateRequest(Borrowing $b, Carbon $when): void
    {
        $b->forceFill(['created_at' => $when, 'updated_at' => $when])->save();
    }

    private function seedBorrowings(): void
    {
        $m = $this->members;
        $bk = $this->books;
        $staff = $this->staff['kepala'];
        $today = now();

        // --- budi.s -----------------------------------------------------------------
        $b = $this->service->requestBorrow($m['budi.s'], $bk['laskar-pelangi'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(60), $today->copy()->subDays(53));
        $b->forceFill(['return_requested_at' => $today->copy()->subDays(56)])->save();
        $this->service->confirmReturn($b->fresh(), $staff);
        $this->backdateLatestNotification($m['budi.s'], $today->copy()->subDays(55));

        $b = $this->service->requestBorrow($m['budi.s'], $bk['atomic-habits'], 'Mau dibaca untuk tugas kuliah.');
        $this->approveBackdated($b, $today->copy()->subDays(5), $today->copy()->addDays(2));

        $b = $this->service->requestBorrow($m['budi.s'], $bk['fenomenal-1'], null);
        $this->backdateRequest($b, $today->copy()->subDays(4));
        $this->service->reject($b->fresh(), $staff, 'Stok sedang dipesan untuk acara kelas lain, coba ajukan lagi minggu depan.');
        $this->backdateRequest($b->fresh(), $today->copy()->subDays(3));
        $this->backdateLatestNotification($m['budi.s'], $today->copy()->subDays(3));

        // --- sari.dewi (contoh terlambat, denda berjalan belum dibayar) -------------
        $b = $this->service->requestBorrow($m['sari.dewi'], $bk['bumi'], null);
        $this->approveBackdated($b, $today->copy()->subDays(20), $today->copy()->subDays(13));

        // --- rina.marlina -------------------------------------------------------------
        $b = $this->service->requestBorrow($m['rina.marlina'], $bk['filosofi-teras'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(40), $today->copy()->subDays(33));
        $b->forceFill(['return_requested_at' => $today->copy()->subDays(30)])->save();
        $this->service->confirmReturn($b->fresh(), $staff);
        $this->service->payFine($b->fresh(), $staff);
        $this->backdateLatestNotification($m['rina.marlina'], $today->copy()->subDays(30));

        $this->service->requestBorrow($m['rina.marlina'], $bk['sejarah-indonesia'], 'Referensi untuk mengajar semester depan.');

        // --- agus.pratama (contoh buku hilang) ---------------------------------------
        $b = $this->service->requestBorrow($m['agus.pratama'], $bk['naruto-1'], null);
        $this->approveBackdated($b, $today->copy()->subDays(25), $today->copy()->subDays(18));
        $this->service->markLost($b->fresh(), $staff);

        // --- dewi.anggraini -------------------------------------------------------------
        $b = $this->service->requestBorrow($m['dewi.anggraini'], $bk['matematika-12'], null);
        $this->approveBackdated($b, $today->copy()->subDays(6), $today->copy()->addDay());

        $b = $this->service->requestBorrow($m['dewi.anggraini'], $bk['clean-code'], 'Untuk tugas akhir pemrograman.');
        $this->backdateRequest($b, $today->copy()->subDays(2));
        $this->service->reject($b->fresh(), $staff, 'Buku masih dipakai dosen pembimbing, coba ajukan lagi bulan depan.');
        $this->backdateRequest($b->fresh(), $today->copy()->subDay());
        $this->backdateLatestNotification($m['dewi.anggraini'], $today->copy()->subDay());

        $b = $this->service->requestBorrow($m['dewi.anggraini'], $bk['ensiklopedia-anak'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(45), $today->copy()->subDays(38));
        $b->forceFill(['return_requested_at' => $today->copy()->subDays(40)])->save();
        $this->service->confirmReturn($b->fresh(), $staff);
        $this->backdateLatestNotification($m['dewi.anggraini'], $today->copy()->subDays(40));

        // --- fajar.n ----------------------------------------------------------------
        $b = $this->service->requestBorrow($m['fajar.n'], $bk['solo-leveling-1'], null);
        $this->backdateRequest($b, $today->copy()->subDays(3));
        $this->service->cancelRequest($b->fresh());
        $this->backdateRequest($b->fresh(), $today->copy()->subDays(2));

        $b = $this->service->requestBorrow($m['fajar.n'], $bk['dongeng-kancil'], null);
        $this->approveBackdated($b, $today->copy()->subDays(3), $today->copy()->addDays(4));

        // --- maya.putri ----------------------------------------------------------------
        $b = $this->service->requestBorrow($m['maya.putri'], $bk['psikologi-populer'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(10), $today->copy()->addDays(3));
        $this->service->requestReturn($b->fresh());
        $this->backdateLatestNotification($this->staff['kepala'], $today->copy()->subHours(2));

        $b = $this->service->requestBorrow($m['maya.putri'], $bk['kamus-inggris'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(35), $today->copy()->subDays(28));
        $b->forceFill(['return_requested_at' => $today->copy()->subDays(30)])->save();
        $this->service->confirmReturn($b->fresh(), $staff);
        $this->backdateLatestNotification($m['maya.putri'], $today->copy()->subDays(30));

        // --- hendra.w (contoh perpanjangan) ---------------------------------------
        $b = $this->service->requestBorrow($m['hendra.w'], $bk['laut-bercerita'], null);
        $this->approveBackdated($b, $today->copy()->subDays(5), $today->copy()->addDays(2));
        $this->service->extend($b->fresh());
        $this->backdateLatestNotification($m['hendra.w'], $today->copy()->subDay());

        // --- rizky.hidayat (pinjaman bersih dibuat dulu, baru yang nunggak) --------
        $b = $this->service->requestBorrow($m['rizky.hidayat'], $bk['jurnal-teknologi'], null);
        $this->approveBackdated($b, $today->copy()->subDays(4), $today->copy()->addDays(3));

        $b = $this->service->requestBorrow($m['rizky.hidayat'], $bk['ensiklopedia-sains'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(25), $today->copy()->subDays(18));
        $b->forceFill(['return_requested_at' => $today->copy()->subDays(15)])->save();
        $this->service->confirmReturn($b->fresh(), $staff);
        $this->backdateLatestNotification($m['rizky.hidayat'], $today->copy()->subDays(15));

        // --- nadia.oktavia -------------------------------------------------------------
        $this->service->requestBorrow($m['nadia.oktavia'], $bk['solo-leveling-2'], 'Lanjutan dari volume 1 yang sudah saya baca.');

        // --- bambang.hartono -------------------------------------------------------------
        $b = $this->service->requestBorrow($m['bambang.hartono'], $bk['bisnis-model-canvas'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(50), $today->copy()->subDays(43));
        $b->forceFill(['return_requested_at' => $today->copy()->subDays(45)])->save();
        $this->service->confirmReturn($b->fresh(), $staff);
        $this->backdateLatestNotification($m['bambang.hartono'], $today->copy()->subDays(45));

        $b = $this->service->requestBorrow($m['bambang.hartono'], $bk['sapiens'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(70), $today->copy()->subDays(63));
        $b->forceFill(['return_requested_at' => $today->copy()->subDays(65)])->save();
        $this->service->confirmReturn($b->fresh(), $staff);
        $this->backdateLatestNotification($m['bambang.hartono'], $today->copy()->subDays(65));

        // --- putri.ramadhani -------------------------------------------------------------
        $b = $this->service->requestBorrow($m['putri.ramadhani'], $bk['kamus-besar'], null);
        $this->approveBackdated($b, $today->copy()->subDays(2), $today->copy()->addDays(5));

        $this->service->requestBorrow($m['putri.ramadhani'], $bk['one-piece-1'], null);

        // --- yusuf.alfarizi (pinjaman bersih dibuat dulu, baru yang nunggak) -------
        $b = $this->service->requestBorrow($m['yusuf.alfarizi'], $bk['jurnal-pendidikan'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(20), $today->copy()->subDays(13));
        $b->forceFill(['return_requested_at' => $today->copy()->subDays(15)])->save();
        $this->service->confirmReturn($b->fresh(), $staff);
        $this->backdateLatestNotification($m['yusuf.alfarizi'], $today->copy()->subDays(15));

        $b = $this->service->requestBorrow($m['yusuf.alfarizi'], $bk['one-piece-1'], null);
        $this->approveBackdated($b, $today->copy()->subDays(9), $today->copy()->subDays(2));

        // --- indah.permata -------------------------------------------------------------
        $b = $this->service->requestBorrow($m['indah.permata'], $bk['majalah-tempo'], null);
        $b = $this->approveBackdated($b, $today->copy()->subDays(15), $today->copy()->subDays(8));
        $b->forceFill(['return_requested_at' => $today->copy()->subDays(10)])->save();
        $this->service->confirmReturn($b->fresh(), $staff);
        $this->backdateLatestNotification($m['indah.permata'], $today->copy()->subDays(10));

        $this->service->requestBorrow($m['indah.permata'], $bk['skripsi-panduan'], 'Untuk membimbing siswa yang akan menulis karya tulis.');

        // Stok akhirnya dihitung ulang sekali lagi (cadangan, seharusnya sudah akurat lewat service di atas).
        foreach ($bk as $book) {
            $book->syncAvailableStock();
        }
    }

    /* ------------------------------------------------- 4. ULASAN & FAVORIT */

    private function seedRatingsAndFavorites(): void
    {
        $m = $this->members;
        $bk = $this->books;
        $today = now();

        // [anggota, buku, bintang, komentar?, hari_lalu]
        $reviews = [
            ['budi.s', 'laskar-pelangi', 5, 'Mengharukan dan penuh semangat. Bikin semangat belajar lagi, recommended banget!', 54],
            ['rina.marlina', 'filosofi-teras', 5, 'Cocok dijadikan bahan diskusi di kelas. Bahasanya ringan meski membahas filsafat.', 29],
            ['dewi.anggraini', 'ensiklopedia-anak', 4, 'Ilustrasinya bagus, anak-anak suka. Sayang beberapa halaman mulai lusuh.', 39],
            ['bambang.hartono', 'sapiens', 5, 'Buku yang mengubah cara pandang saya tentang sejarah manusia. Sudah saya baca dua kali.', 64],
            ['bambang.hartono', 'bisnis-model-canvas', 4, null, 44],
            ['yusuf.alfarizi', 'jurnal-pendidikan', 4, 'Referensi bagus untuk tugas makalah.', 14],
            ['indah.permata', 'majalah-tempo', 4, 'Liputannya lengkap dan aktual.', 9],
            ['maya.putri', 'kamus-inggris', 5, 'Terjemahannya lengkap, selalu jadi andalan saat menyiapkan materi mengajar.', 29],
            ['rizky.hidayat', 'ensiklopedia-sains', 3, 'Isinya bagus tapi agak berat untuk dibaca santai.', 14],
            ['fajar.n', 'dongeng-kancil', 5, null, 2],
            ['agus.pratama', 'naruto-1', 5, 'Sayang sekali bukunya hilang dari saya, padahal ceritanya seru sekali.', 24],
            ['sari.dewi', 'bumi', 4, 'Serunya kebangetan, susah berhenti baca. Semoga nggak kena denda karena lupa waktu.', 18],
            ['hendra.w', 'laut-bercerita', 5, 'Novel sejarah yang ditulis dengan sangat rapi, bikin merinding di beberapa bagian.', 4],
            ['putri.ramadhani', 'kamus-besar', 4, null, 1],
            ['budi.s', 'atomic-habits', 5, 'Banyak insight kecil yang langsung bisa dipraktikkan sehari-hari.', 4],
            ['indah.permata', 'skripsi-panduan', 4, 'Sangat membantu untuk membimbing siswa menulis karya ilmiah.', 0],
        ];

        foreach ($reviews as [$user, $book, $stars, $comment, $daysAgo]) {
            $rating = Rating::create(['user_id' => $m[$user]->id, 'book_id' => $bk[$book]->id, 'rating' => $stars, 'comment' => $comment, 'is_approved' => true]);
            $when = $today->copy()->subDays($daysAgo);
            $rating->forceFill(['created_at' => $when, 'updated_at' => $when])->save();
        }

        // Favorit: tiap anggota aktif menyimpan beberapa judul.
        $favorites = [
            'budi.s' => ['laskar-pelangi', 'atomic-habits', 'clean-code'],
            'sari.dewi' => ['bumi', 'solo-leveling-2', 'true-beauty-1'],
            'rina.marlina' => ['filosofi-teras', 'sejarah-indonesia'],
            'dewi.anggraini' => ['ensiklopedia-anak', 'matematika-12'],
            'fajar.n' => ['naruto-1', 'one-piece-1', 'solo-leveling-1'],
            'maya.putri' => ['psikologi-populer', 'kamus-inggris'],
            'rizky.hidayat' => ['jurnal-teknologi', 'clean-code'],
            'bambang.hartono' => ['sapiens', 'bisnis-model-canvas', 'biografi-bj-habibie'],
            'putri.ramadhani' => ['kamus-besar', 'one-piece-1'],
            'yusuf.alfarizi' => ['jurnal-pendidikan', 'fenomenal-1'],
            'indah.permata' => ['majalah-tempo', 'skripsi-panduan'],
        ];
        foreach ($favorites as $user => $titles) {
            foreach ($titles as $slug) {
                $m[$user]->favoriteBooks()->syncWithoutDetaching([$bk[$slug]->id => ['created_at' => $today->copy()->subDays(random_int(1, 30))]]);
            }
        }
    }

    /* --------------------------------------------------- 5. REQUEST BUKU */

    private function seedBookRequests(): void
    {
        $m = $this->members;
        $staff = $this->staff['kepala'];
        $today = now();

        $rows = [
            // [anggota, judul, penulis, genre, tipe, penerbit, catatan, status, catatan_admin, hari_lalu_diajukan, hari_lalu_ditanggapi]
            ['nadia.oktavia', 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Fiksi', 'Novel', 'Hasta Mitra', 'Katanya wajib baca untuk pencinta sastra klasik Indonesia.', 'pending', null, 3, null],
            ['taufik.ismail', 'Demon Slayer Vol. 1', 'Koyoharu Gotouge', 'Komik', 'Manga', 'Elex Media', null, 'pending', null, 1, null],
            ['anita.wulandari', 'Ikigai', 'Héctor García', 'Psikologi', 'Referensi', 'Noura Books', 'Banyak direkomendasikan teman kuliah.', 'pending', null, 0, null],
            ['putri.ramadhani', 'Rich Dad Poor Dad', 'Robert Kiyosaki', 'Bisnis', 'Referensi', 'Gramedia', null, 'approved', 'Akan dianggarkan pembelian bulan depan, terima kasih usulannya.', 10, 8],
            ['fajar.n', 'Jujutsu Kaisen Vol. 1', 'Gege Akutami', 'Komik', 'Manga', 'Elex Media', null, 'fulfilled', 'Sudah tersedia di rak C-06, silakan dipinjam.', 25, 20],
            ['dewi.anggraini', 'Pemrograman Web dengan Laravel', 'Budi Raharjo', 'Teknologi', 'Referensi', 'Informatika', 'Untuk belajar mandiri di luar kuliah.', 'fulfilled', 'Sudah dibelikan 2 eksemplar, cek rak B-06.', 35, 30],
            ['yusuf.alfarizi', 'Novel Dilan 1990', 'Pidi Baiq', 'Fiksi', 'Novel', 'Mizan', null, 'rejected', 'Anggaran bulan ini sudah habis untuk kategori novel populer, akan dipertimbangkan periode berikutnya.', 18, 15],
            ['budi.s', 'The Lean Startup', 'Eric Ries', 'Bisnis', 'Referensi', 'Crown Business', null, 'rejected', 'Sudah ada buku dengan topik serupa (Business Model Canvas) di katalog.', 22, 19],
        ];

        foreach ($rows as [$user, $title, $author, $genre, $type, $publisher, $notes, $status, $adminNotes, $askedDaysAgo, $handledDaysAgo]) {
            $req = BookRequest::create([
                'user_id' => $m[$user]->id, 'title' => $title, 'author' => $author, 'genre' => $genre, 'type' => $type,
                'publisher' => $publisher, 'notes' => $notes, 'status' => $status, 'admin_notes' => $adminNotes,
                'handled_by' => $handledDaysAgo !== null ? $staff->id : null,
            ]);
            $asked = $today->copy()->subDays($askedDaysAgo);
            $handled = $handledDaysAgo !== null ? $today->copy()->subDays($handledDaysAgo) : $asked;
            $req->forceFill(['created_at' => $asked, 'updated_at' => $handled])->save();

            if ($status !== 'pending') {
                Notification::send($m[$user]->id, 'Request buku ditanggapi', 'Request "'.$title.'" '.match ($status) {
                    'approved' => 'disetujui dan akan diusahakan', 'fulfilled' => 'sudah tersedia di katalog', default => 'belum bisa dipenuhi',
                }.'.', $status === 'rejected' ? 'warning' : 'success');
                $this->backdateLatestNotification($m[$user], $handled);
            }
        }
    }

    /* ------------------------------------------------ 6. PENGAJUAN STAF */

    private function seedStaffApplication(): void
    {
        $kepala = $this->staff['kepala'];
        $today = now();

        // Disetujui — anggota ini sungguhan naik peran jadi pustakawan, konsisten dengan tabel users.
        $rizky = $this->members['rizky.hidayat'];
        $app1 = StaffApplication::create([
            'user_id' => $rizky->id, 'reason' => 'Saya mahasiswa Ilmu Perpustakaan dan ingin menambah pengalaman praktik langsung mengelola koleksi dan peminjaman.',
            'experience' => 'Pernah magang 3 bulan di perpustakaan kampus, terbiasa menata katalog dan melayani peminjaman.',
            'status' => 'approved', 'admin_notes' => 'Pengalaman relevan dan aktif sebagai anggota. Disetujui.', 'handled_by' => $kepala->id,
        ]);
        $askedAt = $today->copy()->subDays(7);
        $app1->forceFill(['created_at' => $askedAt, 'updated_at' => $askedAt->copy()->addDays(2)])->save();
        $rizky->forceFill(['role' => User::ROLE_PUSTAKAWAN])->save();
        Notification::send($rizky->id, 'Pengajuan pustakawan disetujui', 'Selamat! Anda sekarang Pustakawan. Buka menu profil untuk masuk ke panel.', 'success');
        $this->backdateLatestNotification($rizky, $askedAt->copy()->addDays(2));

        // Menunggu keputusan
        $app2 = StaffApplication::create([
            'user_id' => $this->members['putri.ramadhani']->id,
            'reason' => 'Saya ingin membantu kegiatan literasi di perpustakaan sambil mengisi waktu luang di sela kuliah.',
            'experience' => null, 'status' => 'pending',
        ]);
        $askedAt2 = $today->copy()->subDays(2);
        $app2->forceFill(['created_at' => $askedAt2, 'updated_at' => $askedAt2])->save();

        // Ditolak
        $taufik = $this->members['taufik.ismail'];
        $app3 = StaffApplication::create([
            'user_id' => $taufik->id, 'reason' => 'Saya suka membaca dan ingin tahu cara kerja perpustakaan dari dalam.',
            'experience' => null, 'status' => 'rejected', 'admin_notes' => 'Anggota baru bergabung, mohon aktif dulu minimal 3 bulan sebelum mengajukan kembali.', 'handled_by' => $kepala->id,
        ]);
        $askedAt3 = $today->copy()->subDays(5);
        $app3->forceFill(['created_at' => $askedAt3, 'updated_at' => $askedAt3->copy()->addDay()])->save();
        Notification::send($taufik->id, 'Pengajuan pustakawan ditolak', 'Pengajuan Anda belum bisa disetujui. Catatan: Anggota baru bergabung, mohon aktif dulu minimal 3 bulan sebelum mengajukan kembali.', 'warning');
        $this->backdateLatestNotification($taufik, $askedAt3->copy()->addDay());
    }
}

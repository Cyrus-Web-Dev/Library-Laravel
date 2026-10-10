<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;

/**
 * Mengisi cover_image untuk buku yang belum punya cover.
 *   php artisan perpus:backfill-covers
 */
class BackfillCoversCommand extends Command
{
    protected $signature = 'perpus:backfill-covers {--force : Timpa cover yang sudah ada}';

    protected $description = 'Generate/unduh cover untuk buku yang belum punya gambar sampul';

    public function handle(): int
    {
        $dir = storage_path('app/public/covers');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $query = Book::query();
        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('cover_image')->orWhere('cover_image', '');
            });
        }

        $books = $query->get();
        if ($books->isEmpty()) {
            $this->info('Semua buku sudah punya cover.');

            return self::SUCCESS;
        }

        $this->info("Memproses {$books->count()} buku...");

        // URL cover publik (Open Library) berdasarkan kata kunci judul
        $known = [
            'naruto' => 'https://covers.openlibrary.org/b/id/7222246-L.jpg',
            'one piece' => 'https://covers.openlibrary.org/b/id/10521366-L.jpg',
            'solo leveling' => 'https://covers.openlibrary.org/b/isbn/9781975319274-L.jpg',
            'atomic habits' => 'https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg',
            'sapiens' => 'https://covers.openlibrary.org/b/isbn/9780062316097-L.jpg',
            'clean code' => 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg',
            'filosofi teras' => 'https://covers.openlibrary.org/b/isbn/9786024246945-L.jpg',
            'bumi' => 'https://covers.openlibrary.org/b/isbn/9786020301136-L.jpg',
            'laut bercerita' => 'https://covers.openlibrary.org/b/isbn/9786024246938-L.jpg',
            'cantik itu luka' => 'https://covers.openlibrary.org/b/isbn/9789792233735-L.jpg',
            'true beauty' => 'https://covers.openlibrary.org/b/isbn/9781975319458-L.jpg',
        ];

        $ok = 0;
        foreach ($books as $book) {
            $slug = str($book->title)->slug()->limit(40, '')->toString() ?: 'book-'.$book->id;
            $path = null;

            $titleLower = mb_strtolower($book->title);
            foreach ($known as $key => $url) {
                if (str_contains($titleLower, $key)) {
                    $path = $this->download($url, $slug.'-'.$book->id, $dir);
                    break;
                }
            }

            if (! $path) {
                $path = $this->generate($book->title, $book->author ?? '', $slug.'-'.$book->id, $dir);
            }

            if ($path) {
                $book->forceFill(['cover_image' => $path])->saveQuietly();
                $ok++;
                $this->line("  ✓ {$book->title}");
            } else {
                $this->warn("  ✗ gagal: {$book->title}");
            }
        }

        $this->info("Selesai: {$ok}/{$books->count()} cover diisi.");
        $this->line('Pastikan sudah menjalankan: php artisan storage:link');

        return self::SUCCESS;
    }

    private function download(string $url, string $slug, string $dir): ?string
    {
        try {
            $ctx = stream_context_create([
                'http' => ['timeout' => 10, 'header' => "User-Agent: PerpustakaanCover/1.0\r\n"],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $bin = @file_get_contents($url, false, $ctx);
            if ($bin === false || strlen($bin) < 800) {
                return null;
            }
            $filename = $slug.'.jpg';
            file_put_contents($dir.'/'.$filename, $bin);

            return 'covers/'.$filename;
        } catch (\Throwable) {
            return null;
        }
    }

    private function generate(string $title, string $author, string $slug, string $dir): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $w = 300;
        $h = 420;
        $im = imagecreatetruecolor($w, $h);
        $colors = [
            [0x1f4d43, 0xe0a526],
            [0x1a1a2e, 0xe94560],
            [0x0f3460, 0x00cec9],
            [0x2c3e50, 0x9b59b6],
            [0x3d2914, 0xd4a574],
        ];
        [$bgHex, $acHex] = $colors[crc32($title) % count($colors)];
        $bg = imagecolorallocate($im, ($bgHex >> 16) & 0xFF, ($bgHex >> 8) & 0xFF, $bgHex & 0xFF);
        $ac = imagecolorallocate($im, ($acHex >> 16) & 0xFF, ($acHex >> 8) & 0xFF, $acHex & 0xFF);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, $w, $h, $bg);
        imagefilledrectangle($im, 0, $h - 70, $w, $h, $ac);

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
        if ($line) {
            $lines[] = $line;
        }
        $y = 130;
        foreach (array_slice($lines, 0, 4) as $ln) {
            $tw = imagefontwidth(5) * strlen($ln);
            imagestring($im, 5, (int) max(4, ($w - $tw) / 2), $y, $ln, $white);
            $y += 22;
        }
        if ($author) {
            $aw = imagefontwidth(3) * strlen($author);
            imagestring($im, 3, (int) max(4, ($w - $aw) / 2), $h - 42, $author, $white);
        }

        $filename = $slug.'.jpg';
        imagejpeg($im, $dir.'/'.$filename, 88);
        imagedestroy($im);

        return 'covers/'.$filename;
    }
}

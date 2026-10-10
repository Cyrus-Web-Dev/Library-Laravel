<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        $disk = Storage::disk('local');
        $files = collect($disk->files('backups'))
            ->filter(fn ($f) => str_ends_with($f, '.sql') || str_ends_with($f, '.gz'))
            ->map(fn ($f) => [
                'name' => basename($f),
                'path' => $f,
                'size' => $disk->size($f),
                'modified' => $disk->lastModified($f),
            ])
            ->sortByDesc('modified')
            ->values();

        return Inertia::render('Admin/Backup', [
            'backups' => $files,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        $connection = config('database.default');
        $db = config("database.connections.{$connection}");

        if (($db['driver'] ?? '') !== 'pgsql') {
            return back()->with('error', 'Backup otomatis saat ini mendukung PostgreSQL.');
        }

        $filename = 'backup_' . now()->format('Ymd_His') . '.sql';
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir . '/' . $filename;

        $host = $db['host'] ?? '127.0.0.1';
        $port = $db['port'] ?? 5432;
        $database = $db['database'];
        $username = $db['username'];
        $password = $db['password'] ?? '';

        $env = "PGPASSWORD=" . escapeshellarg($password);
        $cmd = sprintf(
            '%s pg_dump -h %s -p %s -U %s -d %s -F p -f %s 2>&1',
            $env,
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($path)
        );

        exec($cmd, $output, $code);

        if ($code !== 0 || ! file_exists($path)) {
            return back()->with('error', 'Gagal membuat backup: ' . implode(' ', $output));
        }

        ActivityLog::record('backup', 'Membuat backup database: ' . $filename);

        return back()->with('success', 'Backup berhasil dibuat: ' . $filename);
    }

    public function download(Request $request, string $filename)
    {
        abort_unless($request->user()->isSuperadmin(), 403);
        $filename = basename($filename);
        $path = 'backups/' . $filename;

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path);
    }

    public function destroy(Request $request, string $filename)
    {
        abort_unless($request->user()->isSuperadmin(), 403);
        $filename = basename($filename);
        Storage::disk('local')->delete('backups/' . $filename);
        ActivityLog::record('backup_delete', 'Menghapus backup: ' . $filename);

        return back()->with('success', 'Backup dihapus.');
    }
}

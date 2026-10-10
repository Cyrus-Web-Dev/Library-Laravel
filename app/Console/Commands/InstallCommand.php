<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Pengganti install.php (versi PHP native): menjalankan migrasi + seeder data dasar,
 * lalu membuat akun Super Admin (Kepala Pustaka) pertama secara interaktif.
 *
 *   php artisan perpus:install
 */
class InstallCommand extends Command
{
    protected $signature = 'perpus:install {--fresh : Jalankan migrate:fresh (HAPUS semua data lama)}';

    protected $description = 'Pasang Perpustakaan Digital: migrasi, data dasar, dan akun Super Admin pertama';

    public function handle(): int
    {
        $this->info('=== Pasang Perpustakaan Digital ===');

        if (User::where('role', User::ROLE_SUPERADMIN)->exists() && ! $this->option('fresh')) {
            $this->warn('Sudah ada akun Super Admin. Jalankan dengan --fresh bila ingin memasang ulang dari awal (data lama akan terhapus).');

            return self::FAILURE;
        }

        $this->call($this->option('fresh') ? 'migrate:fresh' : 'migrate');
        $this->call('db:seed', ['--class' => \Database\Seeders\LibrarySeeder::class]);

        $this->newLine();
        $this->info('Buat akun Super Admin (Kepala Pustaka):');

        while (true) {
            $fullName = $this->ask('Nama lengkap');
            $username = $this->ask('Username');
            $email = $this->ask('Email');
            $password = $this->secret('Password (min. 8 karakter, huruf besar+kecil, angka)');
            $confirm = $this->secret('Ulangi password');

            try {
                $this->validateInput(compact('fullName', 'username', 'email', 'password', 'confirm'));
                break;
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ($messages as $m) {
                        $this->error($m);
                    }
                }
                $this->newLine();
            }
        }

        $user = User::create([
            'full_name' => $fullName,
            'username' => $username,
            'email' => mb_strtolower($email),
            'password' => Hash::make($password), // argon2id, lihat config/hashing.php
            'role' => User::ROLE_SUPERADMIN,
            'member_type' => 'Umum',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info("Selesai! Akun Super Admin '{$user->username}' berhasil dibuat.");
        $this->line('Jalankan berikut di terminal terpisah untuk menjalankan aplikasi:');
        $this->line('  php artisan serve   (backend)');
        $this->line('  npm run dev         (frontend, mode pengembangan)');

        return self::SUCCESS;
    }

    private function validateInput(array $data): void
    {
        validator($data, [
            'fullName' => 'required|string|max:100',
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_.]+$/', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'password' => ['required', Password::defaults(), function ($attr, $value, $fail) use ($data) {
                if ($value !== $data['confirm']) {
                    $fail('Konfirmasi password tidak sama.');
                }
            }],
        ])->validate();
    }
}

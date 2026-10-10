<?php

namespace App\Console\Commands;

use App\Exceptions\GmailSendException;
use App\Services\GmailApiService;
use Illuminate\Console\Command;

class MailTest extends Command
{
    protected $signature = 'perpus:mail-test {email? : Alamat tujuan untuk tes kirim}';

    protected $description = 'Diagnosa kredensial Gmail API, dan (opsional) kirim satu email tes';

    public function handle(GmailApiService $gmail): int
    {
        $diag = $gmail->diagnose();

        $this->line('--- Diagnosa Gmail API ---');
        $this->line('Siap mengirim : '.($diag['ready'] ? 'YA' : 'TIDAK'));
        $this->line('Sumber        : '.($diag['source'] ?? '-'));
        $this->line('credentials.json ada : '.($diag['files']['credentials'] ? 'ya' : 'tidak'));
        $this->line('token.json ada       : '.($diag['files']['token'] ? 'ya' : 'tidak'));
        $this->line('GMAIL_CLIENT_ID/SECRET di .env : '.($diag['has_client'] ? 'ya' : 'tidak'));
        foreach ($diag['notes'] as $note) {
            $this->warn('Catatan: '.$note);
        }

        if (! $diag['ready']) {
            $this->error('Belum siap. Lihat README bagian "Menyiapkan Gmail API".');

            return self::FAILURE;
        }

        $to = $this->argument('email');
        if (! $to) {
            $this->info('Kredensial lengkap. Jalankan lagi dengan alamat email untuk mengirim tes sungguhan:');
            $this->line('  php artisan perpus:mail-test nama@email.com');

            return self::SUCCESS;
        }

        try {
            $gmail->send($to, null, 'Tes Gmail API — Perpustakaan Digital', '<p>Email ini mengonfirmasi Gmail API sudah berfungsi.</p>');
            $this->info("Email tes berhasil dikirim ke {$to}.");

            return self::SUCCESS;
        } catch (GmailSendException $e) {
            $this->error('Gagal mengirim: '.$e->userMessage());

            return self::FAILURE;
        }
    }
}

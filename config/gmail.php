<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sumber kredensial (dicoba berurutan; yang pertama lengkap dipakai)
    |--------------------------------------------------------------------------
    | 1. File storage/app/credentials/credentials.json + token.json (hasil OAuth
    |    "Desktop app" di Google Cloud Console — lihat README bagian "Menyiapkan Gmail API")
    | 2. Variabel .env: GMAIL_CLIENT_ID, GMAIL_CLIENT_SECRET, GMAIL_REFRESH_TOKEN
    */

    'client_id' => env('GMAIL_CLIENT_ID'),
    'client_secret' => env('GMAIL_CLIENT_SECRET'),
    'refresh_token' => env('GMAIL_REFRESH_TOKEN'),

    // Opsional: path cacert.pem bila muncul error SSL "cURL error 60" (umum di Windows/XAMPP).
    'ca_bundle' => env('GMAIL_CA_BUNDLE'),

    'credentials_path' => storage_path('app/credentials/credentials.json'),
    'token_path' => storage_path('app/credentials/token.json'),

    'from_name' => env('MAIL_FROM_NAME', 'Perpustakaan Digital'),

    'http_timeout' => (int) env('GMAIL_HTTP_TIMEOUT', 15),
    'http_connect_timeout' => (int) env('GMAIL_HTTP_CONNECT_TIMEOUT', 8),

    /*
    | Mode lokal: kalau Gmail belum dikonfigurasi DAN APP_ENV=local DAN APP_DEBUG=true,
    | kode OTP ditulis ke storage/logs/laravel.log supaya pengembangan/ujicoba tetap
    | bisa jalan tanpa kredensial Gmail asli. Otomatis MATI di production.
    */
    'local_log_fallback' => (bool) env('GMAIL_LOCAL_LOG_FALLBACK', true),

    // --- Pengaturan OTP (dipakai verifikasi registrasi & reset password) ---
    'otp_length' => 6,
    'otp_ttl_minutes' => 10,
    'otp_max_attempts' => 5,
    'otp_resend_cooldown_seconds' => 60,

];

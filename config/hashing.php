<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver hashing password
    |--------------------------------------------------------------------------
    | argon2id dipilih sebagai default (BUKAN bcrypt): memory-hard sehingga jauh lebih
    | tahan terhadap serangan akselerasi GPU/ASIC dibanding bcrypt, dan merupakan
    | pemenang Password Hashing Competition serta rekomendasi pertama OWASP saat ini.
    |
    | Wajib PHP dikompilasi dengan dukungan Argon2 (PHP 7.2+ / 8.x modern sudah punya
    | ini secara default). Cek dengan: php -r "var_dump(defined('PASSWORD_ARGON2ID'));"
    | — harus bool(true). Bila false, set HASH_DRIVER=bcrypt di .env sebagai fallback.
    |--------------------------------------------------------------------------
    */
    'driver' => env('HASH_DRIVER', 'argon2id'),

    'bcrypt' => [
        // Hanya dipakai bila HASH_DRIVER=bcrypt, atau untuk memverifikasi hash lama
        // yang mungkin masih tersisa dari migrasi data — rehash otomatis ke argon2id
        // terjadi sendiri saat pemiliknya berhasil login (lihat AuthController@login).
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
    ],

    // Rekomendasi minimum OWASP untuk argon2id: memory 19 MiB, time 2, threads 1.
    // Dinaikkan sedikit di sini (37 MiB, time 3) untuk margin keamanan ekstra —
    // server modern menanganinya dalam puluhan milidetik saja.
    'argon' => [
        'memory' => (int) env('ARGON_MEMORY', 37888), // KiB (~37 MiB)
        'threads' => (int) env('ARGON_THREADS', 1),
        'time' => (int) env('ARGON_TIME', 3),
        'verify' => env('HASH_VERIFY', true),
    ],

];

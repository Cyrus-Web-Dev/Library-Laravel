<?php

use Laravel\Sanctum\Sanctum;

return [

    'stateful' => explode(',', (string) env(
        'SANCTUM_STATEFUL_DOMAINS',
        'localhost,localhost:8000,127.0.0.1,127.0.0.1:8000,::1'
    )),

    'guard' => ['web'],

    // Token REST API tidak kedaluwarsa otomatis kecuali diatur (pengguna bisa mencabutnya
    // sendiri lewat "Keluar dari semua perangkat").
    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];

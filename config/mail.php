<?php

return [

    'default' => env('MAIL_MAILER', 'gmail_api'),

    'mailers' => [

        // Transport-nya didaftarkan di App\Providers\AppServiceProvider lewat Mail::extend().
        'gmail_api' => [
            'transport' => 'gmail_api',
        ],

        // Alternatif untuk pengembangan tanpa kredensial Gmail: isi email ditulis
        // ke storage/logs/laravel.log. Set MAIL_MAILER=log di .env untuk memakainya.
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'array' => ['transport' => 'array'],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@perpustakaan.local'),
        'name' => env('MAIL_FROM_NAME', 'Perpustakaan Digital'),
    ],
];

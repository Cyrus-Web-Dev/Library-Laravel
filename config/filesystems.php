<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // Privat: sampul & avatar yang diproses tetap lewat disk ini tapi dipublikasikan
        // via symlink storage/app/public -> public/storage (php artisan storage:link).
        // File e-book PDF TIDAK boleh dipublikasikan langsung — selalu disajikan lewat
        // BookController@readEbook supaya hanya pengguna yang login yang bisa membukanya.
        'ebooks' => [
            'driver' => 'local',
            'root' => storage_path('app/ebooks'),
            'serve' => false,
            'throw' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];

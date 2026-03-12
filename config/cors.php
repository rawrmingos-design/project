<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Path 'assets/*', 'storage/*', dan 'livewire/*' ditambahkan agar Filament
    | FileUpload bisa load preview gambar dari admin subdomain ke main domain.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        // Filament file upload preview & storage dari admin subdomain
        'assets/*',
        'assets/**',
        'storage/*',
        'storage/**',
        'livewire/*',
        'filament/*',
        '*', // fallback: cover all paths (assets served from public/)
    ],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Gunakan wildcard - aman karena static assets tidak butuh credentials
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    // HARUS false jika allowed_origins = ['*'] (tidak bisa keduanya sekaligus)
    'supports_credentials' => false,

];

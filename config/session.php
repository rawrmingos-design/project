<?php

use Illuminate\Support\Str;

return [
    'driver' => env('SESSION_DRIVER', 'file'),

    // Waktu hidup sesi dalam menit (default: 120)
    'lifetime' => env('SESSION_LIFETIME', 30),

    // Tentukan apakah sesi akan berakhir ketika browser ditutup (default: false)
    'expire_on_close' => false,

    // Aktifkan enkripsi data sesi (default: false)
    'encrypt' => true,

    // Penyimpanan sesi dalam file
    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION', null),

    'table' => 'sessions',

    'store' => env('SESSION_STORE', null),

    'lottery' => [2, 100],

    // Nama cookie sesi (default: {nama_aplikasi}_session)
    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    // Path untuk cookie sesi (default: '/')
    'path' => '/',

    // Domain untuk cookie sesi (default: null)
    'domain' => env('SESSION_DOMAIN', null),

    // HTTPS Only Cookies (default: true)
    'secure' => env('SESSION_SECURE_COOKIE', true),

    // HTTP Access Only (default: true)
    'http_only' => true,

    // Same-Site Cookies (default: strict)
    'same_site' => 'strict',
];

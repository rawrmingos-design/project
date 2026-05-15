<?php

use Illuminate\Support\Str;

$sessionDomain = env('SESSION_DOMAIN');
if (is_string($sessionDomain)) {
    $sessionDomain = trim($sessionDomain);
}
if ($sessionDomain === '' || $sessionDomain === 'null') {
    $sessionDomain = null;
}

$runtimeHost = (string) parse_url((string) env('APP_URL', ''), PHP_URL_HOST);
if (isset($_SERVER['HTTP_HOST'])) {
    $runtimeHost = (string) explode(':', (string) $_SERVER['HTTP_HOST'])[0];
}
$runtimeHost = strtolower(trim($runtimeHost));
$isLocalRuntime = in_array($runtimeHost, ['localhost', '127.0.0.1', '::1'], true);

if ($isLocalRuntime) {
    // Host-only cookie is safest for localhost and avoids domain mismatch.
    $sessionDomain = null;
} elseif (is_string($sessionDomain)) {
    $normalizedSessionDomain = strtolower(ltrim($sessionDomain, '.'));
    if ($normalizedSessionDomain !== '' && $runtimeHost !== '' && ! str_ends_with($runtimeHost, $normalizedSessionDomain)) {
        // Prevent invalid domain attribute when current host differs from SESSION_DOMAIN.
        $sessionDomain = null;
    }
}

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

    // Domain untuk cookie sesi. Null = host-only cookie.
    'domain' => $sessionDomain,

    // HTTPS Only Cookies (default: true)
    'secure' => env('SESSION_SECURE_COOKIE', true),

    // HTTP Access Only (default: true)
    'http_only' => true,

    // Same-Site Cookies (lax|strict|none|null)
    'same_site' => env('SESSION_SAME_SITE', 'lax'),
];

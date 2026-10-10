<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SaaS Tenancy Kill Switch
    |--------------------------------------------------------------------------
    |
    | Keep SaaS tenant storefronts and self-service onboarding disabled unless
    | a deployment explicitly enables them. SUSPENDED_TENANT is the deployment
    | flag; SAAS_TENANCY_DISABLED is also accepted as a clearer alias.
    |
    */

    'disabled' => env('SUSPENDED_TENANT', env('SAAS_TENANCY_DISABLED', true)),

    /*
    |--------------------------------------------------------------------------
    | Subdomain Guard
    |--------------------------------------------------------------------------
    |
    | Saklar terpisah supaya pengecekan nama subdomain bisa dimatikan tanpa
    | deploy dan tanpa mematikan seluruh SaaS (kill switch di atas terlalu
    | kasar untuk keperluan ini).
    |
    | PENTING: dibaca lewat config(), BUKAN env() langsung — saat
    | bootstrap/cache/config.php ada, Laravel melewati pembacaan .env dan
    | env() di luar file config mengembalikan null.
    |
    */

    'subdomain_guard_enabled' => env('SUBDOMAIN_GUARD_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare (pengecekan nama di luar daftar reserved)
    |--------------------------------------------------------------------------
    |
    | Hanya dipakai untuk MEMBACA record DNS. Kalau kredensial kosong atau API
    | gagal, guard fail-open: pendaftaran tetap jalan, kejadiannya dicatat.
    |
    */

    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'cache_minutes' => env('CLOUDFLARE_DNS_CACHE_MINUTES', 10),
    ],

];

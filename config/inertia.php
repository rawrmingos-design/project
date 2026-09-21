<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | Bundle SSR dipatok eksplisit ke bootstrap/ssr/ssr.mjs. Tanpa patokan ini,
    | deteksi otomatis Inertia akan menandai public/js/app.js (hasil build
    | laravel-mix) sebagai bundle SSR, sehingga SSR dianggap aktif padahal
    | tidak ada server yang berjalan — akibatnya tag SEO tidak dirender.
    |
    | Bila bundle atau server SSR tidak ada, Inertia otomatis jatuh ke
    | rendering client-side (tidak ada halaman yang error).
    |
    */

    'ssr' => [

        'enabled' => (bool) env('INERTIA_SSR_ENABLED', true),

        'runtime' => env('INERTIA_SSR_RUNTIME', 'node'),

        'ensure_runtime_exists' => (bool) env('INERTIA_SSR_ENSURE_RUNTIME_EXISTS', false),

        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),

        'ensure_bundle_exists' => true,

        'bundle' => base_path('bootstrap/ssr/ssr.mjs'),

        'throw_on_error' => (bool) env('INERTIA_SSR_THROW_ON_ERROR', false),

    ],

];

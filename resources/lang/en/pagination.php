<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Language Lines
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh resources/views/vendor/pagination/legacy.blade.php dan view
    | pagination bawaan Laravel.
    |
    | PENTING: label sengaja TANPA entitas HTML (`&laquo;` / `&raquo;`) seperti
    | versi sebelumnya. View pagination legacy SUDAH merender ikon panah SVG di
    | samping label, jadi entitas itu tampil sebagai panah KEDUA di tombol yang
    | sama — itu keluhan client "tombol previous/next kurang rapi". Entitasnya
    | juga pernah ter-escape ganda (`&amp;laquo;`) saat dirender dengan `{{ }}`.
    |
    | Kunci `showing`/`to`/`of`/`results` dipakai untuk ringkasan di view legacy
    | (view bawaan Laravel memakai teks Inggris hardcoded).
    |
    */

    'previous' => 'Previous',
    'next' => 'Next',

    'showing' => 'Showing',
    'to' => 'to',
    'of' => 'of',
    'results' => 'results',

];

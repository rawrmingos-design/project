<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BeritasSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('beritas')->truncate();

        DB::table('beritas')->insert([
        [
            'id' => 312,
            'path' => 'assets/banner/www.imhaf.online (1).webp',
            'tipe' => 'popup',
            'deskripsi' => '<p>Mau rental akun ff dan ML? di <a target=\"_blank\" rel=\"noopener noreferrer nofollow\" href=\"http://imhaf.online\">imhaf.online</a> aja, lebih murah dan aman</p>',
            'created_at' => '2025-09-29 03:12:57',
            'updated_at' => '2026-01-01 09:23:34'
        ],
        ]);
    }
}
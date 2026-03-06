<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaketsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pakets')->truncate();

        DB::table('pakets')->insert([
        [
            'id' => 2,
            'nama' => '⭐ Spesial Items',
            'created_at' => '2025-04-22 00:57:11',
            'updated_at' => '2025-04-22 00:57:11'
        ],
        [
            'id' => 3,
            'nama' => '⚡ Proses Instant',
            'created_at' => '2025-04-22 00:57:29',
            'updated_at' => '2025-04-22 00:57:29'
        ],
        [
            'id' => 4,
            'nama' => 'Blessing of the Welkin Moon',
            'created_at' => '2025-04-22 01:14:12',
            'updated_at' => '2025-04-22 01:14:12'
        ],
        [
            'id' => 5,
            'nama' => 'Genesis Crystals',
            'created_at' => '2025-04-22 01:15:05',
            'updated_at' => '2025-04-22 01:15:05'
        ],
        [
            'id' => 8,
            'nama' => 'Region Indonesia',
            'created_at' => '2025-04-22 01:17:52',
            'updated_at' => '2025-04-22 01:17:52'
        ],
        [
            'id' => 9,
            'nama' => 'Region Global',
            'created_at' => '2025-04-22 01:18:14',
            'updated_at' => '2025-04-22 01:18:14'
        ],
        [
            'id' => 10,
            'nama' => 'PB Cash',
            'created_at' => '2025-04-22 01:26:04',
            'updated_at' => '2025-04-22 01:26:04'
        ],
        [
            'id' => 11,
            'nama' => '✉️ Akun Premium',
            'created_at' => '2025-04-22 01:32:44',
            'updated_at' => '2025-04-22 01:32:44'
        ],
        ]);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VouchersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('vouchers')->truncate();

        DB::table('vouchers')->insert([
        [
            'id' => 66,
            'kode' => 'Diskon Natal',
            'promo' => 50,
            'stock' => 94,
            'mintrx' => 0,
            'max_potongan' => 1000000000,
            'created_at' => '2026-01-01 15:21:18',
            'updated_at' => '2026-01-01 15:46:32'
        ],
        ]);
    }
}
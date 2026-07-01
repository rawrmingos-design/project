<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvidersSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('providers')->truncate();

        DB::table('providers')->insert([
        [
            'id' => 1,
            'code' => 'digiflazz',
            'name' => 'DIGIFLAZZ',
            'api_endpoint' => 'https://api.digiflazz.com',
            'balance' => '1233.00',
            'is_active' => 1,
            'last_check_at' => '2026-02-06 11:35:31',
            'created_at' => '2026-02-06 09:25:03',
            'updated_at' => '2026-02-06 11:35:31'
        ],
        [
            'id' => 2,
            'code' => 'bangjeff',
            'name' => 'BANGJEFF',
            'api_endpoint' => 'https://client.bangjeff.com/api/v2',
            'balance' => '0.00',
            'is_active' => 1,
            'last_check_at' => null,
            'created_at' => '2026-02-06 09:25:03',
            'updated_at' => '2026-02-06 09:25:03'
        ],
        [
            'id' => 3,
            'code' => 'vip',
            'name' => 'VIP RESELLER',
            'api_endpoint' => 'https://vip-reseller.co.id/api',
            'balance' => '0.00',
            'is_active' => 1,
            'last_check_at' => null,
            'created_at' => '2026-02-06 09:25:03',
            'updated_at' => '2026-02-06 09:25:03'
        ],
        [
            'id' => 4,
            'code' => 'apigames',
            'name' => 'APIGAMES',
            'api_endpoint' => 'https://v1.apigames.id',
            'balance' => '0.00',
            'is_active' => 1,
            'last_check_at' => null,
            'created_at' => '2026-02-06 09:25:03',
            'updated_at' => '2026-02-06 09:25:03'
        ],
        [
            'id' => 5,
            'code' => 'manual',
            'name' => 'MANUAL',
            'api_endpoint' => null,
            'balance' => '0.00',
            'is_active' => 1,
            'last_check_at' => null,
            'created_at' => '2026-02-06 09:25:03',
            'updated_at' => '2026-02-06 09:25:03'
        ],
        ]);
    }
}
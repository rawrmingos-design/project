<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryTypesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('category_types')->truncate();

        DB::table('category_types')->insert([
        [
            'id' => 1,
            'name' => '🎮Top Up Games',
            'slug' => 'top-up-games',
            'sort' => 1,
            'icon' => null,
            'created_at' => '2026-01-16 05:25:09',
            'updated_at' => '2026-01-16 05:25:09'
        ],
        [
            'id' => 2,
            'name' => '✨Specialist Mobile Legends',
            'slug' => 'specialist-mobile-legends',
            'sort' => 2,
            'icon' => null,
            'created_at' => '2026-01-16 05:25:09',
            'updated_at' => '2026-01-16 05:25:09'
        ],
        [
            'id' => 3,
            'name' => '📲App Premium',
            'slug' => 'app-premium',
            'sort' => 3,
            'icon' => null,
            'created_at' => '2026-01-16 05:25:09',
            'updated_at' => '2026-01-16 05:25:09'
        ],
        [
            'id' => 4,
            'name' => '📞Pulsa & Data',
            'slug' => 'pulsa-data',
            'sort' => 4,
            'icon' => null,
            'created_at' => '2026-01-16 05:25:09',
            'updated_at' => '2026-01-16 05:25:09'
        ],
        [
            'id' => 5,
            'name' => '🏷Voucher',
            'slug' => 'voucher',
            'sort' => 5,
            'icon' => null,
            'created_at' => '2026-01-16 05:25:09',
            'updated_at' => '2026-01-16 05:25:09'
        ],
        ]);
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoryType;
use App\Models\Kategori;

class CategoryTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            [
                'name' => '🎮Top Up Games',
                'slug' => 'top-up-games',
                'sort' => 1,
                'types' => ['game', 'populer']
            ],
            [
                'name' => '✨Specialist Mobile Legends',
                'slug' => 'specialist-mobile-legends',
                'sort' => 2,
                'types' => ['giftskin', 'joki', 'jokigendong', 'vilogml']
            ],
            [
                'name' => '📲App Premium',
                'slug' => 'app-premium',
                'sort' => 3,
                'types' => ['app']
            ],
            [
                'name' => '📞Pulsa & Data',
                'slug' => 'pulsa-data',
                'sort' => 4,
                'types' => ['pulsa', 'data']
            ],
            [
                'name' => '🏷Voucher',
                'slug' => 'voucher',
                'sort' => 5,
                'types' => ['voucher']
            ],
        ];

        foreach ($types as $data) {
            $type = CategoryType::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'sort' => $data['sort']
                ]
            );

            Kategori::whereIn('tipe', $data['types'])->update(['category_type_id' => $type->id]);
        }
    }
}

<?php

namespace Database\Factories;

use App\Models\Layanan;
use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

class LayananFactory extends Factory
{
    protected $model = Layanan::class;

    public function definition()
    {
        // Ensure Kategori exists or create one if needed, but for Unit test we might just need ID.
        // using Kategori::factory() assumes KategoriFactory exists, which likely doesn't.
        // We'll rely on simply creating a category if factories allow, or skipping relations not needed for this specific test.
        // However, Layanan needs kategori_id.
        
        return [
            'kategori_id' => Kategori::factory(), 
            'layanan' => $this->faker->words(3, true),
            'provider_id' => 'legacy_code',
            'provider_nomimal' => 'legacy_sku',
            'harga' => 10000,
            'harga_member' => 9000,
            'harga_platinum' => 8000,
            'harga_gold' => 7000,
            'profit' => 10,
            'profit_member' => 10,
            'profit_platinum' => 10,
            'profit_gold' => 10,
            'catatan' => 'Test Note',
            'status' => 'available',
            'product_logo' => 'default.png',
            'is_flash_sale' => 0,
            'expired_flash_sale' => null,
            'harga_flash_sale' => 0,
            'stock_flash_sale' => 0,
            'banner_flash_sale' => null,
        ];
    }
}

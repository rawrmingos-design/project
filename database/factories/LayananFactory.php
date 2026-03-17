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
        return [
            'kategori_id' => Kategori::factory(), 
            'layanan' => $this->faker->words(3, true),
            // Legacy fields used by routing + order flow:
            // `provider` = provider code (digiflazz/vip/manual), `provider_id` = SKU at provider.
            'provider' => 'manual',
            'provider_id' => 'manual-sku',
            'harga' => 10000,
            'harga_member' => 11000,
            'harga_platinum' => 11500,
            'harga_gold' => 12000,
            'profit_member' => 10,
            'profit_platinum' => 15,
            'profit_gold' => 20,
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

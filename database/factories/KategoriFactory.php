<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

class KategoriFactory extends Factory
{
    protected $model = Kategori::class;

    public function definition()
    {
        return [
            'nama' => $this->faker->word,
            'sub_nama' => $this->faker->sentence,
            'kode' => $this->faker->unique()->slug,
            'brand' => $this->faker->word,
            'keterangan' => 'Test Kategori',
            'thumbnail' => 'thumb.jpg',
            'banner' => 'banner.jpg',
            'tipe' => 'game',
            'status' => 'active',
            'server_id' => 0,
            'deskripsi_game' => 'Desc',
            'deskripsi_field' => 'Field Desc',
        ];
    }
}

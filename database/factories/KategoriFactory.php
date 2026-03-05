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
            'nama'            => $this->faker->word,
            'sub_nama'        => $this->faker->word,
            'kode'            => $this->faker->unique()->slug,
            'tipe'            => 'game',
            'status'          => 'active',
            'server_id'       => 0,
            'thumbnail'       => 'thumb.jpg',
            'banner'          => 'banner.jpg',
            'deskripsi_game'  => 'Desc',
            'deskripsi_field' => 'Field Desc',
        ];
    }
}

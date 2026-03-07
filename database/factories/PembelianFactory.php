<?php

namespace Database\Factories;

use App\Models\Pembelian;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PembelianFactory extends Factory
{
    protected $model = Pembelian::class;

    public function definition(): array
    {
        return [
            'order_id'   => 'TEST-' . strtoupper(Str::random(8)),
            'username'   => $this->faker->userName(),
            'layanan'    => 'Mobile Legends 86 Diamond',
            'harga'      => $this->faker->randomElement([5000, 10000, 20000, 50000]),
            'profit'     => 500,
            'user_id'    => $this->faker->numerify('#####'),
            'zone'       => '1234',
            'status'     => 'Proses',
            'used_points' => 0,
        ];
    }

    public function sukses(): static
    {
        return $this->state(['status' => 'Success']);
    }

    public function proses(): static
    {
        return $this->state(['status' => 'Proses']);
    }
}

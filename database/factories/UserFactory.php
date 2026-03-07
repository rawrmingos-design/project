<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'name'          => $this->faker->name(),
            'username'      => $this->faker->unique()->userName(),
            'email'         => $this->faker->unique()->safeEmail(),
            'password'      => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'role'          => 'Member',
            'balance'       => 0,
            'point_balance' => 0,
            'no_wa'         => '08' . $this->faker->numerify('##########'),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition()
    {
        $id = uniqid();
        return [
            'name'          => 'Test User ' . $id,
            'username'      => 'user_' . $id,
            'email'         => 'test_' . $id . '@example.com',
            'password'      => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'role'          => 'Member',
            'balance'       => 0,
            'point_balance' => 0,
            'no_wa'         => '0812' . rand(10000000, 99999999),
            'created_at'    => now()->subDays(7), // Ensure account meets 7-day minimum age requirement
        ];
    }
}

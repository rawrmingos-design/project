<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResellerIntegrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'integration_code' => 'TEST-' . strtoupper($this->faker->lexify('????????')),
            'mode'             => 'live',
            'is_active'        => true,
            'allowed_ips'      => [],
        ];
    }

    public function sandbox(): static
    {
        return $this->state(['mode' => 'sandbox']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

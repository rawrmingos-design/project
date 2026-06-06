<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResellerIntegrationFactory extends Factory
{
    public function definition(): array
    {
        $rawKey = 'testing_live_key';
        return [
            'user_id'          => User::factory(),
            'integration_code' => 'TEST-' . strtoupper($this->faker->lexify('????????')),
            'mode'             => 'live',
            'is_active'        => true,
            'api_key_hash'     => hash('sha256', $rawKey),
            'api_key_hint'     => '...'.substr($rawKey, -6),
            'api_key_prefix'   => substr($rawKey, 0, 8),
            'allowed_ips'      => [],
        ];
    }

    public function sandbox(): static
    {
        return $this->state(function (array $attributes) {
            $rawKey = 'testing_sbx_key';
            return [
                'mode' => 'sandbox',
                'api_key_hash' => hash('sha256', $rawKey),
                'api_key_hint' => '...'.substr($rawKey, -6),
                'api_key_prefix' => substr($rawKey, 0, 8),
            ];
        });
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

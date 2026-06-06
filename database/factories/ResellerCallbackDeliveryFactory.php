<?php

namespace Database\Factories;

use App\Models\ResellerCallbackDelivery;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResellerCallbackDeliveryFactory extends Factory
{
    protected $model = ResellerCallbackDelivery::class;

    public function definition(): array
    {
        return [
            'user_id'                      => User::factory(),
            'reseller_integration_id'      => ResellerIntegration::factory(),
            'reseller_callback_profile_id' => null,
            'pembelian_id'                 => null,
            'environment'                  => 'live',
            'event_name'                   => 'h2h.order.updated',
            'order_id'                     => 'TEST-' . strtoupper($this->faker->lexify('????????')),
            'reference_number'             => '',
            'callback_url'                 => 'https://example.com/webhook',
            'signature_algorithm'          => 'sha256',
            'payload'                      => ['event' => 'h2h.order.updated'],
            'attempt_count'                => 1,
            'status'                       => 'pending',
            'last_attempted_at'            => now(),
        ];
    }

    public function delivered(): static
    {
        return $this->state(['status' => 'delivered', 'delivered_at' => now()]);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }
}

<?php

namespace Database\Factories;

use App\Models\ResellerApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResellerApplicationFactory extends Factory
{
    protected $model = ResellerApplication::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'applied_at' => now(),
            'business_meta' => [
                'business_name' => 'Test Business ' . uniqid(),
                'business_url' => 'https://test-business-' . uniqid() . '.com',
                'estimated_monthly_transactions' => 50000000,
                'application_reason' => 'Test application reason',
            ],
            'submitted_from_ip' => '127.0.0.1',
        ];
    }

    public function approved()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function rejected()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => 'Test rejection reason',
        ]);
    }
}

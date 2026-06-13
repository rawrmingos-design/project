<?php

namespace Database\Factories;

use App\Models\ResellerApplicationReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResellerApplicationReviewFactory extends Factory
{
    protected $model = ResellerApplicationReview::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'action' => 'submitted',
            'reviewed_by' => User::factory(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function submitted()
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'submitted',
            'reviewed_by' => $attributes['user_id'], // User submits their own application
        ]);
    }

    public function approved()
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'approved',
            'notes' => $this->faker->optional()->sentence(),
        ]);
    }

    public function rejected()
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'rejected',
            'notes' => $this->faker->sentence(), // Rejection usually has a reason
        ]);
    }

    public function resubmitted()
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'resubmitted',
            'reviewed_by' => $attributes['user_id'], // User resubmits their own application
        ]);
    }

    public function withNotes(string $notes)
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }
}

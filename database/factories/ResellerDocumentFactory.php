<?php

namespace Database\Factories;

use App\Models\ResellerDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResellerDocumentFactory extends Factory
{
    protected $model = ResellerDocument::class;

    public function definition()
    {
        $types = ['identity', 'selfie', 'business_proof'];
        $type = $this->faker->randomElement($types);

        return [
            'user_id' => User::factory(),
            'document_type' => $type,
            'file_path' => 'reseller-documents/' . $this->faker->uuid() . '.jpg',
            'file_name' => $this->faker->word() . '.jpg',
            'file_size' => $this->faker->numberBetween(100000, 2000000), // 100KB - 2MB
            'mime_type' => 'image/jpeg',
            'status' => 'pending',
        ];
    }

    public function identity()
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'identity',
        ]);
    }

    public function selfie()
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'selfie',
        ]);
    }

    public function businessProof()
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'business_proof',
        ]);
    }

    public function pdf()
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'reseller-documents/' . $this->faker->uuid() . '.pdf',
            'file_name' => $this->faker->word() . '.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function approved()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function rejected()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'notes' => 'Test rejection reason',
        ]);
    }
}

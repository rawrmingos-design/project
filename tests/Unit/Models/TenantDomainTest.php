<?php

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Property 14: isDomainVerified() correctness
|--------------------------------------------------------------------------
|
| For any Tenant instance, isDomainVerified() SHALL return true if and only if
| custom_domain_status equals 'verified' AND custom_domain_verified_at is not null.
|
| **Validates: Requirements 9.3**
|
*/

test('Property 14: isDomainVerified() returns true only when status is verified AND verified_at is not null', function () {
    $statuses = [
        Tenant::DOMAIN_STATUS_PENDING,
        Tenant::DOMAIN_STATUS_VERIFYING,
        Tenant::DOMAIN_STATUS_VERIFIED,
        Tenant::DOMAIN_STATUS_FAILED,
        null,
        '',
        'unknown',
        'active',
        'invalid-status',
        'VERIFIED',
        'Verified',
    ];

    $iterations = 100;
    $faker = \Faker\Factory::create();

    for ($i = 0; $i < $iterations; $i++) {
        // Pick a random status: from the predefined list or a random string
        $status = $faker->randomElement([
            ...$statuses,
            $faker->word(),
            $faker->lexify('??????'),
        ]);

        // Pick a random verified_at: null or a random Carbon date
        $verifiedAt = $faker->randomElement([
            null,
            null,
            Carbon::parse($faker->dateTimeBetween('-2 years', 'now')),
            Carbon::now(),
            Carbon::parse($faker->dateTime()),
        ]);

        $tenant = Tenant::create([
            'name' => "Tenant {$i}",
            'subdomain' => "tenant-prop14-{$i}",
            'custom_domain_status' => $status,
            'custom_domain_verified_at' => $verifiedAt,
        ]);

        $result = $tenant->isDomainVerified();

        $expectedTrue = ($status === Tenant::DOMAIN_STATUS_VERIFIED) && ($verifiedAt !== null);

        expect($result)->toBe(
            $expectedTrue,
            "Failed for iteration {$i}: status='" . ($status ?? 'null') . "', "
            . "verified_at=" . ($verifiedAt ? $verifiedAt->toIso8601String() : 'null') . ". "
            . "Expected: " . ($expectedTrue ? 'true' : 'false') . ", Got: " . ($result ? 'true' : 'false')
        );
    }
});

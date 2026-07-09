<?php

use App\Http\Middleware\ResolveTenant;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Unit Tests: ResolveTenant Middleware Edge Cases
|--------------------------------------------------------------------------
|
| These tests verify the middleware's behavior for custom domain verification
| enforcement and subdomain resolution independence.
|
| **Validates: Requirements 6.2, 6.3, 6.4, 6.5, 6.6**
|
*/

beforeEach(function () {
    config(['app.url' => 'https://platform.test']);
});

afterEach(function () {
    app(TenantContext::class)->clear();
    app()->forgetInstance('tenant');
});

// --- Helper ---

function createTenantForMiddleware(array $attributes = []): Tenant
{
    $owner = User::factory()->create(['role' => 'Gold']);

    return Tenant::query()->create(array_merge([
        'owner_user_id' => $owner->id,
        'name' => 'Test Tenant',
        'subdomain' => 'test-shop-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ], $attributes));
}

/*
|--------------------------------------------------------------------------
| Test 1: Subdomain resolution works normally (Requirement 6.4)
|--------------------------------------------------------------------------
|
| Subdomain resolution only checks status=active, no domain verification required.
|
*/

test('subdomain resolution works normally without requiring domain verification', function () {
    $tenant = createTenantForMiddleware([
        'subdomain' => 'myshop',
        'status' => Tenant::STATUS_ACTIVE,
        'custom_domain' => null,
        'custom_domain_status' => null,
        'custom_domain_verified_at' => null,
    ]);

    $request = Request::create('https://myshop.platform.test/id', 'GET');

    app(ResolveTenant::class)->handle($request, function () use ($tenant) {
        expect(app(TenantContext::class)->has())->toBeTrue();
        expect(app(TenantContext::class)->id())->toBe($tenant->id);

        return response('ok');
    });
});

/*
|--------------------------------------------------------------------------
| Test 2: Custom domain with status=pending results in null tenant (Req 6.2)
|--------------------------------------------------------------------------
|
| When custom_domain_status is 'pending', the domain should NOT resolve.
|
*/

test('custom domain with status pending results in null tenant', function () {
    createTenantForMiddleware([
        'subdomain' => 'pending-shop',
        'custom_domain' => 'pending.example.com',
        'custom_domain_status' => Tenant::DOMAIN_STATUS_PENDING,
        'custom_domain_verified_at' => null,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $request = Request::create('https://pending.example.com/id', 'GET');

    app(ResolveTenant::class)->handle($request, function () {
        expect(app(TenantContext::class)->has())->toBeFalse();
        expect(app()->bound('tenant'))->toBeFalse();

        return response('ok');
    });
});

/*
|--------------------------------------------------------------------------
| Test 3: Custom domain with status=failed results in null tenant (Req 6.2)
|--------------------------------------------------------------------------
|
| When custom_domain_status is 'failed', the domain should NOT resolve.
|
*/

test('custom domain with status failed results in null tenant', function () {
    createTenantForMiddleware([
        'subdomain' => 'failed-shop',
        'custom_domain' => 'failed.example.com',
        'custom_domain_status' => Tenant::DOMAIN_STATUS_FAILED,
        'custom_domain_verified_at' => null,
        'custom_domain_last_error' => 'DNS verification failed',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $request = Request::create('https://failed.example.com/id', 'GET');

    app(ResolveTenant::class)->handle($request, function () {
        expect(app(TenantContext::class)->has())->toBeFalse();
        expect(app()->bound('tenant'))->toBeFalse();

        return response('ok');
    });
});

/*
|--------------------------------------------------------------------------
| Test 4: Custom domain with verified_at=null results in null tenant (Req 6.3)
|--------------------------------------------------------------------------
|
| Even if status is 'verified', a null verified_at should NOT resolve.
|
*/

test('custom domain with verified_at null results in null tenant', function () {
    createTenantForMiddleware([
        'subdomain' => 'noverify-shop',
        'custom_domain' => 'noverify.example.com',
        'custom_domain_status' => Tenant::DOMAIN_STATUS_VERIFIED,
        'custom_domain_verified_at' => null,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $request = Request::create('https://noverify.example.com/id', 'GET');

    app(ResolveTenant::class)->handle($request, function () {
        expect(app(TenantContext::class)->has())->toBeFalse();
        expect(app()->bound('tenant'))->toBeFalse();

        return response('ok');
    });
});

/*
|--------------------------------------------------------------------------
| Test 5: Verified domain with tenant status=suspended results in null (Req 6.5)
|--------------------------------------------------------------------------
|
| A fully verified domain should NOT resolve if the tenant status is not active.
|
*/

test('verified custom domain with suspended tenant results in null tenant', function () {
    createTenantForMiddleware([
        'subdomain' => 'suspended-shop',
        'custom_domain' => 'suspended.example.com',
        'custom_domain_status' => Tenant::DOMAIN_STATUS_VERIFIED,
        'custom_domain_verified_at' => now(),
        'status' => Tenant::STATUS_SUSPENDED,
    ]);

    $request = Request::create('https://suspended.example.com/id', 'GET');

    app(ResolveTenant::class)->handle($request, function () {
        expect(app(TenantContext::class)->has())->toBeFalse();
        expect(app()->bound('tenant'))->toBeFalse();

        return response('ok');
    });
});

/*
|--------------------------------------------------------------------------
| Test 6: Unverified custom domain does NOT fall through to subdomain (Req 6.2, 6.6)
|--------------------------------------------------------------------------
|
| When a custom domain is claimed but not verified, the middleware should NOT
| attempt to resolve via subdomain. It returns null instead of falling through.
|
*/

test('unverified custom domain does not fall through to subdomain resolution', function () {
    // Create a tenant whose subdomain matches what would be extracted from the custom domain
    // if fall-through were allowed
    $tenant = createTenantForMiddleware([
        'subdomain' => 'unverified',
        'custom_domain' => 'unverified.platform.test',
        'custom_domain_status' => Tenant::DOMAIN_STATUS_PENDING,
        'custom_domain_verified_at' => null,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    // Request to the custom domain (which also happens to be a subdomain of platform.test)
    $request = Request::create('https://unverified.platform.test/id', 'GET');

    app(ResolveTenant::class)->handle($request, function () {
        // Should NOT resolve — even though 'unverified' subdomain exists and is active,
        // since the host matched as a custom_domain first, it blocks fall-through.
        expect(app(TenantContext::class)->has())->toBeFalse();
        expect(app()->bound('tenant'))->toBeFalse();

        return response('ok');
    });
});


/*
|--------------------------------------------------------------------------
| PROPERTY-BASED TEST: Property 11 - Middleware resolves only verified active tenants
|--------------------------------------------------------------------------
|
| For any HTTP request whose host matches a tenant's custom domain, the ResolveTenant
| middleware SHALL resolve to that tenant if and only if:
| - custom_domain_status === 'verified'
| - custom_domain_verified_at is not null
| - tenant.status === 'active'
|
| **Validates: Requirements 6.1, 6.2, 6.3, 6.5**
|
*/

test('property 11: middleware resolves only verified active tenants', function () {
    $faker = \Faker\Factory::create();

    $statuses = [
        Tenant::STATUS_ACTIVE,
        Tenant::STATUS_SUSPENDED,
        Tenant::STATUS_CANCELLED,
        Tenant::STATUS_PENDING_PAYMENT,
    ];

    $domainStatuses = [
        Tenant::DOMAIN_STATUS_PENDING,
        Tenant::DOMAIN_STATUS_VERIFYING,
        Tenant::DOMAIN_STATUS_VERIFIED,
        Tenant::DOMAIN_STATUS_FAILED,
        null,
    ];

    for ($i = 0; $i < 100; $i++) {
        // Generate random tenant state
        $status = $faker->randomElement($statuses);
        $domainStatus = $faker->randomElement($domainStatuses);
        $hasVerifiedAt = $faker->boolean(50);
        $verifiedAt = $hasVerifiedAt ? now()->subDays($faker->numberBetween(1, 365)) : null;

        // Generate a unique custom domain for this iteration
        $customDomain = strtolower($faker->lexify('????????')) . "-{$i}.example.com";

        // Create the tenant with random state
        $tenant = createTenantForMiddleware([
            'subdomain' => "pbt-{$i}-" . uniqid(),
            'custom_domain' => $customDomain,
            'status' => $status,
            'custom_domain_status' => $domainStatus,
            'custom_domain_verified_at' => $verifiedAt,
        ]);

        // Create a request with the Host header matching the tenant's custom domain
        $request = Request::create("https://{$customDomain}/some-path", 'GET');

        // Run the middleware and capture what was resolved
        $resolvedTenant = null;

        app(ResolveTenant::class)->handle($request, function () use (&$resolvedTenant) {
            $resolvedTenant = app()->bound('tenant') ? app('tenant') : null;

            return response('OK');
        });

        // Determine expected resolution: tenant resolves ONLY when ALL conditions are met
        $shouldResolve = (
            $status === Tenant::STATUS_ACTIVE
            && $domainStatus === Tenant::DOMAIN_STATUS_VERIFIED
            && $verifiedAt !== null
        );

        if ($shouldResolve) {
            expect($resolvedTenant)->not->toBeNull(
                "Property 11 violated (iteration {$i}): Tenant SHOULD be resolved when "
                . "status='{$status}', domain_status='{$domainStatus}', verified_at is set. "
                . "Domain: '{$customDomain}'"
            );
            expect($resolvedTenant->id)->toBe(
                $tenant->id,
                "Property 11 violated (iteration {$i}): Resolved tenant ID mismatch."
            );
        } else {
            expect($resolvedTenant)->toBeNull(
                "Property 11 violated (iteration {$i}): Tenant should NOT be resolved when "
                . "status='{$status}', domain_status='" . ($domainStatus ?? 'null') . "', "
                . "verified_at=" . ($verifiedAt ? $verifiedAt->toDateTimeString() : 'null') . ". "
                . "Domain: '{$customDomain}'"
            );
        }

        // Clean up the tenant for the next iteration to avoid domain conflicts
        $tenant->forceDelete();
    }
})->group('property-test', 'custom-domain-provisioning');

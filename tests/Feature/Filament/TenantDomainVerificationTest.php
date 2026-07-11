<?php

use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Contracts\DnsResolverInterface;
use App\Tenancy\TenantDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

uses(AdminTestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Filament Domain Verification Integration Tests
|--------------------------------------------------------------------------
|
| Tests for the Filament admin panel domain verification UI:
| - "Domain Verification" section visibility logic
| - "Verify Domain" action success/failure notifications
| - TenantsTable badge column rendering
|
| **Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5, 8.6**
|
*/

// --- Helper: create and authenticate admin user ---
function createAuthenticatedAdmin(): User
{
    $admin = User::factory()->create(['role' => 'Admin']);
    test()->actingAs($admin);

    return $admin;
}

// --- Helper: create tenant with domain ---
function createTenantWithDomain(array $overrides = []): Tenant
{
    return Tenant::create(array_merge([
        'name' => 'Test Tenant',
        'subdomain' => 'test-tenant-' . uniqid(),
        'custom_domain' => 'shop.example.com',
        'custom_domain_status' => Tenant::DOMAIN_STATUS_PENDING,
        'custom_domain_verification_token' => 'topupengine-verify-' . bin2hex(random_bytes(16)),
        'status' => Tenant::STATUS_ACTIVE,
    ], $overrides));
}

/*
|--------------------------------------------------------------------------
| Test: "Domain Verification" section visible when tenant has custom_domain
|--------------------------------------------------------------------------
| Requirements: 8.1
*/
test('Domain Verification section is visible when tenant has custom_domain', function () {
    createAuthenticatedAdmin();
    $tenant = createTenantWithDomain();

    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->assertSee('Domain Verification')
        ->assertSee('Verification Token')
        ->assertSee('DNS Setup Instructions');
});

/*
|--------------------------------------------------------------------------
| Test: "Domain Verification" section NOT visible when no custom_domain
|--------------------------------------------------------------------------
| Requirements: 8.2
*/
test('Domain Verification section is NOT visible when tenant has no custom_domain', function () {
    createAuthenticatedAdmin();
    $tenant = Tenant::create([
        'name' => 'No Domain Tenant',
        'subdomain' => 'no-domain-' . uniqid(),
        'custom_domain' => null,
        'custom_domain_status' => null,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->assertDontSee('Verification Token')
        ->assertDontSee('DNS Setup Instructions');
});

/*
|--------------------------------------------------------------------------
| Test: "Verify Domain" action shows success notification on verified
|--------------------------------------------------------------------------
| Requirements: 8.4, 8.5
*/
test('Verify Domain action calls TenantDomainService and shows success notification on verified', function () {
    createAuthenticatedAdmin();

    $token = 'topupengine-verify-' . bin2hex(random_bytes(16));
    $tenant = createTenantWithDomain([
        'custom_domain' => 'verified.example.com',
        'custom_domain_status' => Tenant::DOMAIN_STATUS_PENDING,
        'custom_domain_verification_token' => $token,
    ]);

    // Mock DnsResolverInterface to return matching token
    $mockResolver = Mockery::mock(DnsResolverInterface::class);
    $mockResolver->shouldReceive('getTxtRecords')
        ->with('verified.example.com', 10)
        ->andReturn([$token]);

    app()->instance(DnsResolverInterface::class, $mockResolver);

    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->assertActionVisible('verify_domain')
        ->callAction('verify_domain')
        ->assertNotified('Domain verification passed');

    $tenant->refresh();
    expect($tenant->custom_domain_status)->toBe(Tenant::DOMAIN_STATUS_VERIFIED);
    expect($tenant->custom_domain_verified_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Test: "Verify Domain" action shows danger notification on failure
|--------------------------------------------------------------------------
| Requirements: 8.4, 8.6
*/
test('Verify Domain action shows danger notification on failure', function () {
    createAuthenticatedAdmin();

    $token = 'topupengine-verify-' . bin2hex(random_bytes(16));
    $tenant = createTenantWithDomain([
        'custom_domain' => 'failing.example.com',
        'custom_domain_status' => Tenant::DOMAIN_STATUS_FAILED,
        'custom_domain_verification_token' => $token,
    ]);

    // Mock DnsResolverInterface to return non-matching records
    $mockResolver = Mockery::mock(DnsResolverInterface::class);
    $mockResolver->shouldReceive('getTxtRecords')
        ->with('failing.example.com', 10)
        ->andReturn(['some-other-txt-value', 'v=spf1 include:example.com ~all']);

    app()->instance(DnsResolverInterface::class, $mockResolver);

    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->assertActionVisible('verify_domain')
        ->callAction('verify_domain')
        ->assertNotified('Domain verification failed');

    $tenant->refresh();
    expect($tenant->custom_domain_status)->toBe(Tenant::DOMAIN_STATUS_FAILED);
    expect($tenant->custom_domain_last_error)->toContain('TXT record not found');
});

/*
|--------------------------------------------------------------------------
| Test: "Verify Domain" action hidden when domain is verified
|--------------------------------------------------------------------------
| Requirements: 8.4
*/
test('Verify Domain action is hidden when domain is already verified', function () {
    createAuthenticatedAdmin();

    $tenant = createTenantWithDomain([
        'custom_domain_status' => Tenant::DOMAIN_STATUS_VERIFIED,
        'custom_domain_verified_at' => now(),
    ]);

    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->assertActionHidden('verify_domain');
});

/*
|--------------------------------------------------------------------------
| Test: TenantsTable shows custom_domain_status badge column
|--------------------------------------------------------------------------
| Requirements: 8.3
*/
test('TenantsTable shows custom_domain_status badge column', function () {
    createAuthenticatedAdmin();

    createTenantWithDomain([
        'custom_domain_status' => Tenant::DOMAIN_STATUS_VERIFIED,
        'custom_domain_verified_at' => now(),
    ]);

    Livewire::test(ListTenants::class)
        ->assertCanRenderTableColumn('custom_domain_status')
        ->assertTableColumnExists('custom_domain_status');
});

test('Tenant form rejects reserved custom domains', function () {
    createAuthenticatedAdmin();

    config(['app.url' => 'https://istanatopup.test']);
    putenv('FILAMENT_ADMIN_DOMAIN=admin.istanatopup.test');

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name' => 'Reserved Domain Store',
            'subdomain' => 'reserved-domain-store',
            'custom_domain' => 'admin.istanatopup.test',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ])
        ->call('create')
        ->assertHasFormErrors(['custom_domain']);

    expect(Tenant::query()->where('custom_domain', 'admin.istanatopup.test')->exists())->toBeFalse();
});

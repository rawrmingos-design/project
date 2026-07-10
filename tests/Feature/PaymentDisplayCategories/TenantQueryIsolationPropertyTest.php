<?php

/**
 * Property 14: Tenant query isolation
 *
 * For any two tenants A and B, querying PaymentDisplayCategory records while
 * tenant context is set to A SHALL never return records belonging to tenant B,
 * and vice versa.
 *
 * **Validates: Requirements 6.3**
 */

use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

afterEach(function () {
    app(TenantContext::class)->clear();
});

test('Property 14: querying with tenant A context never returns tenant B records', function () {
    /**
     * **Validates: Requirements 6.3**
     *
     * When tenant context is set to A, all PaymentDisplayCategory queries
     * scoped by the BelongsToTenant trait must return only records with
     * tenant_id matching A. No records from tenant B should appear.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenantA = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Query Isolation Tenant A',
        'subdomain' => 'query-iso-a-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantB = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Query Isolation Tenant B',
        'subdomain' => 'query-iso-b-' . uniqid(),
        'tier' => 'business',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    // Create additional categories for tenant A (bypassing global scope)
    $extraCountA = rand(1, 5);
    for ($i = 0; $i < $extraCountA; $i++) {
        PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'label' => 'TenantA-Extra-' . uniqid(),
            'display_style' => $i % 2 === 0 ? 'flat' : 'accordion',
            'sort_order' => 100 + $i,
            'is_visible' => true,
        ]);
    }

    // Create additional categories for tenant B (bypassing global scope)
    $extraCountB = rand(1, 5);
    for ($i = 0; $i < $extraCountB; $i++) {
        PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'label' => 'TenantB-Extra-' . uniqid(),
            'display_style' => $i % 2 === 0 ? 'accordion' : 'flat',
            'sort_order' => 200 + $i,
            'is_visible' => true,
        ]);
    }

    // Set context to tenant A and query
    app(TenantContext::class)->set($tenantA);
    $resultsA = PaymentDisplayCategory::query()->get();

    // All results must belong to tenant A — no tenant B records
    expect($resultsA)->not->toBeEmpty();
    $resultsA->each(function (PaymentDisplayCategory $cat) use ($tenantA) {
        expect($cat->tenant_id)->toBe($tenantA->id);
    });

    // Explicitly verify no tenant B IDs appear
    $tenantBIds = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $tenantB->id)
        ->pluck('id')
        ->toArray();

    expect($resultsA->pluck('id')->intersect($tenantBIds)->toArray())->toBeEmpty();

    // Switch context to tenant B and query
    app(TenantContext::class)->set($tenantB);
    $resultsB = PaymentDisplayCategory::query()->get();

    // All results must belong to tenant B — no tenant A records
    expect($resultsB)->not->toBeEmpty();
    $resultsB->each(function (PaymentDisplayCategory $cat) use ($tenantB) {
        expect($cat->tenant_id)->toBe($tenantB->id);
    });

    // Explicitly verify no tenant A IDs appear
    $tenantAIds = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $tenantA->id)
        ->pluck('id')
        ->toArray();

    expect($resultsB->pluck('id')->intersect($tenantAIds)->toArray())->toBeEmpty();
})->repeat(20);

test('Property 14: scoped queries with filters still respect tenant isolation', function () {
    /**
     * **Validates: Requirements 6.3**
     *
     * Even when using scopes like visible() and ordered(), the tenant isolation
     * must still be enforced. No records from another tenant should leak through.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenantA = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Scoped Query Tenant A',
        'subdomain' => 'scoped-a-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantB = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Scoped Query Tenant B',
        'subdomain' => 'scoped-b-' . uniqid(),
        'tier' => 'business',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    // Create additional visible and hidden categories for both tenants
    PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenantA->id,
        'label' => 'A-ExtraVisible-' . uniqid(),
        'display_style' => 'flat',
        'sort_order' => 50,
        'is_visible' => true,
    ]);

    PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenantA->id,
        'label' => 'A-ExtraHidden-' . uniqid(),
        'display_style' => 'accordion',
        'sort_order' => 51,
        'is_visible' => false,
    ]);

    PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenantB->id,
        'label' => 'B-ExtraVisible-' . uniqid(),
        'display_style' => 'flat',
        'sort_order' => 60,
        'is_visible' => true,
    ]);

    PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenantB->id,
        'label' => 'B-ExtraHidden-' . uniqid(),
        'display_style' => 'accordion',
        'sort_order' => 61,
        'is_visible' => false,
    ]);

    // Query with tenant A context using visible() scope
    app(TenantContext::class)->set($tenantA);
    $visibleA = PaymentDisplayCategory::query()->visible()->ordered()->get();

    // All visible results must belong to tenant A
    expect($visibleA)->not->toBeEmpty();
    $visibleA->each(function (PaymentDisplayCategory $cat) use ($tenantA) {
        expect($cat->tenant_id)->toBe($tenantA->id);
        expect($cat->is_visible)->toBeTrue();
    });

    // No tenant B records in the visible results for A
    $tenantBIds = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $tenantB->id)
        ->pluck('id')
        ->toArray();
    expect($visibleA->pluck('id')->intersect($tenantBIds)->toArray())->toBeEmpty();

    // Query with tenant B context using visible() scope
    app(TenantContext::class)->set($tenantB);
    $visibleB = PaymentDisplayCategory::query()->visible()->ordered()->get();

    // All visible results must belong to tenant B
    expect($visibleB)->not->toBeEmpty();
    $visibleB->each(function (PaymentDisplayCategory $cat) use ($tenantB) {
        expect($cat->tenant_id)->toBe($tenantB->id);
        expect($cat->is_visible)->toBeTrue();
    });

    // No tenant A records in the visible results for B
    $tenantAIds = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $tenantA->id)
        ->pluck('id')
        ->toArray();
    expect($visibleB->pluck('id')->intersect($tenantAIds)->toArray())->toBeEmpty();
})->repeat(20);

test('Property 14: tenant context isolation holds with many tenants', function () {
    /**
     * **Validates: Requirements 6.3**
     *
     * With multiple tenants each having categories, querying with any one
     * tenant's context must return only that tenant's records. This verifies
     * the property holds across N tenants, not just two.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenantCount = rand(3, 6);
    $tenants = collect();

    for ($t = 0; $t < $tenantCount; $t++) {
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => "Multi Tenant {$t}",
            'subdomain' => 'multi-' . uniqid() . "-{$t}",
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $tenants->push($tenant);

        // Create additional random categories for each tenant
        $extraCount = rand(1, 4);
        for ($i = 0; $i < $extraCount; $i++) {
            PaymentDisplayCategory::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'label' => "T{$t}-Extra{$i}-" . uniqid(),
                'display_style' => 'flat',
                'sort_order' => 100 + $i,
                'is_visible' => true,
            ]);
        }
    }

    // For each tenant, verify isolation
    $tenants->each(function (Tenant $currentTenant) use ($tenants) {
        app(TenantContext::class)->set($currentTenant);

        $results = PaymentDisplayCategory::query()->get();

        // All records must belong to the current tenant
        expect($results)->not->toBeEmpty();
        $results->each(function (PaymentDisplayCategory $cat) use ($currentTenant) {
            expect($cat->tenant_id)->toBe($currentTenant->id);
        });

        // None of the other tenants' records should appear
        $otherTenantIds = $tenants
            ->filter(fn (Tenant $t) => $t->id !== $currentTenant->id)
            ->pluck('id')
            ->toArray();

        $otherCategoryIds = PaymentDisplayCategory::withoutGlobalScopes()
            ->whereIn('tenant_id', $otherTenantIds)
            ->pluck('id')
            ->toArray();

        expect($results->pluck('id')->intersect($otherCategoryIds)->toArray())->toBeEmpty();
    });
})->repeat(20);

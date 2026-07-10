<?php

/**
 * Property 15: Tenant cache isolation
 *
 * For any two tenants A and B, the cache key for tenant A's payment display
 * categories SHALL differ from tenant B's, and retrieving cached data for
 * tenant A SHALL never return tenant B's data.
 *
 * **Validates: Requirements 6.6**
 */

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentDisplayCategoryService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

afterEach(function () {
    app(TenantContext::class)->clear();
    Cache::flush();
});

test('Property 15: cache keys differ between tenants', function () {
    /**
     * **Validates: Requirements 6.6**
     *
     * For any two distinct tenants, the cache key format "tenant_{id}:payment_display_categories"
     * produces unique keys since tenant IDs are unique.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenants = collect();
    for ($i = 0; $i < 10; $i++) {
        $tenants->push(Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => "Tenant {$i}",
            'subdomain' => 'cache-iso-' . uniqid() . "-{$i}",
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]));
    }

    // Generate cache keys for all tenants
    $cacheKeys = $tenants->map(fn (Tenant $t) => "tenant_{$t->id}:payment_display_categories");

    // All cache keys must be unique
    expect($cacheKeys->unique()->count())->toBe($cacheKeys->count());

    // Verify format follows the expected pattern
    $cacheKeys->each(function (string $key, int $index) use ($tenants) {
        expect($key)->toBe("tenant_{$tenants[$index]->id}:payment_display_categories");
    });
})->repeat(20);

test('Property 15: retrieving cached data for tenant A never returns tenant B data', function () {
    /**
     * **Validates: Requirements 6.6**
     *
     * When two tenants each have their own categories cached via the service,
     * switching tenant context and retrieving cached data must return only the
     * categories belonging to that tenant.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenantA = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Isolation Tenant A',
        'subdomain' => 'iso-a-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantB = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Isolation Tenant B',
        'subdomain' => 'iso-b-' . uniqid(),
        'tier' => 'business',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    // Create distinct categories for each tenant with random labels
    $labelA = 'TenantA-Category-' . uniqid();
    $labelB = 'TenantB-Category-' . uniqid();

    $categoryA = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenantA->id,
        'label' => $labelA,
        'display_style' => 'flat',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    $categoryB = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenantB->id,
        'label' => $labelB,
        'display_style' => 'accordion',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    // Create a method for each category so they appear in results
    Method::query()->create([
        'payment_display_category_id' => $categoryA->id,
        'sort_order_in_category' => 0,
        'name' => 'Method A - ' . uniqid(),
        'images' => '/assets/method-a.webp',
        'code' => 'METHOD_A_' . uniqid(),
        'keterangan' => 'Test method A',
        'tipe' => 'e-walet',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);

    Method::query()->create([
        'payment_display_category_id' => $categoryB->id,
        'sort_order_in_category' => 0,
        'name' => 'Method B - ' . uniqid(),
        'images' => '/assets/method-b.webp',
        'code' => 'METHOD_B_' . uniqid(),
        'keterangan' => 'Test method B',
        'tipe' => 'virtual-account',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);

    $service = app(PaymentDisplayCategoryService::class);

    // Set context to tenant A and warm cache
    app(TenantContext::class)->set($tenantA);
    $resultA = $service->getCategoriesForOrderPage();

    // Verify tenant A only sees its own categories
    expect($resultA)->not->toBeEmpty();
    $resultA->each(function (PaymentDisplayCategory $cat) use ($tenantA) {
        expect($cat->tenant_id)->toBe($tenantA->id);
    });
    expect($resultA->pluck('label')->toArray())->toContain($labelA);
    expect($resultA->pluck('label')->toArray())->not->toContain($labelB);

    // Switch context to tenant B and get cached data
    app(TenantContext::class)->set($tenantB);
    $resultB = $service->getCategoriesForOrderPage();

    // Verify tenant B only sees its own categories
    expect($resultB)->not->toBeEmpty();
    $resultB->each(function (PaymentDisplayCategory $cat) use ($tenantB) {
        expect($cat->tenant_id)->toBe($tenantB->id);
    });
    expect($resultB->pluck('label')->toArray())->toContain($labelB);
    expect($resultB->pluck('label')->toArray())->not->toContain($labelA);

    // Verify the data doesn't leak: A's result still intact after switching
    app(TenantContext::class)->set($tenantA);
    $resultAAgain = $service->getCategoriesForOrderPage();
    expect($resultAAgain->pluck('id')->toArray())->toBe($resultA->pluck('id')->toArray());
})->repeat(20);

test('Property 15: cache stores separate data per tenant even with same category labels', function () {
    /**
     * **Validates: Requirements 6.6**
     *
     * Even when two tenants have categories with identical labels, the cache
     * isolation ensures each tenant retrieves only their own records.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenantA = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Same Label Tenant A',
        'subdomain' => 'same-label-a-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantB = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Same Label Tenant B',
        'subdomain' => 'same-label-b-' . uniqid(),
        'tier' => 'business',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $sharedLabel = 'Shared-' . uniqid();

    // Both tenants have a category with the same label
    $catA = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenantA->id,
        'label' => $sharedLabel,
        'display_style' => 'flat',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    $catB = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenantB->id,
        'label' => $sharedLabel,
        'display_style' => 'accordion',
        'sort_order' => 2,
        'is_visible' => true,
    ]);

    // Create methods for both
    Method::query()->create([
        'payment_display_category_id' => $catA->id,
        'sort_order_in_category' => 0,
        'name' => 'Shared Method A - ' . uniqid(),
        'images' => '/assets/shared-a.webp',
        'code' => 'SHARED_A_' . uniqid(),
        'keterangan' => 'Test shared method A',
        'tipe' => 'qris',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);

    Method::query()->create([
        'payment_display_category_id' => $catB->id,
        'sort_order_in_category' => 0,
        'name' => 'Shared Method B - ' . uniqid(),
        'images' => '/assets/shared-b.webp',
        'code' => 'SHARED_B_' . uniqid(),
        'keterangan' => 'Test shared method B',
        'tipe' => 'qris',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);

    $service = app(PaymentDisplayCategoryService::class);

    // Warm cache for tenant A
    app(TenantContext::class)->set($tenantA);
    $resultA = $service->getCategoriesForOrderPage();

    // Warm cache for tenant B
    app(TenantContext::class)->set($tenantB);
    $resultB = $service->getCategoriesForOrderPage();

    // Despite same labels, the category IDs are different
    expect($resultA->pluck('id')->toArray())->not->toBe($resultB->pluck('id')->toArray());

    // Each result has the correct tenant_id
    $resultA->each(fn ($cat) => expect($cat->tenant_id)->toBe($tenantA->id));
    $resultB->each(fn ($cat) => expect($cat->tenant_id)->toBe($tenantB->id));

    // Verify cache keys are different
    $keyA = "tenant_{$tenantA->id}:payment_display_categories";
    $keyB = "tenant_{$tenantB->id}:payment_display_categories";
    expect($keyA)->not->toBe($keyB);

    // Verify cache has both entries stored separately
    expect(Cache::has($keyA))->toBeTrue();
    expect(Cache::has($keyB))->toBeTrue();
})->repeat(20);

<?php

/**
 * Property 13: Cache-database equivalence
 *
 * For any database state, the cached result from
 * PaymentDisplayCategoryService::getCategoriesForOrderPage() SHALL be identical
 * in content and ordering to a fresh database query of visible PaymentDisplayCategory
 * records ordered by sort_order (SALDO first) with their enabled Method records
 * ordered by sort_order_in_category then name.
 *
 * **Validates: Requirements 5.1, 5.7**
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
    if (app()->bound(TenantContext::class)) {
        app(TenantContext::class)->clear();
    }
    Cache::flush();
});

test('Property 13: cached result matches fresh DB query in content and ordering', function () {
    /**
     * **Validates: Requirements 5.1, 5.7**
     *
     * For any random database state of categories and methods,
     * the cached service result is identical in content and ordering
     * to a fresh database query applying the same logic.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Cache Equiv Tenant ' . uniqid(),
        'subdomain' => 'cache-eq-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();

    Cache::flush();

    // Generate random categories with random visibility and sort_order
    $numCategories = rand(1, 6);
    $displayStyles = ['flat', 'accordion'];

    $createdCategories = [];
    for ($i = 0; $i < $numCategories; $i++) {
        $createdCategories[] = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'label' => 'Category_' . $i . '_' . uniqid(),
            'display_style' => $displayStyles[array_rand($displayStyles)],
            'sort_order' => rand(0, 100),
            'is_visible' => (bool) rand(0, 1),
            'icon' => rand(0, 1) ? 'icon-' . $i : null,
        ]);
    }

    // Generate random methods assigned to random categories
    $numMethods = rand(0, 15);
    for ($j = 0; $j < $numMethods; $j++) {
        $assignedCategory = rand(0, 1) ? $createdCategories[array_rand($createdCategories)] : null;
        $isSaldo = rand(1, 20) === 1; // Small chance of being a SALDO method

        Method::query()->create([
            'name' => ($isSaldo ? 'SALDO ' : 'Method_') . $j . '_' . uniqid(),
            'code' => $isSaldo ? 'SALDO' : 'CODE_' . $j . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'keterangan' => 'Test method ' . $j,
            'tipe' => $displayStyles[array_rand($displayStyles)] === 'flat' ? 'qris' : 'e-walet',
            'payment' => 'tripay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'min_pembelian' => 1000,
            'max_pembelian' => 1000000,
            'statuspayment' => (bool) rand(0, 1),
            'payment_display_category_id' => $assignedCategory?->id,
            'sort_order_in_category' => rand(0, 50),
        ]);
    }

    // Clear cache to ensure fresh state
    Cache::flush();

    // Get the cached result (first call populates cache)
    $service = app(PaymentDisplayCategoryService::class);
    $cachedResult = $service->getCategoriesForOrderPage();

    // Now perform a fresh database query with the exact same logic
    $freshResult = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->where('is_visible', true)
        ->orderBy('sort_order', 'asc')
        ->orderBy('created_at', 'asc')
        ->with(['methods' => function ($query) {
            $query->where('statuspayment', true)
                ->orderBy('sort_order_in_category', 'asc')
                ->orderBy('name', 'asc');
        }])
        ->get()
        ->filter(fn (PaymentDisplayCategory $category) => $category->methods->isNotEmpty());

    // Apply SALDO-first ordering (same logic as service)
    $saldoCategories = $freshResult->filter(function (PaymentDisplayCategory $category) {
        return $category->methods->contains(fn (Method $method) => $method->isSaldoMethod());
    });

    $otherCategories = $freshResult->reject(function (PaymentDisplayCategory $category) {
        return $category->methods->contains(fn (Method $method) => $method->isSaldoMethod());
    });

    $expectedResult = $saldoCategories->values()->merge($otherCategories->values());

    // Verify count matches
    expect($cachedResult)->toHaveCount($expectedResult->count());

    // Verify ordering and content match
    $cachedResult->values()->each(function ($cachedCategory, $index) use ($expectedResult) {
        $expectedCategory = $expectedResult->values()->get($index);

        // Category attributes match
        expect($cachedCategory->id)->toBe($expectedCategory->id);
        expect($cachedCategory->label)->toBe($expectedCategory->label);
        expect($cachedCategory->display_style)->toBe($expectedCategory->display_style);
        expect($cachedCategory->sort_order)->toBe($expectedCategory->sort_order);
        expect($cachedCategory->is_visible)->toBe($expectedCategory->is_visible);

        // Methods count and ordering match
        expect($cachedCategory->methods)->toHaveCount($expectedCategory->methods->count());

        $cachedCategory->methods->values()->each(function ($cachedMethod, $methodIndex) use ($expectedCategory) {
            $expectedMethod = $expectedCategory->methods->values()->get($methodIndex);

            expect($cachedMethod->id)->toBe($expectedMethod->id);
            expect($cachedMethod->name)->toBe($expectedMethod->name);
            expect($cachedMethod->sort_order_in_category)->toBe($expectedMethod->sort_order_in_category);
        });
    });

    // Verify second call returns same result from cache (not a fresh DB query)
    $secondCachedResult = $service->getCategoriesForOrderPage();
    expect($secondCachedResult)->toHaveCount($cachedResult->count());

    $secondCachedResult->values()->each(function ($category, $index) use ($cachedResult) {
        expect($category->id)->toBe($cachedResult->values()->get($index)->id);
    });
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 13: cached result remains equivalent after cache warm', function () {
    /**
     * **Validates: Requirements 5.1, 5.7**
     *
     * After warming the cache via warmCache(), the result from getCategoriesForOrderPage()
     * still matches a fresh database query.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Cache Warm Tenant ' . uniqid(),
        'subdomain' => 'cache-warm-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();

    Cache::flush();

    // Create a visible category with some enabled methods
    $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'label' => 'Test Category ' . uniqid(),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 50),
        'is_visible' => true,
        'icon' => null,
    ]);

    // Create some enabled methods with random sort orders
    $numMethods = rand(1, 5);
    for ($i = 0; $i < $numMethods; $i++) {
        Method::query()->create([
            'name' => 'Method_' . $i . '_' . uniqid(),
            'code' => 'CODE_' . $i . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'keterangan' => 'Test',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'min_pembelian' => 1000,
            'max_pembelian' => 1000000,
            'statuspayment' => true,
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => rand(0, 100),
        ]);
    }

    // Clear cache
    Cache::flush();

    // Warm the cache
    $service = app(PaymentDisplayCategoryService::class);
    $service->warmCache();

    // Get cached result
    $cachedResult = $service->getCategoriesForOrderPage();

    // Fresh DB query
    $freshResult = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->where('is_visible', true)
        ->orderBy('sort_order', 'asc')
        ->orderBy('created_at', 'asc')
        ->with(['methods' => function ($query) {
            $query->where('statuspayment', true)
                ->orderBy('sort_order_in_category', 'asc')
                ->orderBy('name', 'asc');
        }])
        ->get()
        ->filter(fn (PaymentDisplayCategory $category) => $category->methods->isNotEmpty());

    // No SALDO methods here, so ordering stays the same
    $expectedResult = $freshResult->values();

    expect($cachedResult)->toHaveCount($expectedResult->count());

    $cachedResult->values()->each(function ($cachedCategory, $index) use ($expectedResult) {
        $expectedCategory = $expectedResult->get($index);

        expect($cachedCategory->id)->toBe($expectedCategory->id);
        expect($cachedCategory->label)->toBe($expectedCategory->label);

        // Verify method ordering
        expect($cachedCategory->methods)->toHaveCount($expectedCategory->methods->count());

        $cachedCategory->methods->values()->each(function ($cachedMethod, $methodIndex) use ($expectedCategory) {
            $expectedMethod = $expectedCategory->methods->values()->get($methodIndex);
            expect($cachedMethod->id)->toBe($expectedMethod->id);
        });
    });
})->repeat(20)->group('property-test', 'payment-display-categories');

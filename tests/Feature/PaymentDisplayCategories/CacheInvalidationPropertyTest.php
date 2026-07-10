<?php

/**
 * Property 12: Cache invalidation on model mutations
 *
 * For any create, update, or delete operation on a PaymentDisplayCategory or Method record,
 * the tenant-scoped category cache SHALL be invalidated (the cache key SHALL no longer exist
 * after the mutation).
 *
 * **Validates: Requirements 5.4, 5.5**
 */

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Use withoutEvents to prevent the TenantObserver from triggering provisioning
    // which would interfere with our cache invalidation assertions
    $this->tenant = Tenant::withoutEvents(function () {
        return Tenant::create([
            'name' => 'Cache Invalidation Test Tenant',
            'subdomain' => 'cache-inv-' . uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    });

    $tenantContext = app(TenantContext::class);
    $tenantContext->set($this->tenant);

    $this->cacheKey = "tenant_{$this->tenant->id}:payment_display_categories";
});

afterEach(function () {
    app(TenantContext::class)->clear();
});

// --- PaymentDisplayCategory mutations ---

test('Property 12: creating a PaymentDisplayCategory invalidates the tenant cache', function () {
    // Seed the cache with dummy data
    Cache::put($this->cacheKey, ['cached_data' => true], 300);
    expect(Cache::has($this->cacheKey))->toBeTrue();

    // Create a category
    PaymentDisplayCategory::create([
        'label' => 'Category ' . uniqid(),
        'display_style' => 'flat',
        'sort_order' => rand(0, 999),
        'is_visible' => true,
        'tenant_id' => $this->tenant->id,
    ]);

    // Cache must be invalidated
    expect(Cache::has($this->cacheKey))->toBeFalse();
})->repeat(20);

test('Property 12: updating a PaymentDisplayCategory invalidates the tenant cache', function () {
    // Create a category first (this will also invalidate cache, so we re-seed after)
    $category = PaymentDisplayCategory::create([
        'label' => 'Update Test ' . uniqid(),
        'display_style' => 'flat',
        'sort_order' => rand(0, 999),
        'is_visible' => true,
        'tenant_id' => $this->tenant->id,
    ]);

    // Re-seed the cache
    Cache::put($this->cacheKey, ['cached_data' => true], 300);
    expect(Cache::has($this->cacheKey))->toBeTrue();

    // Update a random attribute
    $mutations = ['label', 'display_style', 'sort_order', 'is_visible', 'icon'];
    $attribute = $mutations[array_rand($mutations)];

    match ($attribute) {
        'label' => $category->update(['label' => 'Updated ' . uniqid()]),
        'display_style' => $category->update(['display_style' => $category->display_style === 'flat' ? 'accordion' : 'flat']),
        'sort_order' => $category->update(['sort_order' => rand(0, 999)]),
        'is_visible' => $category->update(['is_visible' => !$category->is_visible]),
        'icon' => $category->update(['icon' => 'icon-' . uniqid()]),
    };

    // Cache must be invalidated
    expect(Cache::has($this->cacheKey))->toBeFalse();
})->repeat(20);

test('Property 12: deleting a PaymentDisplayCategory invalidates the tenant cache', function () {
    // Create a category first
    $category = PaymentDisplayCategory::create([
        'label' => 'Delete Test ' . uniqid(),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 999),
        'is_visible' => (bool) rand(0, 1),
        'tenant_id' => $this->tenant->id,
    ]);

    // Re-seed the cache
    Cache::put($this->cacheKey, ['cached_data' => true], 300);
    expect(Cache::has($this->cacheKey))->toBeTrue();

    // Delete the category
    $category->delete();

    // Cache must be invalidated
    expect(Cache::has($this->cacheKey))->toBeFalse();
})->repeat(20);

// --- Method mutations ---

test('Property 12: creating a Method invalidates the tenant cache', function () {
    // Seed the cache
    Cache::put($this->cacheKey, ['cached_data' => true], 300);
    expect(Cache::has($this->cacheKey))->toBeTrue();

    // Create a method (methods table has no tenant_id column; cache invalidation uses TenantContext)
    Method::create([
        'name' => 'Method ' . uniqid(),
        'code' => 'CODE_' . strtoupper(uniqid()),
        'images' => '/assets/test.webp',
        'keterangan' => 'Test method',
        'tipe' => collect(['e-walet', 'virtual-account', 'qris', 'convenience-store'])->random(),
        'payment' => 'tripay',
        'fee_percent' => rand(0, 5),
        'fix_fee' => rand(0, 5000),
        'min_pembelian' => 1000,
        'max_pembelian' => 10000000,
        'statuspayment' => true,
    ]);

    // Cache must be invalidated
    expect(Cache::has($this->cacheKey))->toBeFalse();
})->repeat(20);

test('Property 12: updating a Method invalidates the tenant cache', function () {
    // Create a method first (methods table has no tenant_id; cache invalidation uses TenantContext)
    $method = Method::create([
        'name' => 'Update Method ' . uniqid(),
        'code' => 'UPD_' . strtoupper(uniqid()),
        'images' => '/assets/test.webp',
        'keterangan' => 'Test method',
        'tipe' => 'e-walet',
        'payment' => 'tripay',
        'fee_percent' => 0,
        'fix_fee' => 0,
        'min_pembelian' => 1000,
        'max_pembelian' => 10000000,
        'statuspayment' => true,
    ]);

    // Re-seed the cache
    Cache::put($this->cacheKey, ['cached_data' => true], 300);
    expect(Cache::has($this->cacheKey))->toBeTrue();

    // Update a random attribute
    $mutations = ['name', 'statuspayment', 'fee_percent', 'sort_order_in_category'];
    $attribute = $mutations[array_rand($mutations)];

    match ($attribute) {
        'name' => $method->update(['name' => 'Updated ' . uniqid()]),
        'statuspayment' => $method->update(['statuspayment' => !$method->statuspayment]),
        'fee_percent' => $method->update(['fee_percent' => rand(0, 10)]),
        'sort_order_in_category' => $method->update(['sort_order_in_category' => rand(0, 999)]),
    };

    // Cache must be invalidated
    expect(Cache::has($this->cacheKey))->toBeFalse();
})->repeat(20);

test('Property 12: deleting a Method invalidates the tenant cache', function () {
    // Create a method first (methods table has no tenant_id; cache invalidation uses TenantContext)
    $method = Method::create([
        'name' => 'Delete Method ' . uniqid(),
        'code' => 'DEL_' . strtoupper(uniqid()),
        'images' => '/assets/test.webp',
        'keterangan' => 'Test method',
        'tipe' => collect(['e-walet', 'virtual-account', 'qris', 'convenience-store'])->random(),
        'payment' => 'tripay',
        'fee_percent' => 0,
        'fix_fee' => 0,
        'min_pembelian' => 1000,
        'max_pembelian' => 10000000,
        'statuspayment' => true,
    ]);

    // Re-seed the cache
    Cache::put($this->cacheKey, ['cached_data' => true], 300);
    expect(Cache::has($this->cacheKey))->toBeTrue();

    // Delete the method
    $method->delete();

    // Cache must be invalidated
    expect(Cache::has($this->cacheKey))->toBeFalse();
})->repeat(20);

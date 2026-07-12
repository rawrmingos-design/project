<?php

/**
 * Regression: Global methods visible to tenants
 *
 * When a tenant context is active, methods with tenant_id = null (global/shared
 * methods that are not tied to any specific tenant) MUST still appear alongside
 * tenant-specific methods. They MUST NOT be filtered out by the methods eager-load
 * query inside PaymentDisplayCategoryService::getCategoriesForOrderPage().
 *
 * Bug: Previously, the methods eager-load used:
 *   ->where('tenant_id', $tenantId)
 * which excluded global methods (tenant_id = null) when a tenant context was active.
 *
 * Fix: Changed to:
 *   ->where(fn ($q) => $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id'))
 * so global methods are included for any tenant.
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

test('global methods (tenant_id = null) are visible when tenant context is active', function () {
    /**
     * When a tenant accesses the order page, global methods (with tenant_id = null)
     * should appear alongside any tenant-specific methods. This is the direct
     * regression test for the bug where ->where('tenant_id', $tenantId) excluded
     * global methods from the result.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenantA = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Global Method Tenant A',
        'subdomain' => 'global-method-a-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    // Update the seeded category to avoid unique constraint, or fetch it
    $categoryA = PaymentDisplayCategory::withoutGlobalScopes()
        ->firstOrCreate(
            ['tenant_id' => $tenantA->id, 'label' => 'QRIS'],
            ['display_style' => 'flat', 'sort_order' => 1, 'is_visible' => true]
        );

    $globalMethod = Method::query()->create([
        'payment_display_category_id' => $categoryA->id,
        'sort_order_in_category' => 1,
        'name' => 'QRIS Global',
        'images' => '/assets/qris.webp',
        'code' => 'QRIS_GLOBAL_' . uniqid(),
        'keterangan' => 'Global QRIS method',
        'tipe' => 'qris',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);
    if (\Illuminate\Support\Facades\Schema::hasColumn('methods', 'tenant_id')) {
        $globalMethod->update(['tenant_id' => null]);
    }

    // Set tenant context and call the service
    app(TenantContext::class)->set($tenantA);
    $service = app(PaymentDisplayCategoryService::class);
    $categories = $service->getCategoriesForOrderPage();

    // The QRIS category should appear (it has a global method)
    expect($categories)->not->toBeEmpty();

    $methodCodes = $categories->flatMap(fn ($cat) => $cat->methods->pluck('code'))->toArray();
    expect($methodCodes)->toContain($globalMethod->code);
});

test('global methods coexist with tenant-specific methods in the same category', function () {
    /**
     * When a category has both a global method (tenant_id = null) and a
     * tenant-specific method, both should appear when that tenant is active.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Mixed Methods Tenant',
        'subdomain' => 'mixed-methods-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $category = PaymentDisplayCategory::withoutGlobalScopes()
        ->firstOrCreate(
            ['tenant_id' => $tenant->id, 'label' => 'E-Wallet'],
            ['display_style' => 'accordion', 'sort_order' => 2, 'is_visible' => true]
        );

    $globalMethod = Method::query()->create([
        'payment_display_category_id' => $category->id,
        'sort_order_in_category' => 1,
        'name' => 'ShopeePay Global',
        'images' => '/assets/shopeepay.webp',
        'code' => 'SPGLOBAL_' . uniqid(),
        'keterangan' => 'Global ShopeePay',
        'tipe' => 'e-walet',
        'payment' => 'duitku',
        'statuspayment' => true,
    ]);
    if (\Illuminate\Support\Facades\Schema::hasColumn('methods', 'tenant_id')) {
        $globalMethod->update(['tenant_id' => null]);
    }

    $tenantMethod = Method::query()->create([
        'payment_display_category_id' => $category->id,
        'sort_order_in_category' => 2,
        'name' => 'ShopeePay Tenant',
        'images' => '/assets/shopeepay-t.webp',
        'code' => 'SPTENANT_' . uniqid(),
        'keterangan' => 'Tenant-specific ShopeePay',
        'tipe' => 'e-walet',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);
    if (\Illuminate\Support\Facades\Schema::hasColumn('methods', 'tenant_id')) {
        $tenantMethod->update(['tenant_id' => $tenant->id]);
    }

    app(TenantContext::class)->set($tenant);
    $categories = app(PaymentDisplayCategoryService::class)->getCategoriesForOrderPage();

    expect($categories)->not->toBeEmpty();

    $methodCodes = $categories->flatMap(fn ($cat) => $cat->methods->pluck('code'))->toArray();
    expect($methodCodes)->toContain($globalMethod->code);
    expect($methodCodes)->toContain($tenantMethod->code);
});

test('global methods are not included for another tenant in the same category', function () {
    /**
     * Tenant A and Tenant B each have their own category. Tenant A's category
     * has a global method. When Tenant B is active, that global method should
     * NOT appear because it is assigned to Tenant A's category (not Tenant B's).
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenantA = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Owner Tenant A',
        'subdomain' => 'owner-a-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantB = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Other Tenant B',
        'subdomain' => 'other-b-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    // Category for Tenant A
    $categoryA = PaymentDisplayCategory::withoutGlobalScopes()
        ->firstOrCreate(
            ['tenant_id' => $tenantA->id, 'label' => 'QRIS-A'],
            ['display_style' => 'flat', 'sort_order' => 1, 'is_visible' => true]
        );

    // Global method linked to Tenant A's category
    $globalMethodA = Method::query()->create([
        'payment_display_category_id' => $categoryA->id,
        'sort_order_in_category' => 1,
        'name' => 'QRIS A Global',
        'images' => '/assets/qris-a.webp',
        'code' => 'QRISGLOBALA_' . uniqid(),
        'keterangan' => 'Global QRIS for A',
        'tipe' => 'qris',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);
    if (\Illuminate\Support\Facades\Schema::hasColumn('methods', 'tenant_id')) {
        $globalMethodA->update(['tenant_id' => null]);
    }

    // Category for Tenant B with its own method
    $categoryB = PaymentDisplayCategory::withoutGlobalScopes()
        ->firstOrCreate(
            ['tenant_id' => $tenantB->id, 'label' => 'QRIS-B'],
            ['display_style' => 'flat', 'sort_order' => 1, 'is_visible' => true]
        );

    $methodB = Method::query()->create([
        'payment_display_category_id' => $categoryB->id,
        'sort_order_in_category' => 1,
        'name' => 'QRIS B',
        'images' => '/assets/qris-b.webp',
        'code' => 'QRISB_' . uniqid(),
        'keterangan' => 'Tenant B QRIS',
        'tipe' => 'qris',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);
    if (\Illuminate\Support\Facades\Schema::hasColumn('methods', 'tenant_id')) {
        $methodB->update(['tenant_id' => $tenantB->id]);
    }

    $service = app(PaymentDisplayCategoryService::class);

    // Tenant A sees its global method
    app(TenantContext::class)->set($tenantA);
    $resultA = $service->getCategoriesForOrderPage();
    $codesA = $resultA->flatMap(fn ($cat) => $cat->methods->pluck('code'))->toArray();
    expect($codesA)->toContain($globalMethodA->code);

    // Tenant B does NOT see Tenant A's method (even though it is global)
    // because it is linked to Tenant A's category which Tenant B can't see
    Cache::flush();
    app(TenantContext::class)->set($tenantB);
    $resultB = $service->getCategoriesForOrderPage();
    $codesB = $resultB->flatMap(fn ($cat) => $cat->methods->pluck('code'))->toArray();
    expect($codesB)->not->toContain($globalMethodA->code);
    expect($codesB)->toContain($methodB->code);
});

test('without tenant context, only global methods (tenant_id = null) are returned', function () {
    /**
     * Main site (no tenant context): only methods with tenant_id = null should
     * appear. Methods belonging to a specific tenant must not leak.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Some Tenant',
        'subdomain' => 'some-tenant-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    // Global category (tenant_id = null) with a global method
    $globalCategory = PaymentDisplayCategory::withoutGlobalScopes()
        ->firstOrCreate(
            ['tenant_id' => null, 'label' => 'QRIS'],
            ['display_style' => 'flat', 'sort_order' => 1, 'is_visible' => true]
        );

    $globalMethod = Method::query()->create([
        'payment_display_category_id' => $globalCategory->id,
        'sort_order_in_category' => 1,
        'name' => 'QRIS Main',
        'images' => '/assets/qris.webp',
        'code' => 'QRISMAIN_' . uniqid(),
        'keterangan' => 'Main QRIS',
        'tipe' => 'qris',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);
    if (\Illuminate\Support\Facades\Schema::hasColumn('methods', 'tenant_id')) {
        $globalMethod->update(['tenant_id' => null]);
    }

    // Tenant-specific category with a tenant method
    $tenantCategory = PaymentDisplayCategory::withoutGlobalScopes()
        ->firstOrCreate(
            ['tenant_id' => $tenant->id, 'label' => 'VA'],
            ['display_style' => 'accordion', 'sort_order' => 2, 'is_visible' => true]
        );

    $tenantMethod = Method::query()->create([
        'payment_display_category_id' => $tenantCategory->id,
        'sort_order_in_category' => 1,
        'name' => 'VA Tenant',
        'images' => '/assets/va.webp',
        'code' => 'VATENANT_' . uniqid(),
        'keterangan' => 'Tenant VA',
        'tipe' => 'virtual-account',
        'payment' => 'tripay',
        'statuspayment' => true,
    ]);
    if (\Illuminate\Support\Facades\Schema::hasColumn('methods', 'tenant_id')) {
        $tenantMethod->update(['tenant_id' => $tenant->id]);
    }

    // No tenant context (main site)
    app(TenantContext::class)->clear();
    $categories = app(PaymentDisplayCategoryService::class)->getCategoriesForOrderPage();

    $methodCodes = $categories->flatMap(fn ($cat) => $cat->methods->pluck('code'))->toArray();
    expect($methodCodes)->toContain($globalMethod->code);
    expect($methodCodes)->not->toContain($tenantMethod->code);
});

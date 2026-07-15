<?php

/**
 * Property 16: SALDO category always rendered first
 *
 * For any configuration of PaymentDisplayCategory records with any sort_order values,
 * if one category contains a Method where isSaldoMethod() returns true, that category
 * SHALL be rendered before all other categories regardless of its sort_order.
 *
 * **Validates: Requirements 7.5**
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

test('Property 16: SALDO-containing category is always first regardless of sort_order', function () {
    /**
     * **Validates: Requirements 7.5**
     *
     * For any randomized sort_order assignment across multiple categories,
     * the SALDO-containing category is always first in the result regardless of its sort_order.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'SALDO Ordering Tenant',
        'subdomain' => 'saldo-ordering-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Clean pre-provisioned tenant categories, then clear context while creating canonical catalog rows.
    PaymentDisplayCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();
    $context->clear();

    Cache::flush();

    // Generate a random number of categories (2-5)
    $numCategories = rand(2, 5);

    // Create non-SALDO categories with low sort_orders (0-10) so they'd normally come first
    $categories = [];
    for ($i = 0; $i < $numCategories - 1; $i++) {
        $categories[] = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'label' => "Category_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => rand(0, 10),
            'is_visible' => true,
        ]);
    }

    // Create SALDO category with a HIGH sort_order (500-999) so it should NOT normally be first
    $saldoCategory = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => null,
        'label' => 'SALDO_' . uniqid(),
        'display_style' => 'flat',
        'sort_order' => rand(500, 999),
        'is_visible' => true,
    ]);

    // Add at least one enabled method to each non-SALDO category
    foreach ($categories as $category) {
        Method::query()->create([
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => rand(0, 50),
            'name' => 'Method_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'CODE_' . uniqid(),
            'keterangan' => 'Test method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);
    }

    // Add a SALDO method to the SALDO category
    Method::query()->create([
        'payment_display_category_id' => $saldoCategory->id,
        'sort_order_in_category' => 0,
        'name' => 'SALDO Payment ' . uniqid(),
        'images' => '/assets/thumbnail/saldo.webp',
        'code' => 'SALDO',
        'keterangan' => 'Saldo payment',
        'tipe' => 'saldo',
        'payment' => 'saldo',
        'statuspayment' => true,
    ]);

    $context->set($tenant);
    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    // Assert: result is not empty
    expect($result)->not->toBeEmpty();

    // Assert: first category contains a SALDO method
    $firstCategory = $result->first();
    $hasSaldoMethod = $firstCategory->methods->contains(fn (Method $method) => $method->isSaldoMethod());

    expect($hasSaldoMethod)->toBeTrue(
        "First category (label: {$firstCategory->label}, sort_order: {$firstCategory->sort_order}) " .
        "should contain a SALDO method. SALDO category sort_order was {$saldoCategory->sort_order}."
    );
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 16: SALDO category first even with maximum sort_order difference', function () {
    /**
     * **Validates: Requirements 7.5**
     *
     * Even when the SALDO category has sort_order=999 and other categories have sort_order=0,
     * the SALDO category is still rendered first.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'SALDO Max Sort Tenant',
        'subdomain' => 'saldo-max-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Clean pre-provisioned tenant categories, then clear context while creating canonical catalog rows.
    PaymentDisplayCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();
    $context->clear();

    Cache::flush();

    // Create SALDO category with max sort_order
    $saldoCategory = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => null,
        'label' => 'SALDO_max_' . uniqid(),
        'display_style' => 'flat',
        'sort_order' => 999,
        'is_visible' => true,
    ]);

    // Create multiple other categories with sort_order = 0 (would normally be first)
    $numOthers = rand(1, 4);
    for ($i = 0; $i < $numOthers; $i++) {
        $otherCategory = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'label' => "Other_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        // Add a non-SALDO method
        Method::query()->create([
            'payment_display_category_id' => $otherCategory->id,
            'sort_order_in_category' => 0,
            'name' => 'Method_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'CODE_' . uniqid(),
            'keterangan' => 'Test method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);
    }

    // Add SALDO method to SALDO category
    Method::query()->create([
        'payment_display_category_id' => $saldoCategory->id,
        'sort_order_in_category' => 0,
        'name' => 'SALDO_' . uniqid(),
        'images' => '/assets/thumbnail/saldo.webp',
        'code' => 'SALDO',
        'keterangan' => 'Saldo payment',
        'tipe' => 'saldo',
        'payment' => 'saldo',
        'statuspayment' => true,
    ]);

    $context->set($tenant);
    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    expect($result)->not->toBeEmpty();

    $firstCategory = $result->first();
    $hasSaldoMethod = $firstCategory->methods->contains(fn (Method $method) => $method->isSaldoMethod());

    expect($hasSaldoMethod)->toBeTrue(
        "SALDO category (sort_order=999) should be first even when other categories have sort_order=0. " .
        "First category was: {$firstCategory->label} (sort_order={$firstCategory->sort_order})"
    );
})->repeat(20)->group('property-test', 'payment-display-categories');

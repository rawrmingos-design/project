<?php

/**
 * Property 5: Unassigned methods excluded from grouping
 *
 * For any collection of Method records where some have payment_display_category_id = null,
 * the grouping function SHALL return only methods with a non-null category assignment;
 * null-FK methods SHALL never appear in any rendered group.
 *
 * **Validates: Requirements 2.4**
 */

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
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

test('Property 5: methods with null payment_display_category_id never appear in any category group', function () {
    /**
     * **Validates: Requirements 2.4**
     *
     * For any random mix of assigned and unassigned methods,
     * getCategoriesForOrderPage() never includes a method with null FK
     * in any category's methods collection.
     */
    app(TenantContext::class)->clear();

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->whereNull('tenant_id')->delete();

    Cache::flush();

    // Create random visible categories (1-4)
    $numCategories = rand(1, 4);
    $categories = [];
    for ($i = 0; $i < $numCategories; $i++) {
        $categories[] = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'label' => "Category_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => rand(0, 100),
            'is_visible' => true,
        ]);
    }

    // Create a random mix of assigned and unassigned methods
    $numAssigned = rand(1, 6);
    $numUnassigned = rand(1, 6);

    // Track unassigned method IDs
    $unassignedMethodIds = [];

    // Create assigned methods (with a category FK)
    for ($j = 0; $j < $numAssigned; $j++) {
        $category = $categories[array_rand($categories)];
        Method::query()->create([
            'name' => 'Assigned_' . $j . '_' . uniqid(),
            'code' => 'ASSIGNED_' . $j . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'keterangan' => 'Assigned method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'min_pembelian' => 1000,
            'max_pembelian' => 1000000,
            'statuspayment' => true,
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => rand(0, 50),
        ]);
    }

    // Create unassigned methods (null FK)
    for ($k = 0; $k < $numUnassigned; $k++) {
        $unassigned = Method::query()->create([
            'name' => 'Unassigned_' . $k . '_' . uniqid(),
            'code' => 'UNASSIGNED_' . $k . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'keterangan' => 'Unassigned method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'min_pembelian' => 1000,
            'max_pembelian' => 1000000,
            'statuspayment' => true,
            'payment_display_category_id' => null,
            'sort_order_in_category' => 0,
        ]);
        $unassignedMethodIds[] = $unassigned->id;
    }

    // Get categories from the service
    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    // Collect all method IDs that appear in any category group
    $allMethodIdsInGroups = $result->flatMap(
        fn (PaymentDisplayCategory $category) => $category->methods->pluck('id')
    )->toArray();

    // Assert: none of the unassigned method IDs appear in any group
    foreach ($unassignedMethodIds as $unassignedId) {
        expect($allMethodIdsInGroups)->not->toContain(
            $unassignedId,
            "Method ID {$unassignedId} with null payment_display_category_id should NOT appear in any category group"
        );
    }

    // Also verify every method in the groups has a non-null FK
    $result->each(function (PaymentDisplayCategory $category) {
        $category->methods->each(function (Method $method) use ($category) {
            expect($method->payment_display_category_id)->not->toBeNull(
                "Method '{$method->name}' (ID: {$method->id}) in category '{$category->label}' " .
                "should have a non-null payment_display_category_id"
            );
        });
    });
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 5: when all methods are unassigned, no categories are returned', function () {
    /**
     * **Validates: Requirements 2.4**
     *
     * If every method in the system has payment_display_category_id = null,
     * getCategoriesForOrderPage() returns an empty collection since no category
     * has any methods to display.
     */
    app(TenantContext::class)->clear();

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->whereNull('tenant_id')->delete();

    Cache::flush();

    // Create some visible categories
    $numCategories = rand(1, 4);
    for ($i = 0; $i < $numCategories; $i++) {
        PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'label' => "EmptyCategory_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => rand(0, 100),
            'is_visible' => true,
        ]);
    }

    // Create methods all with null FK
    $numMethods = rand(1, 8);
    for ($j = 0; $j < $numMethods; $j++) {
        Method::query()->create([
            'name' => 'Orphan_' . $j . '_' . uniqid(),
            'code' => 'ORPHAN_' . $j . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'keterangan' => 'Orphan method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'min_pembelian' => 1000,
            'max_pembelian' => 1000000,
            'statuspayment' => true,
            'payment_display_category_id' => null,
            'sort_order_in_category' => 0,
        ]);
    }

    // Get categories from the service
    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    // Assert: no categories returned because none have assigned methods
    expect($result)->toBeEmpty(
        "When all methods have null payment_display_category_id, no categories should be returned. " .
        "Got " . $result->count() . " categories."
    );
})->repeat(20)->group('property-test', 'payment-display-categories');

<?php

/**
 * Property 9: Non-renderable categories omitted
 *
 * For any collection of PaymentDisplayCategory records, those with is_visible = false
 * OR those containing zero enabled methods SHALL be excluded from the rendered output.
 *
 * **Validates: Requirements 3.4, 3.7**
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

test('Property 9: hidden categories (is_visible=false) are excluded from getCategoriesForOrderPage', function () {
    /**
     * **Validates: Requirements 3.4**
     *
     * For any random mix of visible and hidden categories, getCategoriesForOrderPage()
     * never returns a category where is_visible = false.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Hidden Categories Tenant ' . uniqid(),
        'subdomain' => 'hidden-cat-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();

    Cache::flush();

    // Create a random mix of visible and hidden categories
    $numVisible = rand(1, 4);
    $numHidden = rand(1, 4);

    $visibleCategoryIds = [];
    $hiddenCategoryIds = [];

    // Create visible categories with at least one enabled method each
    for ($i = 0; $i < $numVisible; $i++) {
        $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'label' => "Visible_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => rand(0, 100),
            'is_visible' => true,
        ]);

        Method::query()->create([
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => 0,
            'name' => 'VisibleMethod_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'VIS_' . uniqid(),
            'keterangan' => 'Visible method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);

        $visibleCategoryIds[] = $category->id;
    }

    // Create hidden categories with enabled methods (should still be excluded)
    for ($i = 0; $i < $numHidden; $i++) {
        $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'label' => "Hidden_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => rand(0, 100),
            'is_visible' => false,
        ]);

        // Even with enabled methods, hidden categories should not appear
        Method::query()->create([
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => 0,
            'name' => 'HiddenMethod_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'HID_' . uniqid(),
            'keterangan' => 'Method in hidden category',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);

        $hiddenCategoryIds[] = $category->id;
    }

    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    $resultCategoryIds = $result->pluck('id')->toArray();

    // Assert: no hidden category appears in the result
    foreach ($hiddenCategoryIds as $hiddenId) {
        expect($resultCategoryIds)->not->toContain($hiddenId);
    }

    // Assert: all visible categories with methods are present
    foreach ($visibleCategoryIds as $visibleId) {
        expect(in_array($visibleId, $resultCategoryIds))->toBeTrue(
            "Category ID {$visibleId} with is_visible=true and enabled methods should appear in getCategoriesForOrderPage()"
        );
    }

    // Assert: every returned category has is_visible = true
    $result->each(function (PaymentDisplayCategory $category) {
        expect($category->is_visible)->toBeTrue(
            "Category '{$category->label}' (ID: {$category->id}) returned by getCategoriesForOrderPage() " .
            "should have is_visible=true"
        );
    });
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 9: categories with zero enabled methods are excluded from getCategoriesForOrderPage', function () {
    /**
     * **Validates: Requirements 3.7**
     *
     * For any visible category that has no methods with statuspayment=true
     * (either no methods at all, or all methods have statuspayment=false),
     * getCategoriesForOrderPage() should exclude that category.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Zero Methods Tenant ' . uniqid(),
        'subdomain' => 'zero-methods-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();

    Cache::flush();

    // Create categories with enabled methods (should appear)
    $numWithMethods = rand(1, 3);
    $categoriesWithMethodIds = [];

    for ($i = 0; $i < $numWithMethods; $i++) {
        $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'label' => "WithMethods_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => rand(0, 100),
            'is_visible' => true,
        ]);

        Method::query()->create([
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => 0,
            'name' => 'EnabledMethod_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'EN_' . uniqid(),
            'keterangan' => 'Enabled method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);

        $categoriesWithMethodIds[] = $category->id;
    }

    // Create categories with NO methods at all (should be excluded)
    $numEmpty = rand(1, 3);
    $emptyCategoryIds = [];

    for ($i = 0; $i < $numEmpty; $i++) {
        $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'label' => "EmptyCat_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => rand(0, 100),
            'is_visible' => true,
        ]);

        $emptyCategoryIds[] = $category->id;
    }

    // Create categories with ONLY disabled methods (statuspayment=false) — should be excluded
    $numDisabledOnly = rand(1, 3);
    $disabledOnlyCategoryIds = [];

    for ($i = 0; $i < $numDisabledOnly; $i++) {
        $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'label' => "DisabledOnly_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => rand(0, 100),
            'is_visible' => true,
        ]);

        // Add methods with statuspayment=false
        $numDisabledMethods = rand(1, 3);
        for ($j = 0; $j < $numDisabledMethods; $j++) {
            Method::query()->create([
                'payment_display_category_id' => $category->id,
                'sort_order_in_category' => $j,
                'name' => 'DisabledMethod_' . $j . '_' . uniqid(),
                'images' => '/assets/thumbnail/test.webp',
                'code' => 'DIS_' . $j . '_' . uniqid(),
                'keterangan' => 'Disabled method',
                'tipe' => 'e-walet',
                'payment' => 'tripay',
                'statuspayment' => false,
            ]);
        }

        $disabledOnlyCategoryIds[] = $category->id;
    }

    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    $resultCategoryIds = $result->pluck('id')->toArray();

    // Assert: empty categories (no methods at all) are excluded
    foreach ($emptyCategoryIds as $emptyId) {
        expect($resultCategoryIds)->not->toContain($emptyId);
    }

    // Assert: categories with only disabled methods are excluded
    foreach ($disabledOnlyCategoryIds as $disabledId) {
        expect($resultCategoryIds)->not->toContain($disabledId);
    }

    // Assert: categories with enabled methods ARE present
    foreach ($categoriesWithMethodIds as $validId) {
        expect(in_array($validId, $resultCategoryIds))->toBeTrue(
            "Category ID {$validId} with enabled methods should appear in getCategoriesForOrderPage()"
        );
    }

    // Assert: every returned category has at least one method in its loaded relation
    $result->each(function (PaymentDisplayCategory $category) {
        expect($category->methods)->not->toBeEmpty(
            "Category '{$category->label}' (ID: {$category->id}) should have at least one enabled method. " .
            "Categories with zero enabled methods should be excluded."
        );
    });
})->repeat(20)->group('property-test', 'payment-display-categories');

<?php

/**
 * Property 7: Category ordering
 *
 * For any set of visible PaymentDisplayCategory records (excluding the SALDO-containing category),
 * the rendered order SHALL be ascending by sort_order, with ties broken by created_at ascending.
 *
 * **Validates: Requirements 3.1**
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

test('Property 7: non-SALDO categories are ordered by sort_order asc then created_at asc', function () {
    /**
     * **Validates: Requirements 3.1**
     *
     * For any randomized set of non-SALDO categories with random sort_order values and
     * creation timestamps, the service returns them ordered by sort_order ascending,
     * with created_at ascending as tiebreaker.
     */
    app(TenantContext::class)->clear();

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->whereNull('tenant_id')->delete();

    Cache::flush();

    // Generate random number of categories (3-6)
    $numCategories = rand(3, 6);
    $categories = [];

    for ($i = 0; $i < $numCategories; $i++) {
        $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'label' => "Category_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => rand(0, 50),
            'is_visible' => true,
        ]);

        // Add at least one enabled method so the category isn't filtered out
        Method::query()->create([
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => 0,
            'name' => 'Method_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'CODE_' . uniqid(),
            'keterangan' => 'Test method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);

        $categories[] = $category;
    }

    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    expect($result)->toHaveCount($numCategories);

    // Verify ordering: sort_order asc, then created_at asc
    $result->values()->each(function ($category, $index) use ($result) {
        if ($index === 0) {
            return;
        }

        $previous = $result->values()->get($index - 1);

        if ($previous->sort_order < $category->sort_order) {
            // Previous has lower sort_order — correct
            expect(true)->toBeTrue();
        } elseif ($previous->sort_order === $category->sort_order) {
            // Same sort_order — created_at should be <= current (ascending tiebreaker)
            expect($previous->created_at->lte($category->created_at))->toBeTrue(
                "Categories with same sort_order ({$category->sort_order}) " .
                "should be ordered by created_at ascending. " .
                "Got '{$previous->label}' (created: {$previous->created_at}) " .
                "before '{$category->label}' (created: {$category->created_at})."
            );
        } else {
            // Previous has higher sort_order — incorrect ordering
            $this->fail(
                "Categories should be ordered by sort_order ascending. " .
                "Got sort_order {$previous->sort_order} ('{$previous->label}') " .
                "before sort_order {$category->sort_order} ('{$category->label}')."
            );
        }
    });
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 7: categories with identical sort_order are ordered by created_at ascending', function () {
    /**
     * **Validates: Requirements 3.1**
     *
     * When multiple categories share the same sort_order value, they must be
     * ordered by their created_at timestamp in ascending order as a tiebreaker.
     */
    app(TenantContext::class)->clear();

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->whereNull('tenant_id')->delete();

    Cache::flush();

    // Use a common sort_order to force the created_at tiebreaker
    $commonSortOrder = rand(0, 100);

    // Create categories with distinct created_at timestamps
    $numCategories = rand(3, 5);
    $createdCategories = [];

    for ($i = 0; $i < $numCategories; $i++) {
        $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'label' => "Tiebreak_{$i}_" . uniqid(),
            'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
            'sort_order' => $commonSortOrder,
            'is_visible' => true,
            'created_at' => now()->addSeconds($i * 10), // Ensure distinct timestamps
        ]);

        // Add at least one enabled method
        Method::query()->create([
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => 0,
            'name' => 'Method_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'CODE_' . uniqid(),
            'keterangan' => 'Test method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);

        $createdCategories[] = $category;
    }

    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    expect($result)->toHaveCount($numCategories);

    // All categories have the same sort_order, so they must be ordered by created_at ascending
    $resultTimestamps = $result->pluck('created_at')->values();

    $resultTimestamps->each(function ($timestamp, $index) use ($resultTimestamps, $result) {
        if ($index === 0) {
            return;
        }

        $previousTimestamp = $resultTimestamps->get($index - 1);
        $previousCategory = $result->values()->get($index - 1);
        $currentCategory = $result->values()->get($index);

        expect($previousTimestamp->lte($timestamp))->toBeTrue(
            "Categories with same sort_order should be ordered by created_at ascending. " .
            "Got '{$previousCategory->label}' (created: {$previousTimestamp}) " .
            "before '{$currentCategory->label}' (created: {$timestamp})."
        );
    });
})->repeat(20)->group('property-test', 'payment-display-categories');

<?php

/**
 * Property 6: Method ordering within category
 *
 * For any set of Methods belonging to the same PaymentDisplayCategory, the rendered order
 * SHALL be ascending by sort_order_in_category, with ties broken by method name in
 * alphabetical (lexicographic) order.
 *
 * **Validates: Requirements 2.5, 3.5**
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

test('Property 6: methods within a category are sorted by sort_order_in_category asc then name alphabetically', function () {
    /**
     * **Validates: Requirements 2.5, 3.5**
     *
     * For any random set of methods with random sort_order_in_category values and names,
     * the service returns them ordered by sort_order_in_category ascending, with ties
     * broken by name in alphabetical order.
     */
    app(TenantContext::class)->clear();

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->whereNull('tenant_id')->delete();

    Cache::flush();

    // Create a visible category
    $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => null,
        'label' => 'Test Category ' . uniqid(),
        'display_style' => ['flat', 'accordion'][array_rand(['flat', 'accordion'])],
        'sort_order' => rand(0, 100),
        'is_visible' => true,
    ]);

    // Generate random number of methods (3-8) with random sort orders and names
    $numMethods = rand(3, 8);
    $namePool = ['Alpha', 'Beta', 'Charlie', 'Delta', 'Echo', 'Foxtrot', 'Golf', 'Hotel', 'India', 'Juliet'];
    shuffle($namePool);
    $selectedNames = array_slice($namePool, 0, $numMethods);

    foreach ($selectedNames as $name) {
        Method::query()->create([
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => rand(0, 20),
            'name' => $name . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'CODE_' . uniqid(),
            'keterangan' => 'Test method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);
    }

    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    expect($result)->not->toBeEmpty();

    $resultCategory = $result->first();
    $methods = $resultCategory->methods;

    expect($methods)->toHaveCount($numMethods);

    // Verify ordering: sort_order_in_category asc, then name alphabetically
    $methods->values()->each(function ($method, $index) use ($methods) {
        if ($index === 0) {
            return;
        }

        $previous = $methods->values()->get($index - 1);

        if ($previous->sort_order_in_category < $method->sort_order_in_category) {
            // Previous has lower sort_order — correct
            expect(true)->toBeTrue();
        } elseif ($previous->sort_order_in_category === $method->sort_order_in_category) {
            // Same sort_order — name should be alphabetically <= current
            expect(strcasecmp($previous->name, $method->name))->toBeLessThanOrEqual(
                0,
                "Methods with same sort_order_in_category ({$method->sort_order_in_category}) " .
                "should be sorted alphabetically by name. " .
                "Got '{$previous->name}' before '{$method->name}'."
            );
        } else {
            // Previous has higher sort_order — incorrect ordering
            $this->fail(
                "Methods should be ordered by sort_order_in_category ascending. " .
                "Got sort_order {$previous->sort_order_in_category} ('{$previous->name}') " .
                "before sort_order {$method->sort_order_in_category} ('{$method->name}')."
            );
        }
    });
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 6: methods with identical sort_order_in_category are ordered by name alphabetically', function () {
    /**
     * **Validates: Requirements 2.5, 3.5**
     *
     * When multiple methods share the same sort_order_in_category value,
     * they must be ordered by their name in alphabetical order as a tiebreaker.
     */
    app(TenantContext::class)->clear();

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->whereNull('tenant_id')->delete();

    Cache::flush();

    // Create a visible category
    $category = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => null,
        'label' => 'Tiebreak Category ' . uniqid(),
        'display_style' => 'flat',
        'sort_order' => 1,
        'is_visible' => true,
    ]);

    // Use a common sort_order for all methods to force alphabetical tiebreaker
    $commonSortOrder = rand(0, 50);

    // Generate methods with distinct names that test alphabetical ordering
    $names = ['Zebra', 'Apple', 'Mango', 'Banana', 'Cherry'];
    shuffle($names); // Randomize creation order

    foreach ($names as $name) {
        Method::query()->create([
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => $commonSortOrder,
            'name' => $name,
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'CODE_' . uniqid(),
            'keterangan' => 'Test method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);
    }

    $service = app(PaymentDisplayCategoryService::class);
    $result = $service->getCategoriesForOrderPage();

    expect($result)->not->toBeEmpty();

    $resultCategory = $result->first();
    $methods = $resultCategory->methods;

    expect($methods)->toHaveCount(count($names));

    // All methods have the same sort_order, so they must be in alphabetical order by name
    $sortedNames = collect($names)->sort()->values();
    $resultNames = $methods->pluck('name')->values();

    expect($resultNames->toArray())->toBe($sortedNames->toArray(),
        "Methods with same sort_order_in_category ({$commonSortOrder}) should be sorted " .
        "alphabetically by name. Expected: [" . $sortedNames->implode(', ') . "] " .
        "Got: [" . $resultNames->implode(', ') . "]"
    );
})->repeat(20)->group('property-test', 'payment-display-categories');

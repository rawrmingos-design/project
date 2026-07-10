<?php

/**
 * Property 3: Delete cascading preserves methods
 *
 * For any PaymentDisplayCategory with N associated Method records, deleting the category
 * SHALL result in all N Method records still existing in the database with their
 * `payment_display_category_id` set to null.
 *
 * **Validates: Requirements 1.6**
 */

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::withoutEvents(function () {
        return Tenant::create([
            'name' => 'Delete Cascade Test Tenant',
            'subdomain' => 'del-cascade-' . uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    });

    $tenantContext = app(TenantContext::class);
    $tenantContext->set($this->tenant);
});

afterEach(function () {
    app(TenantContext::class)->clear();
});

test('Property 3: deleting a category preserves all associated methods with null FK', function () {
    // Create a category with a random number of associated methods (1 to 10)
    $methodCount = rand(1, 10);

    $category = PaymentDisplayCategory::create([
        'label' => 'Category ' . uniqid(),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 999),
        'is_visible' => (bool) rand(0, 1),
        'tenant_id' => $this->tenant->id,
    ]);

    // Create N methods associated with the category
    $methodIds = [];
    for ($i = 0; $i < $methodCount; $i++) {
        $method = Method::create([
            'name' => 'Method ' . uniqid(),
            'code' => 'CODE_' . strtoupper(uniqid()),
            'images' => '/assets/test.webp',
            'keterangan' => 'Test method ' . $i,
            'tipe' => collect(['e-walet', 'virtual-account', 'qris', 'convenience-store'])->random(),
            'payment' => 'tripay',
            'fee_percent' => rand(0, 5),
            'fix_fee' => rand(0, 5000),
            'min_pembelian' => 1000,
            'max_pembelian' => 10000000,
            'statuspayment' => true,
            'payment_display_category_id' => $category->id,
            'sort_order_in_category' => $i,
        ]);
        $methodIds[] = $method->id;
    }

    // Verify methods are associated before deletion
    expect(Method::whereIn('id', $methodIds)->where('payment_display_category_id', $category->id)->count())
        ->toBe($methodCount);

    // Delete the category
    $category->delete();

    // Verify all N methods still exist in the database
    $remainingMethods = Method::whereIn('id', $methodIds)->get();
    expect($remainingMethods)->toHaveCount($methodCount);

    // Verify all methods now have payment_display_category_id set to null (ON DELETE SET NULL)
    foreach ($remainingMethods as $method) {
        expect($method->payment_display_category_id)->toBeNull(
            "Method {$method->id} should have null payment_display_category_id after category deletion"
        );
    }
})->repeat(20);

test('Property 3: deleting a category with zero methods does not affect other methods', function () {
    // Create two categories
    $categoryToDelete = PaymentDisplayCategory::create([
        'label' => 'Empty Category ' . uniqid(),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 999),
        'is_visible' => true,
        'tenant_id' => $this->tenant->id,
    ]);

    $otherCategory = PaymentDisplayCategory::create([
        'label' => 'Other Category ' . uniqid(),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 999),
        'is_visible' => true,
        'tenant_id' => $this->tenant->id,
    ]);

    // Create methods assigned to the other category
    $otherMethodCount = rand(1, 5);
    $otherMethodIds = [];
    for ($i = 0; $i < $otherMethodCount; $i++) {
        $method = Method::create([
            'name' => 'Other Method ' . uniqid(),
            'code' => 'OTH_' . strtoupper(uniqid()),
            'images' => '/assets/test.webp',
            'keterangan' => 'Other method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'min_pembelian' => 1000,
            'max_pembelian' => 10000000,
            'statuspayment' => true,
            'payment_display_category_id' => $otherCategory->id,
            'sort_order_in_category' => $i,
        ]);
        $otherMethodIds[] = $method->id;
    }

    // Delete the empty category
    $categoryToDelete->delete();

    // Verify methods belonging to the other category are unaffected
    $otherMethods = Method::whereIn('id', $otherMethodIds)->get();
    expect($otherMethods)->toHaveCount($otherMethodCount);

    foreach ($otherMethods as $method) {
        expect($method->payment_display_category_id)->toBe($otherCategory->id,
            "Method {$method->id} should still reference the other category"
        );
    }
})->repeat(20);

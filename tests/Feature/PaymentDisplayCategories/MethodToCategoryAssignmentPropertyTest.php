<?php

/**
 * Property 4: Method-to-category is many-to-one replacement
 *
 * For any Method record, assigning it to a new PaymentDisplayCategory SHALL replace
 * the previous assignment, such that the method references exactly one category (or null)
 * at all times.
 *
 * **Validates: Requirements 2.3**
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
            'name' => 'Category Assignment Test Tenant',
            'subdomain' => 'cat-assign-' . uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    });

    $tenantContext = app(TenantContext::class);
    $tenantContext->set($this->tenant);
});

afterEach(function () {
    app(TenantContext::class)->clear();
});

test('Property 4: assigning a method to a new category replaces the previous assignment', function () {
    // Create multiple categories for this tenant
    $categories = collect();
    $numCategories = rand(2, 5);
    for ($i = 0; $i < $numCategories; $i++) {
        $categories->push(PaymentDisplayCategory::create([
            'label' => 'Category ' . uniqid(),
            'display_style' => collect(['flat', 'accordion'])->random(),
            'sort_order' => $i,
            'is_visible' => true,
            'tenant_id' => $this->tenant->id,
        ]));
    }

    // Create a method with an initial category assignment
    $initialCategory = $categories->random();
    $method = Method::create([
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
        'payment_display_category_id' => $initialCategory->id,
        'sort_order_in_category' => rand(0, 999),
    ]);

    // Verify initial assignment: method has exactly one category
    $method->refresh();
    expect($method->payment_display_category_id)->toBe($initialCategory->id);
    expect($method->displayCategory)->not->toBeNull();
    expect($method->displayCategory->id)->toBe($initialCategory->id);

    // Now assign to a different category
    $newCategory = $categories->filter(fn ($c) => $c->id !== $initialCategory->id)->random();
    $method->update(['payment_display_category_id' => $newCategory->id]);

    // Verify reassignment: method now references exactly the new category
    $method->refresh();
    expect($method->payment_display_category_id)->toBe($newCategory->id);
    expect($method->displayCategory->id)->toBe($newCategory->id);

    // Verify only one category reference exists (no pivot, no leftover)
    $assignedCategories = PaymentDisplayCategory::whereHas('methods', function ($q) use ($method) {
        $q->where('methods.id', $method->id);
    })->get();

    expect($assignedCategories)->toHaveCount(1);
    expect($assignedCategories->first()->id)->toBe($newCategory->id);
})->repeat(20);

test('Property 4: assigning a method to null clears the category and method has no category', function () {
    // Create a category
    $category = PaymentDisplayCategory::create([
        'label' => 'Nullable Test ' . uniqid(),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 999),
        'is_visible' => true,
        'tenant_id' => $this->tenant->id,
    ]);

    // Create a method assigned to the category
    $method = Method::create([
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
        'payment_display_category_id' => $category->id,
        'sort_order_in_category' => rand(0, 999),
    ]);

    // Verify initial assignment
    $method->refresh();
    expect($method->payment_display_category_id)->toBe($category->id);

    // Clear the assignment (set to null)
    $method->update(['payment_display_category_id' => null]);

    // Verify: method has null category
    $method->refresh();
    expect($method->payment_display_category_id)->toBeNull();
    expect($method->displayCategory)->toBeNull();

    // Verify method no longer appears in any category's methods
    $assignedCategories = PaymentDisplayCategory::whereHas('methods', function ($q) use ($method) {
        $q->where('methods.id', $method->id);
    })->get();

    expect($assignedCategories)->toHaveCount(0);
})->repeat(20);

test('Property 4: sequential reassignments always result in exactly one category reference', function () {
    // Create several categories
    $categories = collect();
    $numCategories = rand(3, 6);
    for ($i = 0; $i < $numCategories; $i++) {
        $categories->push(PaymentDisplayCategory::create([
            'label' => 'SeqCat ' . uniqid(),
            'display_style' => collect(['flat', 'accordion'])->random(),
            'sort_order' => $i,
            'is_visible' => true,
            'tenant_id' => $this->tenant->id,
        ]));
    }

    // Create a method without category
    $method = Method::create([
        'name' => 'SeqMethod ' . uniqid(),
        'code' => 'SEQ_' . strtoupper(uniqid()),
        'images' => '/assets/test.webp',
        'keterangan' => 'Test method',
        'tipe' => collect(['e-walet', 'virtual-account', 'qris', 'convenience-store'])->random(),
        'payment' => 'tripay',
        'fee_percent' => rand(0, 5),
        'fix_fee' => rand(0, 5000),
        'min_pembelian' => 1000,
        'max_pembelian' => 10000000,
        'statuspayment' => true,
        'payment_display_category_id' => null,
        'sort_order_in_category' => 0,
    ]);

    // Perform multiple sequential reassignments
    $numReassignments = rand(3, 7);
    $lastCategoryId = null;

    for ($i = 0; $i < $numReassignments; $i++) {
        // Randomly pick a category (or null) for each reassignment
        $useNull = rand(0, 4) === 0; // 20% chance of null
        $targetCategoryId = $useNull ? null : $categories->random()->id;

        $method->update(['payment_display_category_id' => $targetCategoryId]);
        $method->refresh();

        // After each assignment, verify invariant: exactly one or null
        expect($method->payment_display_category_id)->toBe($targetCategoryId);

        if ($targetCategoryId === null) {
            expect($method->displayCategory)->toBeNull();
            $assignedCategories = PaymentDisplayCategory::whereHas('methods', function ($q) use ($method) {
                $q->where('methods.id', $method->id);
            })->get();
            expect($assignedCategories)->toHaveCount(0);
        } else {
            expect($method->displayCategory)->not->toBeNull();
            expect($method->displayCategory->id)->toBe($targetCategoryId);
            $assignedCategories = PaymentDisplayCategory::whereHas('methods', function ($q) use ($method) {
                $q->where('methods.id', $method->id);
            })->get();
            expect($assignedCategories)->toHaveCount(1);
        }

        $lastCategoryId = $targetCategoryId;
    }

    // Final check: the last assignment is what persists
    $method->refresh();
    expect($method->payment_display_category_id)->toBe($lastCategoryId);
})->repeat(20);

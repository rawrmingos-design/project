<?php

/**
 * Property 10: Tipe normalization maps to correct category
 *
 * For any Method with a `tipe` value, applying `Method::normalizeTipe()` SHALL produce
 * a key that maps to the corresponding default PaymentDisplayCategory (e.g.,
 * "e-walet" → "E-Wallet", "virtual-account" → "Virtual Account"). When a new Method
 * is created without explicit category, the system SHALL auto-assign it to the matching category.
 *
 * **Validates: Requirements 4.2, 4.5**
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

test('Property 10: normalizeTipe produces correct keys mapping to expected categories', function () {
    /**
     * **Validates: Requirements 4.2, 4.5**
     *
     * For any known tipe value variant, normalizeTipe() produces the correct normalized key,
     * and that key maps to the expected default PaymentDisplayCategory label.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Tipe Norm Tenant ' . uniqid(),
        'subdomain' => 'tipe-norm-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Provision default categories for the tenant
    $service = app(PaymentDisplayCategoryService::class);
    $service->provisionDefaultsForTenant($tenant);

    // Define the complete mapping: input tipe variants → normalized key → expected category label
    $tipeToNormalizedMapping = [
        'ewallet' => 'e-walet',
        'e-wallet' => 'e-walet',
        'e_walet' => 'e-walet',
        'e_wallet' => 'e-walet',
        'virtual_account' => 'virtual-account',
        'convenience_store' => 'convenience-store',
        'qris' => 'qris',
        'saldo' => 'saldo',
        'e-walet' => 'e-walet',
        'virtual-account' => 'virtual-account',
        'convenience-store' => 'convenience-store',
    ];

    $normalizedToLabel = [
        'e-walet' => 'E-Wallet',
        'virtual-account' => 'Virtual Account',
        'convenience-store' => 'Convenience Store',
        'qris' => 'QRIS',
        'saldo' => 'SALDO',
    ];

    // Pick a random subset of tipe variants to test each iteration
    $allInputTipes = array_keys($tipeToNormalizedMapping);
    $numToTest = rand(3, count($allInputTipes));
    $selectedTipes = collect($allInputTipes)->shuffle()->take($numToTest)->all();

    foreach ($selectedTipes as $inputTipe) {
        $expectedNormalized = $tipeToNormalizedMapping[$inputTipe];
        $expectedLabel = $normalizedToLabel[$expectedNormalized];

        // Verify normalizeTipe produces the correct normalized key
        $actualNormalized = Method::normalizeTipe($inputTipe);
        expect($actualNormalized)->toBe($expectedNormalized,
            "normalizeTipe('{$inputTipe}') should produce '{$expectedNormalized}', got '{$actualNormalized}'"
        );

        // Verify mapTipeToCategory returns the correct category
        $category = $service->mapTipeToCategory($actualNormalized);
        expect($category)->not->toBeNull(
            "mapTipeToCategory('{$actualNormalized}') should return a category, got null"
        );
        expect($category->label)->toBe($expectedLabel,
            "mapTipeToCategory('{$actualNormalized}') should map to '{$expectedLabel}', got '{$category->label}'"
        );
    }
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 10: auto-assignment assigns new method to matching category based on tipe', function () {
    /**
     * **Validates: Requirements 4.2, 4.5**
     *
     * When a Method is created without an explicit category, the auto-assignment logic
     * (as implemented in CreateMethod) assigns it to the correct PaymentDisplayCategory
     * based on its normalized tipe value.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Auto Assign Tenant ' . uniqid(),
        'subdomain' => 'auto-assign-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Provision default categories for the tenant
    $service = app(PaymentDisplayCategoryService::class);
    $service->provisionDefaultsForTenant($tenant);

    // Tipe variants that should auto-assign to a known category
    $tipeVariants = [
        'ewallet' => 'E-Wallet',
        'e-wallet' => 'E-Wallet',
        'e_walet' => 'E-Wallet',
        'e_wallet' => 'E-Wallet',
        'e-walet' => 'E-Wallet',
        'virtual_account' => 'Virtual Account',
        'virtual-account' => 'Virtual Account',
        'convenience_store' => 'Convenience Store',
        'convenience-store' => 'Convenience Store',
        'qris' => 'QRIS',
        'saldo' => 'SALDO',
    ];

    // Pick a random tipe variant to test
    $inputTipe = collect(array_keys($tipeVariants))->random();
    $expectedLabel = $tipeVariants[$inputTipe];

    // Simulate auto-assignment logic (same as CreateMethod::mutateFormDataBeforeCreate)
    $normalizedTipe = Method::normalizeTipe($inputTipe);
    $category = $service->mapTipeToCategory($normalizedTipe);

    expect($category)->not->toBeNull(
        "Auto-assignment should find a category for tipe '{$inputTipe}' (normalized: '{$normalizedTipe}')"
    );
    expect($category->label)->toBe($expectedLabel,
        "Auto-assignment for tipe '{$inputTipe}' should map to '{$expectedLabel}', got '{$category->label}'"
    );

    // Create the method with the auto-assigned category to verify the full flow
    $method = Method::query()->create([
        'name' => 'Test Method ' . uniqid(),
        'code' => 'TEST_' . uniqid(),
        'images' => '/assets/thumbnail/test.webp',
        'keterangan' => 'Auto-assignment test',
        'tipe' => $inputTipe,
        'payment' => 'tripay',
        'fee_percent' => 0,
        'fix_fee' => 0,
        'min_pembelian' => 1000,
        'max_pembelian' => 1000000,
        'statuspayment' => true,
        'payment_display_category_id' => $category->id,
        'sort_order_in_category' => 0,
    ]);

    // Verify the method is correctly associated with the expected category
    $method->refresh();
    expect($method->payment_display_category_id)->toBe($category->id);
    expect($method->displayCategory->label)->toBe($expectedLabel);
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 10: unmatched tipe returns null from mapTipeToCategory', function () {
    /**
     * **Validates: Requirements 4.2, 4.5**
     *
     * When a Method has a tipe that does not match any known category,
     * mapTipeToCategory returns null and no auto-assignment occurs.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Unmatched Tipe Tenant ' . uniqid(),
        'subdomain' => 'unmatched-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Provision default categories for the tenant
    $service = app(PaymentDisplayCategoryService::class);
    $service->provisionDefaultsForTenant($tenant);

    // Generate random unknown tipe values that should NOT match any category
    $unknownTipes = [
        'unknown-' . uniqid(),
        'payment-type-' . rand(100, 999),
        'custom_' . uniqid(),
        'bank-transfer',
        'credit-card',
        'ovo-direct',
    ];

    $selectedTipe = $unknownTipes[array_rand($unknownTipes)];
    $normalizedTipe = Method::normalizeTipe($selectedTipe);

    // Verify that unknown tipe values do not map to any category
    $category = $service->mapTipeToCategory($normalizedTipe);
    expect($category)->toBeNull(
        "mapTipeToCategory('{$normalizedTipe}') for unknown tipe '{$selectedTipe}' should return null"
    );
})->repeat(20)->group('property-test', 'payment-display-categories');

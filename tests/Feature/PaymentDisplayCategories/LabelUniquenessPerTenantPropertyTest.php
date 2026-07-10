<?php

/**
 * Property 2: Label uniqueness per tenant
 *
 * For any two PaymentDisplayCategory records within the same tenant, if they share
 * the same label (case-insensitive), the system SHALL reject the second creation/update.
 * The same label in different tenants SHALL be accepted.
 *
 * **Validates: Requirements 1.5**
 */

use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->tenantA = Tenant::create([
        'name' => 'Tenant A - Uniqueness Test',
        'subdomain' => 'tenant-a-' . uniqid(),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $this->tenantB = Tenant::create([
        'name' => 'Tenant B - Uniqueness Test',
        'subdomain' => 'tenant-b-' . uniqid(),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantContext = app(TenantContext::class);
    $tenantContext->set($this->tenantA);
});

afterEach(function () {
    app(TenantContext::class)->clear();
});

/**
 * Generates a random label string of a given length.
 */
function generateRandomLabel(int $length = 0): string
{
    if ($length <= 0) {
        $length = rand(1, 50);
    }

    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 -_';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[rand(0, strlen($chars) - 1)];
    }

    return trim($result) ?: 'fallback';
}

test('Property 2: duplicate label within same tenant is rejected by DB unique constraint', function () {
    $label = generateRandomLabel(rand(1, 80));
    $displayStyle = collect(['flat', 'accordion'])->random();
    $sortOrder = rand(0, 999);

    // Create first record successfully
    $first = PaymentDisplayCategory::create([
        'label' => $label,
        'display_style' => $displayStyle,
        'sort_order' => $sortOrder,
        'is_visible' => true,
        'tenant_id' => test()->tenantA->id,
    ]);

    expect($first->exists)->toBeTrue();

    // Attempt to create a second record with the same label in the same tenant
    $rejected = false;
    try {
        PaymentDisplayCategory::create([
            'label' => $label,
            'display_style' => collect(['flat', 'accordion'])->random(),
            'sort_order' => rand(0, 999),
            'is_visible' => (bool) rand(0, 1),
            'tenant_id' => test()->tenantA->id,
        ]);
    } catch (QueryException $e) {
        $rejected = true;
        // Verify it's a unique constraint violation (SQLSTATE 23000 or 23505)
        expect($e->getCode())->toMatch('/^23/');
    }

    expect($rejected)->toBeTrue(
        "Expected duplicate label '{$label}' within tenant {$this->tenantA->id} to be rejected by unique constraint"
    );
})->repeat(20);

test('Property 2: same label in different tenants is accepted', function () {
    $label = generateRandomLabel(rand(1, 80));
    $displayStyle = collect(['flat', 'accordion'])->random();
    $sortOrder = rand(0, 999);

    // Create record in tenant A
    $categoryA = PaymentDisplayCategory::create([
        'label' => $label,
        'display_style' => $displayStyle,
        'sort_order' => $sortOrder,
        'is_visible' => true,
        'tenant_id' => test()->tenantA->id,
    ]);

    expect($categoryA->exists)->toBeTrue();

    // Create record with the same label in tenant B - should succeed
    $categoryB = PaymentDisplayCategory::create([
        'label' => $label,
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 999),
        'is_visible' => true,
        'tenant_id' => test()->tenantB->id,
    ]);

    expect($categoryB->exists)->toBeTrue();
    expect($categoryB->label)->toBe($label);
    expect($categoryB->tenant_id)->toBe(test()->tenantB->id);
    expect($categoryA->tenant_id)->not->toBe($categoryB->tenant_id);

    // Both records coexist in the database
    $countA = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', test()->tenantA->id)
        ->where('label', $label)
        ->count();
    $countB = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', test()->tenantB->id)
        ->where('label', $label)
        ->count();

    expect($countA)->toBe(1);
    expect($countB)->toBe(1);
})->repeat(20);

test('Property 2: unique labels within same tenant are all accepted', function () {
    $numCategories = rand(2, 5);
    $usedLabels = [];
    $created = [];

    // Use a unique batch identifier to avoid collisions with prior repetitions
    $batchId = uniqid('batch_', true);

    for ($i = 0; $i < $numCategories; $i++) {
        // Generate a unique label for each category in this batch
        $label = "{$batchId}_item_{$i}";
        $usedLabels[] = $label;

        $category = PaymentDisplayCategory::create([
            'label' => $label,
            'display_style' => collect(['flat', 'accordion'])->random(),
            'sort_order' => rand(0, 999),
            'is_visible' => (bool) rand(0, 1),
            'tenant_id' => test()->tenantA->id,
        ]);

        expect($category->exists)->toBeTrue();
        $created[] = $category;
    }

    // Verify all records from this batch exist
    expect(count($created))->toBe($numCategories);

    $batchCount = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', test()->tenantA->id)
        ->whereIn('label', $usedLabels)
        ->count();

    expect($batchCount)->toBe($numCategories);
})->repeat(20);

<?php

/**
 * Property 11: Migration idempotence
 *
 * For any database state, executing the migration N times (N ≥ 1) SHALL produce
 * the same result as executing it once: no duplicate PaymentDisplayCategory records
 * are created and no existing method-to-category associations are modified.
 *
 * **Validates: Requirements 4.6**
 */

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Services\PaymentDisplayCategoryService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::withoutEvents(function () {
        return Tenant::create([
            'name' => 'Idempotence Test Tenant',
            'subdomain' => 'idempotence-' . uniqid(),
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    });

    $tenantContext = app(TenantContext::class);
    $tenantContext->set($this->tenant);

    $this->service = new PaymentDisplayCategoryService();
});

afterEach(function () {
    app(TenantContext::class)->clear();
});

test('Property 11: calling provisionDefaultsForTenant N times produces same categories as calling once', function () {
    $N = rand(2, 5);

    // Call provisioning the first time
    $this->service->provisionDefaultsForTenant($this->tenant);

    // Capture state after first call
    $categoriesAfterFirst = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $this->tenant->id)
        ->orderBy('label')
        ->get()
        ->toArray();

    $countAfterFirst = count($categoriesAfterFirst);

    // Call provisioning N-1 more times
    for ($i = 1; $i < $N; $i++) {
        $this->service->provisionDefaultsForTenant($this->tenant);
    }

    // Capture state after N calls
    $categoriesAfterN = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $this->tenant->id)
        ->orderBy('label')
        ->get()
        ->toArray();

    $countAfterN = count($categoriesAfterN);

    // No duplicates: count must be the same
    expect($countAfterN)->toBe($countAfterFirst,
        "Expected {$countAfterFirst} categories after {$N} calls, got {$countAfterN} — duplicates were created"
    );

    // Exact same records (same IDs, same data)
    expect($categoriesAfterN)->toBe($categoriesAfterFirst,
        "Categories were modified after repeated provisioning calls"
    );
})->repeat(20);

test('Property 11: repeated provisioning does not modify existing method-to-category associations', function () {
    // Canonical catalog categories are global (tenant_id = null). Tenant provisioning is no-op.
    $categories = collect([
        PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'label' => 'QRIS_' . uniqid(),
            'display_style' => 'flat',
            'sort_order' => 1,
            'is_visible' => true,
        ]),
        PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => null,
            'label' => 'E-Wallet_' . uniqid(),
            'display_style' => 'accordion',
            'sort_order' => 2,
            'is_visible' => true,
        ]),
    ]);

    $numMethods = rand(2, 5);
    $methods = [];

    for ($i = 0; $i < $numMethods; $i++) {
        $category = $categories->random();
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
        $methods[] = $method;
    }

    // Capture method assignments before repeated provisioning
    $assignmentsBefore = collect($methods)->map(fn ($m) => [
        'id' => $m->id,
        'payment_display_category_id' => $m->fresh()->payment_display_category_id,
        'sort_order_in_category' => $m->fresh()->sort_order_in_category,
    ])->toArray();

    // Call provisioning multiple times
    $N = rand(2, 5);
    for ($i = 0; $i < $N; $i++) {
        $this->service->provisionDefaultsForTenant($this->tenant);
    }

    // Capture method assignments after repeated provisioning
    $assignmentsAfter = collect($methods)->map(fn ($m) => [
        'id' => $m->id,
        'payment_display_category_id' => $m->fresh()->payment_display_category_id,
        'sort_order_in_category' => $m->fresh()->sort_order_in_category,
    ])->toArray();

    // Assignments must not be modified
    expect($assignmentsAfter)->toBe($assignmentsBefore,
        "Method-to-category associations were modified after {$N} repeated provisioning calls"
    );
})->repeat(20);

test('Property 11: provisioning with pre-existing tenant categories does not create additional duplicates', function () {
    // Legacy tenant category rows may exist from older data. New provisioning is a no-op
    // because tenants now use the canonical global catalog plus visibility overrides.
    $allDefaults = [
        ['label' => 'SALDO', 'display_style' => 'flat', 'sort_order' => 1],
        ['label' => 'QRIS', 'display_style' => 'flat', 'sort_order' => 2],
        ['label' => 'E-Wallet', 'display_style' => 'accordion', 'sort_order' => 3],
        ['label' => 'Virtual Account', 'display_style' => 'accordion', 'sort_order' => 4],
        ['label' => 'Convenience Store', 'display_style' => 'accordion', 'sort_order' => 5],
    ];

    $subsetSize = rand(1, 4);
    $subset = collect($allDefaults)->random($subsetSize)->all();

    foreach ($subset as $default) {
        PaymentDisplayCategory::withoutGlobalScopes()->create([
            'label' => $default['label'],
            'display_style' => $default['display_style'],
            'sort_order' => $default['sort_order'],
            'is_visible' => true,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    $countBeforeProvisioning = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $this->tenant->id)
        ->count();

    $this->service->provisionDefaultsForTenant($this->tenant);

    $countAfterProvisioning = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $this->tenant->id)
        ->count();

    expect($countAfterProvisioning)->toBe($countBeforeProvisioning,
        "Provisioning should not create tenant category rows under canonical catalog mode"
    );

    $this->service->provisionDefaultsForTenant($this->tenant);

    $countAfterSecond = PaymentDisplayCategory::withoutGlobalScopes()
        ->where('tenant_id', $this->tenant->id)
        ->count();

    expect($countAfterSecond)->toBe($countBeforeProvisioning,
        "Repeated provisioning should remain no-op for tenant categories"
    );
})->repeat(20);

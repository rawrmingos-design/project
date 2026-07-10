<?php

use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Services\PaymentDisplayCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('creating a tenant provisions default payment display categories', function () {
    $tenant = Tenant::create([
        'name' => 'Observer Test Tenant',
        'subdomain' => 'observer-test-' . uniqid(),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $categories = PaymentDisplayCategory::where('tenant_id', $tenant->id)
        ->orderBy('sort_order')
        ->get();

    expect($categories)->toHaveCount(5);

    expect($categories[0]->label)->toBe('SALDO');
    expect($categories[0]->display_style)->toBe('flat');
    expect($categories[0]->sort_order)->toBe(1);
    expect($categories[0]->is_visible)->toBeTrue();

    expect($categories[1]->label)->toBe('QRIS');
    expect($categories[1]->display_style)->toBe('flat');
    expect($categories[1]->sort_order)->toBe(2);

    expect($categories[2]->label)->toBe('E-Wallet');
    expect($categories[2]->display_style)->toBe('accordion');
    expect($categories[2]->sort_order)->toBe(3);

    expect($categories[3]->label)->toBe('Virtual Account');
    expect($categories[3]->display_style)->toBe('accordion');
    expect($categories[3]->sort_order)->toBe(4);

    expect($categories[4]->label)->toBe('Convenience Store');
    expect($categories[4]->display_style)->toBe('accordion');
    expect($categories[4]->sort_order)->toBe(5);
});

test('provisioning is idempotent - calling it again does not duplicate categories', function () {
    $tenant = Tenant::create([
        'name' => 'Idempotent Test Tenant',
        'subdomain' => 'idempotent-test-' . uniqid(),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    // Observer already provisioned defaults; call again manually
    app(PaymentDisplayCategoryService::class)->provisionDefaultsForTenant($tenant);

    $count = PaymentDisplayCategory::where('tenant_id', $tenant->id)->count();

    expect($count)->toBe(5);
});

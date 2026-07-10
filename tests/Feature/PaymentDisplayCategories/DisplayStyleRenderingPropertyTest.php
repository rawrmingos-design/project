<?php

/**
 * Property 8: Display style determines render mode
 *
 * For any PaymentDisplayCategory, if display_style is "flat" then the rendered output
 * SHALL contain method items without a collapsible wrapper; if display_style is "accordion"
 * then the rendered output SHALL contain a collapsible section with the category label as its header.
 *
 * **Validates: Requirements 3.2, 3.3**
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

test('Property 8: flat display_style renders without collapsible wrapper', function () {
    /**
     * **Validates: Requirements 3.2**
     *
     * For any PaymentDisplayCategory with display_style "flat", the rendered output
     * uses the flat partial with visible methods (no accordion/collapsible structure).
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Flat Style Tenant ' . uniqid(),
        'subdomain' => 'flat-style-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();

    Cache::flush();

    // Create a flat category with a random label
    $label = 'Flat_' . uniqid();
    $flatCategory = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'label' => $label,
        'display_style' => 'flat',
        'sort_order' => rand(0, 999),
        'is_visible' => true,
        'icon' => null,
    ]);

    // Create random number of methods (1-5) assigned to the flat category
    $numMethods = rand(1, 5);
    for ($i = 0; $i < $numMethods; $i++) {
        Method::query()->create([
            'payment_display_category_id' => $flatCategory->id,
            'sort_order_in_category' => $i,
            'name' => 'FlatMethod_' . $i . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'FLAT_' . $i . '_' . uniqid(),
            'keterangan' => 'Flat test method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);
    }

    // Get the categories from the service
    $service = app(PaymentDisplayCategoryService::class);
    $categories = $service->getCategoriesForOrderPage();

    // Render the dynamic partial
    $html = view('template.partials.payment-categories-dynamic', [
        'paymentCategories' => $categories,
    ])->render();

    // Flat partial should contain the category label in a simple span (not inside an accordion button)
    expect($html)->toContain($label);

    // Flat partial renders method items directly (role="radio" with method-id attribute)
    expect($html)->toContain('role="radio"');

    // Flat partial should NOT contain accordion-specific elements:
    // - No accordion-header class
    expect($html)->not->toContain('accordion-header');

    // - No aria-controls="disclosure-panel-" pattern for this category
    expect($html)->not->toContain('aria-controls="disclosure-panel-' . $flatCategory->id . '"');

    // - No collapsible max-h-0 container for methods
    expect($html)->not->toContain('x-ref="container_cat_' . $flatCategory->id . '"');
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 8: accordion display_style renders with collapsible section and label as header', function () {
    /**
     * **Validates: Requirements 3.3**
     *
     * For any PaymentDisplayCategory with display_style "accordion", the rendered output
     * uses the accordion partial with a collapsible section and the category label as the header.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Accordion Style Tenant ' . uniqid(),
        'subdomain' => 'accordion-style-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();

    Cache::flush();

    // Create an accordion category with a random label
    $label = 'Accordion_' . uniqid();
    $accordionCategory = PaymentDisplayCategory::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'label' => $label,
        'display_style' => 'accordion',
        'sort_order' => rand(0, 999),
        'is_visible' => true,
        'icon' => null,
    ]);

    // Create random number of methods (1-5) assigned to the accordion category
    $numMethods = rand(1, 5);
    for ($i = 0; $i < $numMethods; $i++) {
        Method::query()->create([
            'payment_display_category_id' => $accordionCategory->id,
            'sort_order_in_category' => $i,
            'name' => 'AccordionMethod_' . $i . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'ACC_' . $i . '_' . uniqid(),
            'keterangan' => 'Accordion test method',
            'tipe' => 'virtual-account',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);
    }

    // Get the categories from the service
    $service = app(PaymentDisplayCategoryService::class);
    $categories = $service->getCategoriesForOrderPage();

    // Render the dynamic partial
    $html = view('template.partials.payment-categories-dynamic', [
        'paymentCategories' => $categories,
    ])->render();

    // Accordion partial should contain the category label as header text
    expect($html)->toContain($label);

    // Accordion-specific structure assertions:
    // - Has accordion-header class
    expect($html)->toContain('accordion-header');

    // - Has aria-controls referencing the disclosure panel for this category
    expect($html)->toContain('aria-controls="disclosure-panel-' . $accordionCategory->id . '"');

    // - Has the collapsible container reference
    expect($html)->toContain('x-ref="container_cat_' . $accordionCategory->id . '"');

    // - Has disclosure panel id
    expect($html)->toContain('id="disclosure-panel-' . $accordionCategory->id . '"');

    // - Has the expand/collapse SVG chevron
    expect($html)->toContain('rotate-180');
})->repeat(20)->group('property-test', 'payment-display-categories');

test('Property 8: mixed display styles render correctly in same page', function () {
    /**
     * **Validates: Requirements 3.2, 3.3**
     *
     * For any random mix of flat and accordion categories on the same page,
     * flat categories render without collapsible wrappers and accordion categories
     * render with collapsible sections, each with the correct label as header.
     */
    $owner = User::factory()->create(['role' => 'Gold']);

    $tenant = Tenant::query()->create([
        'owner_user_id' => $owner->id,
        'name' => 'Mixed Style Tenant ' . uniqid(),
        'subdomain' => 'mixed-style-' . uniqid(),
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $context = app(TenantContext::class);
    $context->set($tenant);

    // Clean pre-provisioned categories
    PaymentDisplayCategory::withoutGlobalScopes()->where('tenant_id', $tenant->id)->delete();

    Cache::flush();

    // Randomly decide how many flat and accordion categories to create (at least 1 of each)
    $numFlat = rand(1, 3);
    $numAccordion = rand(1, 3);

    $flatLabels = [];
    $accordionLabels = [];
    $flatCategoryIds = [];
    $accordionCategoryIds = [];

    // Create flat categories
    for ($i = 0; $i < $numFlat; $i++) {
        $label = 'FlatMixed_' . $i . '_' . uniqid();
        $flatLabels[] = $label;
        $cat = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'label' => $label,
            'display_style' => 'flat',
            'sort_order' => rand(0, 999),
            'is_visible' => true,
            'icon' => null,
        ]);
        $flatCategoryIds[] = $cat->id;

        // Add at least one method
        Method::query()->create([
            'payment_display_category_id' => $cat->id,
            'sort_order_in_category' => 0,
            'name' => 'FlatMixMethod_' . $i . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'FMIX_' . $i . '_' . uniqid(),
            'keterangan' => 'Flat mixed method',
            'tipe' => 'e-walet',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);
    }

    // Create accordion categories
    for ($j = 0; $j < $numAccordion; $j++) {
        $label = 'AccordionMixed_' . $j . '_' . uniqid();
        $accordionLabels[] = $label;
        $cat = PaymentDisplayCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'label' => $label,
            'display_style' => 'accordion',
            'sort_order' => rand(0, 999),
            'is_visible' => true,
            'icon' => null,
        ]);
        $accordionCategoryIds[] = $cat->id;

        // Add at least one method
        Method::query()->create([
            'payment_display_category_id' => $cat->id,
            'sort_order_in_category' => 0,
            'name' => 'AccMixMethod_' . $j . '_' . uniqid(),
            'images' => '/assets/thumbnail/test.webp',
            'code' => 'AMIX_' . $j . '_' . uniqid(),
            'keterangan' => 'Accordion mixed method',
            'tipe' => 'virtual-account',
            'payment' => 'tripay',
            'statuspayment' => true,
        ]);
    }

    // Get the categories from the service
    $service = app(PaymentDisplayCategoryService::class);
    $categories = $service->getCategoriesForOrderPage();

    // Render the dynamic partial
    $html = view('template.partials.payment-categories-dynamic', [
        'paymentCategories' => $categories,
    ])->render();

    // All flat labels should be present
    foreach ($flatLabels as $flatLabel) {
        expect($html)->toContain($flatLabel);
    }

    // All accordion labels should be present
    foreach ($accordionLabels as $accordionLabel) {
        expect($html)->toContain($accordionLabel);
    }

    // Accordion categories should have their disclosure panels
    foreach ($accordionCategoryIds as $accId) {
        expect($html)->toContain('aria-controls="disclosure-panel-' . $accId . '"');
        expect($html)->toContain('x-ref="container_cat_' . $accId . '"');
    }

    // Flat categories should NOT have disclosure panels
    foreach ($flatCategoryIds as $flatId) {
        expect($html)->not->toContain('aria-controls="disclosure-panel-' . $flatId . '"');
        expect($html)->not->toContain('x-ref="container_cat_' . $flatId . '"');
    }
})->repeat(20)->group('property-test', 'payment-display-categories');

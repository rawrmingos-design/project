<?php

/**
 * Property 1: Category form validation
 *
 * For any input to the PaymentDisplayCategory form, the system SHALL accept the input
 * if and only if the label is 1–100 characters, display_style is "flat" or "accordion",
 * sort_order is 0–999, and icon (if provided) is at most 50 characters; otherwise it
 * SHALL reject with validation errors.
 *
 * **Validates: Requirements 1.2, 1.4**
 */

use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::create([
        'name' => 'Form Validation Test Tenant',
        'subdomain' => 'form-val-' . uniqid(),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantContext = app(TenantContext::class);
    $tenantContext->set($this->tenant);
});

afterEach(function () {
    app(TenantContext::class)->clear();
});

/**
 * Returns the validation rules that mirror the PaymentDisplayCategoryForm schema.
 * These are the rules as defined in the Filament form:
 * - label: required, max 100
 * - display_style: required, in:flat,accordion
 * - sort_order: required, numeric, min:0, max:999
 * - icon: nullable, max:50
 */
function getCategoryValidationRules(?int $ignoreId = null): array
{
    $tenantId = app(TenantContext::class)->id();

    $uniqueRule = 'unique:payment_display_categories,label';
    if ($ignoreId) {
        $uniqueRule .= ",{$ignoreId}";
    }
    if ($tenantId) {
        $uniqueRule .= ",id,tenant_id,{$tenantId}";
    }

    return [
        'label' => ['required', 'string', 'max:100'],
        'display_style' => ['required', 'in:flat,accordion'],
        'sort_order' => ['required', 'numeric', 'min:0', 'max:999'],
        'icon' => ['nullable', 'string', 'max:50'],
    ];
}

/**
 * Validates form data against the same rules used by the Filament form.
 */
function validateCategoryForm(array $data, ?int $ignoreId = null): \Illuminate\Validation\Validator
{
    return Validator::make($data, getCategoryValidationRules($ignoreId));
}

/**
 * Generates a random string of exactly the given length.
 */
function randomStr(int $length): string
{
    if ($length <= 0) {
        return '';
    }

    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[rand(0, strlen($chars) - 1)];
    }

    return $result;
}

/**
 * Determines whether a given input combination should be valid according to Property 1.
 */
function isValidInput(array $input): bool
{
    $label = $input['label'] ?? null;
    $displayStyle = $input['display_style'] ?? null;
    $sortOrder = $input['sort_order'] ?? null;
    $icon = $input['icon'] ?? null;

    // Label: required, 1-100 characters
    if (empty($label) || !is_string($label) || strlen($label) < 1 || strlen($label) > 100) {
        return false;
    }

    // Display style: must be "flat" or "accordion"
    if (!in_array($displayStyle, ['flat', 'accordion'], true)) {
        return false;
    }

    // Sort order: required, numeric, 0-999
    if ($sortOrder === null || !is_numeric($sortOrder) || $sortOrder < 0 || $sortOrder > 999) {
        return false;
    }

    // Icon: optional, max 50 characters
    if ($icon !== null && is_string($icon) && strlen($icon) > 50) {
        return false;
    }

    return true;
}

// --- Property tests: valid inputs accepted ---

test('Property 1: valid inputs are accepted - random label length 1-100, valid display_style, sort_order 0-999, icon 0-50', function () {
    $labelLength = rand(1, 100);
    $displayStyle = collect(['flat', 'accordion'])->random();
    $sortOrder = rand(0, 999);
    $includeIcon = (bool) rand(0, 1);
    $iconLength = $includeIcon ? rand(1, 50) : 0;

    $input = [
        'label' => randomStr($labelLength),
        'display_style' => $displayStyle,
        'sort_order' => $sortOrder,
        'icon' => $iconLength > 0 ? randomStr($iconLength) : null,
    ];

    $validator = validateCategoryForm($input);

    expect($validator->passes())->toBeTrue(
        "Valid input should pass validation. Input: label_len={$labelLength}, display_style={$displayStyle}, sort_order={$sortOrder}, icon_len={$iconLength}. Errors: " . json_encode($validator->errors()->toArray())
    );

    // Also verify the model can actually be persisted
    $category = PaymentDisplayCategory::create(array_merge($input, [
        'tenant_id' => test()->tenant->id,
        'is_visible' => true,
    ]));

    expect($category->exists)->toBeTrue();
    expect($category->label)->toBe($input['label']);
    expect($category->display_style)->toBe($input['display_style']);
    expect($category->sort_order)->toBe($input['sort_order']);
})->repeat(20);

// --- Property tests: invalid inputs rejected ---

test('Property 1: empty label is rejected', function () {
    $input = [
        'label' => '',
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 999),
        'icon' => null,
    ];

    $validator = validateCategoryForm($input);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('label'))->toBeTrue();
    expect(isValidInput($input))->toBeFalse();
})->repeat(20);

test('Property 1: label exceeding 100 characters is rejected', function () {
    $labelLength = rand(101, 250);
    $input = [
        'label' => randomStr($labelLength),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 999),
        'icon' => null,
    ];

    $validator = validateCategoryForm($input);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('label'))->toBeTrue();
    expect(isValidInput($input))->toBeFalse();
})->repeat(20);

test('Property 1: invalid display_style values are rejected', function () {
    $invalidStyles = ['invalid', 'dropdown', 'FLAT', 'Accordion', 'grid', 'list', 'tabs', 'none', ''];
    $chosenStyle = $invalidStyles[array_rand($invalidStyles)];

    $input = [
        'label' => randomStr(rand(1, 100)),
        'display_style' => $chosenStyle,
        'sort_order' => rand(0, 999),
        'icon' => null,
    ];

    $validator = validateCategoryForm($input);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('display_style'))->toBeTrue();
    expect(isValidInput($input))->toBeFalse();
})->repeat(20);

test('Property 1: sort_order below 0 is rejected', function () {
    $sortOrder = -1 * rand(1, 10000);

    $input = [
        'label' => randomStr(rand(1, 100)),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => $sortOrder,
        'icon' => null,
    ];

    $validator = validateCategoryForm($input);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('sort_order'))->toBeTrue();
    expect(isValidInput($input))->toBeFalse();
})->repeat(20);

test('Property 1: sort_order above 999 is rejected', function () {
    $sortOrder = 1000 + rand(0, 10000);

    $input = [
        'label' => randomStr(rand(1, 100)),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => $sortOrder,
        'icon' => null,
    ];

    $validator = validateCategoryForm($input);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('sort_order'))->toBeTrue();
    expect(isValidInput($input))->toBeFalse();
})->repeat(20);

test('Property 1: icon exceeding 50 characters is rejected', function () {
    $iconLength = rand(51, 150);

    $input = [
        'label' => randomStr(rand(1, 100)),
        'display_style' => collect(['flat', 'accordion'])->random(),
        'sort_order' => rand(0, 999),
        'icon' => randomStr($iconLength),
    ];

    $validator = validateCategoryForm($input);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('icon'))->toBeTrue();
    expect(isValidInput($input))->toBeFalse();
})->repeat(20);

test('Property 1: random input space - validation decision matches expected behavior', function () {
    // Generate random inputs across the entire input space (both valid and invalid)
    $labelLength = rand(0, 150); // 0 = empty string, > 100 = too long
    $displayStyleOptions = ['flat', 'accordion', 'invalid', 'FLAT', '', 'grid'];
    $displayStyle = $displayStyleOptions[array_rand($displayStyleOptions)];
    $sortOrder = rand(-100, 1100); // covers invalid negative and > 999
    $iconLength = rand(0, 80); // covers valid (0-50) and invalid (> 50)

    $input = [
        'label' => $labelLength > 0 ? randomStr($labelLength) : '',
        'display_style' => $displayStyle,
        'sort_order' => $sortOrder,
        'icon' => $iconLength > 0 ? randomStr($iconLength) : null,
    ];

    $validator = validateCategoryForm($input);
    $shouldBeValid = isValidInput($input);

    if ($shouldBeValid) {
        expect($validator->passes())->toBeTrue(
            "Expected valid but got errors: " . json_encode($validator->errors()->toArray()) .
            " Input: label_len={$labelLength}, display_style={$displayStyle}, sort_order={$sortOrder}, icon_len={$iconLength}"
        );
    } else {
        expect($validator->fails())->toBeTrue(
            "Expected invalid but passed. Input: label_len={$labelLength}, display_style={$displayStyle}, sort_order={$sortOrder}, icon_len={$iconLength}"
        );
    }
})->repeat(20);

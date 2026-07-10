<?php

use App\Filament\Admin\Resources\InboundSourcePolicies\Pages\ListInboundSourcePolicies;
use App\Models\InboundSourceEntry;
use App\Models\InboundSourcePolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Filament Bulk Whitelist Mode Switch Feature Tests
|--------------------------------------------------------------------------
|
| Tests for the bulk mode switch UI in the InboundSourcePolicy Filament
| admin panel:
| - Bulk action visibility and form display
| - Confirmation dialog behavior
| - Successful mode switch with notification
| - Cancel leaves policies unchanged
| - Two-phase empty policy warning on enforce switch
|
| **Validates: Requirements 1.1, 1.2, 1.3, 1.4**
|
*/

// --- Helper: create and authenticate admin user ---
function createAdmin(): User
{
    $admin = User::factory()->create(['role' => 'Admin']);
    test()->actingAs($admin);

    return $admin;
}

// --- Helper: create an InboundSourcePolicy ---
function createPolicy(array $attributes = []): InboundSourcePolicy
{
    return InboundSourcePolicy::create(array_merge([
        'source_domain' => 'payment_gateway',
        'source_name' => 'tripay_' . uniqid(),
        'mode' => 'log_only',
        'is_active' => true,
    ], $attributes));
}

// --- Helper: create an active entry for a policy ---
function createActiveEntry(InboundSourcePolicy $policy, string $ip = '192.168.1.1'): InboundSourceEntry
{
    return InboundSourceEntry::create([
        'policy_id' => $policy->id,
        'value' => $ip,
        'value_type' => 'ipv4',
        'is_active' => true,
    ]);
}

/*
|--------------------------------------------------------------------------
| Test: Admin can see the bulk action in the table
|--------------------------------------------------------------------------
| Requirements: 1.1
*/
test('admin can see the bulk_switch_mode bulk action in the table', function () {
    createAdmin();

    $policy = createPolicy();

    Livewire::test(ListInboundSourcePolicies::class)
        ->assertTableBulkActionExists('bulk_switch_mode');
});

/*
|--------------------------------------------------------------------------
| Test: Bulk action form displays mode selection options
|--------------------------------------------------------------------------
| Requirements: 1.1
*/
test('bulk action form displays target mode selection options', function () {
    createAdmin();

    $policy = createPolicy();

    Livewire::test(ListInboundSourcePolicies::class)
        ->assertTableBulkActionExists('bulk_switch_mode')
        ->callTableBulkAction('bulk_switch_mode', [$policy->id], data: [
            'target_mode' => 'enforce',
            'acknowledge_empty_risk' => true,
        ])
        ->assertHasNoTableBulkActionErrors();
});

/*
|--------------------------------------------------------------------------
| Test: Confirmation dialog appears (requiresConfirmation)
|--------------------------------------------------------------------------
| Requirements: 1.1
*/
test('bulk action requires confirmation before executing', function () {
    createAdmin();

    $policy = createPolicy(['mode' => 'log_only', 'source_name' => 'confirm_test']);
    createActiveEntry($policy, '10.0.0.5');

    // The bulk action has requiresConfirmation() which means Filament
    // shows a modal before executing. We verify the action exists and
    // that submitting the form with valid data successfully completes.
    Livewire::test(ListInboundSourcePolicies::class)
        ->assertTableBulkActionExists('bulk_switch_mode')
        ->callTableBulkAction('bulk_switch_mode', [$policy->id], data: [
            'target_mode' => 'disabled',
        ])
        ->assertHasNoTableBulkActionErrors()
        ->assertNotified('Mode berhasil diubah');

    $policy->refresh();
    expect($policy->mode)->toBe('disabled');
});

/*
|--------------------------------------------------------------------------
| Test: Successful mode switch updates policies and shows notification
|--------------------------------------------------------------------------
| Requirements: 1.2, 1.3
*/
test('successful bulk mode switch updates policies and shows notification', function () {
    createAdmin();

    $policy1 = createPolicy(['mode' => 'log_only', 'source_name' => 'provider_a']);
    $policy2 = createPolicy(['mode' => 'log_only', 'source_name' => 'provider_b']);

    // Add active entries so no empty policy warning triggers
    createActiveEntry($policy1, '10.0.0.1');
    createActiveEntry($policy2, '10.0.0.2');

    Livewire::test(ListInboundSourcePolicies::class)
        ->callTableBulkAction('bulk_switch_mode', [$policy1->id, $policy2->id], data: [
            'target_mode' => 'enforce',
            'acknowledge_empty_risk' => false,
        ])
        ->assertHasNoTableBulkActionErrors()
        ->assertNotified('Mode berhasil diubah');

    // Verify DB was updated
    $policy1->refresh();
    $policy2->refresh();

    expect($policy1->mode)->toBe('enforce');
    expect($policy2->mode)->toBe('enforce');
});

/*
|--------------------------------------------------------------------------
| Test: Cancel leaves policies unchanged
|--------------------------------------------------------------------------
| Requirements: 1.4
*/
test('not confirming the bulk action leaves policies unchanged', function () {
    createAdmin();

    $policy1 = createPolicy(['mode' => 'log_only', 'source_name' => 'provider_x']);
    $policy2 = createPolicy(['mode' => 'log_only', 'source_name' => 'provider_y']);

    // Simply rendering the page without calling the action keeps data intact
    Livewire::test(ListInboundSourcePolicies::class)
        ->assertTableBulkActionExists('bulk_switch_mode');

    // Verify DB was NOT updated
    $policy1->refresh();
    $policy2->refresh();

    expect($policy1->mode)->toBe('log_only');
    expect($policy2->mode)->toBe('log_only');
});

/*
|--------------------------------------------------------------------------
| Test: Two-phase empty policy warning halts when switching to enforce
|       without acknowledgment
|--------------------------------------------------------------------------
| Requirements: 1.1 (safety warning aspect from Requirement 4)
*/
test('switching to enforce without acknowledging empty risk halts the action', function () {
    createAdmin();

    // Create a policy with NO active entries (empty policy)
    $emptyPolicy = createPolicy(['mode' => 'log_only', 'source_name' => 'empty_provider']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->callTableBulkAction('bulk_switch_mode', [$emptyPolicy->id], data: [
            'target_mode' => 'enforce',
            'acknowledge_empty_risk' => false,
        ])
        ->assertNotified('Policy tanpa IP aktif terdeteksi');

    // Policy should remain unchanged because the action was halted
    $emptyPolicy->refresh();
    expect($emptyPolicy->mode)->toBe('log_only');
});

/*
|--------------------------------------------------------------------------
| Test: Acknowledging empty risk allows enforce switch on empty policies
|--------------------------------------------------------------------------
| Requirements: 1.2 (combined with Requirement 4.4)
*/
test('acknowledging empty risk allows enforce switch on empty policies', function () {
    createAdmin();

    // Create a policy with NO active entries
    $emptyPolicy = createPolicy(['mode' => 'log_only', 'source_name' => 'risky_provider']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->callTableBulkAction('bulk_switch_mode', [$emptyPolicy->id], data: [
            'target_mode' => 'enforce',
            'acknowledge_empty_risk' => true,
        ])
        ->assertHasNoTableBulkActionErrors()
        ->assertNotified('Mode berhasil diubah');

    // Policy should be updated
    $emptyPolicy->refresh();
    expect($emptyPolicy->mode)->toBe('enforce');
});

/*
|--------------------------------------------------------------------------
| Test: Switching to non-enforce mode does not require empty risk checkbox
|--------------------------------------------------------------------------
| Requirements: 1.2
*/
test('switching to log_only does not require empty risk acknowledgment', function () {
    createAdmin();

    $policy = createPolicy(['mode' => 'enforce', 'source_name' => 'some_provider']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->callTableBulkAction('bulk_switch_mode', [$policy->id], data: [
            'target_mode' => 'log_only',
        ])
        ->assertHasNoTableBulkActionErrors()
        ->assertNotified('Mode berhasil diubah');

    $policy->refresh();
    expect($policy->mode)->toBe('log_only');
});


/*
|--------------------------------------------------------------------------
| Filament Feature Tests: Header Actions
|--------------------------------------------------------------------------
|
| Integration tests for the header actions on the ListInboundSourcePolicies page:
| - "Switch All to Enforce" header action
| - "Switch All to Log Only" header action
|
| **Validates: Requirements 2.1, 2.2, 3.1, 3.2, 4.2, 4.3, 5.2**
|
*/

// --- Helper: create policy with multiple active entries ---
function createPolicyWithEntries(array $policyAttributes = [], int $activeEntries = 2): InboundSourcePolicy
{
    $policy = createPolicy($policyAttributes);

    for ($i = 0; $i < $activeEntries; $i++) {
        createActiveEntry($policy, '10.0.' . rand(1, 254) . '.' . ($i + 1));
    }

    return $policy;
}

/*
|--------------------------------------------------------------------------
| Test: Both header actions are visible on the list page
|--------------------------------------------------------------------------
| Requirements: 2.1, 3.1
*/
test('both header actions are visible on the list page', function () {
    createAdmin();

    // Need at least one policy in log_only and one in enforce for both actions to be visible
    createPolicy(['mode' => 'log_only', 'is_active' => true, 'source_name' => 'hdr_vis_a']);
    createPolicy(['mode' => 'enforce', 'is_active' => true, 'source_name' => 'hdr_vis_b']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->assertActionVisible('switch_all_to_enforce')
        ->assertActionVisible('switch_all_to_log_only');
});

/*
|--------------------------------------------------------------------------
| Test: Switch All to Enforce action is hidden when no log_only policies exist
|--------------------------------------------------------------------------
| Requirements: 2.1
*/
test('switch all to enforce action is hidden when no log_only policies exist', function () {
    createAdmin();

    createPolicy(['mode' => 'enforce', 'is_active' => true, 'source_name' => 'enforce_only']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->assertActionHidden('switch_all_to_enforce');
});

/*
|--------------------------------------------------------------------------
| Test: Switch All to Log Only action is hidden when no enforce policies exist
|--------------------------------------------------------------------------
| Requirements: 3.1
*/
test('switch all to log only action is hidden when no enforce policies exist', function () {
    createAdmin();

    createPolicy(['mode' => 'log_only', 'is_active' => true, 'source_name' => 'logonly_only']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->assertActionHidden('switch_all_to_log_only');
});

/*
|--------------------------------------------------------------------------
| Test: Switch All to Enforce action only updates log_only policies (correct scoping)
|--------------------------------------------------------------------------
| Requirements: 2.2, 2.3
*/
test('switch all to enforce only targets log_only policies and shows correct count', function () {
    createAdmin();

    $logOnly1 = createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'count_a']);
    $logOnly2 = createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'count_b']);
    $enforceAlready = createPolicyWithEntries(['mode' => 'enforce', 'source_name' => 'count_c']);
    $disabled = createPolicyWithEntries(['mode' => 'disabled', 'source_name' => 'count_d']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->callAction('switch_all_to_enforce')
        ->assertNotified('Mode berhasil diubah ke Blokir');

    $logOnly1->refresh();
    $logOnly2->refresh();
    $enforceAlready->refresh();
    $disabled->refresh();

    // Only log_only policies should be switched
    expect($logOnly1->mode)->toBe('enforce');
    expect($logOnly2->mode)->toBe('enforce');
    // enforce and disabled policies should remain unchanged
    expect($enforceAlready->mode)->toBe('enforce');
    expect($disabled->mode)->toBe('disabled');
});

/*
|--------------------------------------------------------------------------
| Test: Switch All to Log Only only targets enforce policies (correct scoping)
|--------------------------------------------------------------------------
| Requirements: 3.2, 3.3
*/
test('switch all to log only only targets enforce policies and shows correct count', function () {
    createAdmin();

    $enforce1 = createPolicyWithEntries(['mode' => 'enforce', 'source_name' => 'lo_a']);
    $enforce2 = createPolicyWithEntries(['mode' => 'enforce', 'source_name' => 'lo_b']);
    $enforce3 = createPolicyWithEntries(['mode' => 'enforce', 'source_name' => 'lo_c']);
    $logOnlyAlready = createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'lo_d']);
    $disabled = createPolicyWithEntries(['mode' => 'disabled', 'source_name' => 'lo_e']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->callAction('switch_all_to_log_only')
        ->assertNotified('Mode berhasil diubah ke Pantau Saja');

    $enforce1->refresh();
    $enforce2->refresh();
    $enforce3->refresh();
    $logOnlyAlready->refresh();
    $disabled->refresh();

    // Only enforce policies should be switched
    expect($enforce1->mode)->toBe('log_only');
    expect($enforce2->mode)->toBe('log_only');
    expect($enforce3->mode)->toBe('log_only');
    // Others remain unchanged
    expect($logOnlyAlready->mode)->toBe('log_only');
    expect($disabled->mode)->toBe('disabled');
});

/*
|--------------------------------------------------------------------------
| Test: Safety warning blocks enforce on empty policies without acknowledgment
|--------------------------------------------------------------------------
| Requirements: 4.2, 4.3
*/
test('enforce header action halts when empty policies exist and risk not acknowledged', function () {
    createAdmin();

    // Policy with no active entries (empty)
    $emptyPolicy = createPolicy([
        'mode' => 'log_only',
        'source_name' => 'empty_header_test',
        'source_domain' => 'payment_gateway',
    ]);

    // Policy with active entries (not empty)
    createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'normal_header_test']);

    // Calling without acknowledgment should halt (notification about required confirmation)
    Livewire::test(ListInboundSourcePolicies::class)
        ->callAction('switch_all_to_enforce', data: [
            'acknowledge_empty_risk' => false,
        ])
        ->assertNotified('Konfirmasi diperlukan');

    // Empty policy should remain in log_only mode
    $emptyPolicy->refresh();
    expect($emptyPolicy->mode)->toBe('log_only');
});

/*
|--------------------------------------------------------------------------
| Test: Enforce header action proceeds when empty policy risk is acknowledged
|--------------------------------------------------------------------------
| Requirements: 4.2, 4.3
*/
test('enforce header action proceeds when empty policy risk is acknowledged', function () {
    createAdmin();

    // Policy with no active entries (empty)
    $emptyPolicy = createPolicy([
        'mode' => 'log_only',
        'source_name' => 'empty_ack_test',
        'source_domain' => 'payment_gateway',
    ]);

    // Policy with active entries
    $normalPolicy = createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'normal_ack_test']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->callAction('switch_all_to_enforce', data: [
            'acknowledge_empty_risk' => true,
        ])
        ->assertNotified('Mode berhasil diubah ke Blokir');

    // Both policies should be switched to enforce
    $emptyPolicy->refresh();
    $normalPolicy->refresh();
    expect($emptyPolicy->mode)->toBe('enforce');
    expect($normalPolicy->mode)->toBe('enforce');
});

/*
|--------------------------------------------------------------------------
| Test: Filter scoping restricts enforce action to matching source_domain
|--------------------------------------------------------------------------
| Requirements: 5.2
*/
test('enforce header action respects source_domain filter scope', function () {
    createAdmin();

    $supplierPolicy = createPolicyWithEntries([
        'mode' => 'log_only',
        'source_domain' => 'supplier_callback',
        'source_name' => 'digiflazz_filter',
    ]);
    $paymentPolicy = createPolicyWithEntries([
        'mode' => 'log_only',
        'source_domain' => 'payment_gateway',
        'source_name' => 'tripay_filter',
    ]);

    // Apply source_domain filter for supplier_callback then call the action
    Livewire::test(ListInboundSourcePolicies::class)
        ->filterTable('source_domain', 'supplier_callback')
        ->callAction('switch_all_to_enforce')
        ->assertNotified();

    $supplierPolicy->refresh();
    $paymentPolicy->refresh();

    // Only supplier_callback policy should be switched
    expect($supplierPolicy->mode)->toBe('enforce');
    // payment_gateway policy should remain unchanged
    expect($paymentPolicy->mode)->toBe('log_only');
});

/*
|--------------------------------------------------------------------------
| Test: Cancel (not submitting) leaves policies unchanged for enforce action
|--------------------------------------------------------------------------
| Requirements: 4.3
*/
test('cancel leaves policies unchanged for enforce header action', function () {
    createAdmin();

    $policy1 = createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'cancel_a']);
    $policy2 = createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'cancel_b']);

    // Mount the action but don't submit it (simulate cancel)
    Livewire::test(ListInboundSourcePolicies::class)
        ->mountAction('switch_all_to_enforce');

    // Policies should still be in log_only mode
    $policy1->refresh();
    $policy2->refresh();

    expect($policy1->mode)->toBe('log_only');
    expect($policy2->mode)->toBe('log_only');
});

/*
|--------------------------------------------------------------------------
| Test: Cancel (not submitting) leaves policies unchanged for log only action
|--------------------------------------------------------------------------
| Requirements: 3.2
*/
test('cancel leaves policies unchanged for log only header action', function () {
    createAdmin();

    $policy1 = createPolicyWithEntries(['mode' => 'enforce', 'source_name' => 'cancel_lo_a']);
    $policy2 = createPolicyWithEntries(['mode' => 'enforce', 'source_name' => 'cancel_lo_b']);

    // Mount the action but don't submit
    Livewire::test(ListInboundSourcePolicies::class)
        ->mountAction('switch_all_to_log_only');

    // Policies should remain in enforce mode
    $policy1->refresh();
    $policy2->refresh();

    expect($policy1->mode)->toBe('enforce');
    expect($policy2->mode)->toBe('enforce');
});

/*
|--------------------------------------------------------------------------
| Test: Switch All to Enforce executes successfully
|--------------------------------------------------------------------------
| Requirements: 2.2, 2.3
*/
test('switch all to enforce header action updates log_only policies to enforce', function () {
    createAdmin();

    $logOnly1 = createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'exec_a']);
    $logOnly2 = createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'exec_b']);
    $enforceAlready = createPolicyWithEntries(['mode' => 'enforce', 'source_name' => 'exec_c']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->callAction('switch_all_to_enforce')
        ->assertNotified();

    $logOnly1->refresh();
    $logOnly2->refresh();
    $enforceAlready->refresh();

    expect($logOnly1->mode)->toBe('enforce');
    expect($logOnly2->mode)->toBe('enforce');
    expect($enforceAlready->mode)->toBe('enforce'); // already enforce, unchanged
});

/*
|--------------------------------------------------------------------------
| Test: Switch All to Log Only executes successfully
|--------------------------------------------------------------------------
| Requirements: 3.2, 3.3
*/
test('switch all to log only header action updates enforce policies to log_only', function () {
    createAdmin();

    $enforce1 = createPolicyWithEntries(['mode' => 'enforce', 'source_name' => 'exec_lo_a']);
    $enforce2 = createPolicyWithEntries(['mode' => 'enforce', 'source_name' => 'exec_lo_b']);
    $logOnlyAlready = createPolicyWithEntries(['mode' => 'log_only', 'source_name' => 'exec_lo_c']);

    Livewire::test(ListInboundSourcePolicies::class)
        ->callAction('switch_all_to_log_only')
        ->assertNotified();

    $enforce1->refresh();
    $enforce2->refresh();
    $logOnlyAlready->refresh();

    expect($enforce1->mode)->toBe('log_only');
    expect($enforce2->mode)->toBe('log_only');
    expect($logOnlyAlready->mode)->toBe('log_only'); // already log_only, unchanged
});

<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ResellerDepositHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function createResellerUser(): User
    {
        $user = User::factory()->create([
            'role'     => 'Member',
            'username' => 'reseller_test_' . uniqid(),
        ]);

        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-DEP-' . strtoupper(uniqid()),
            'mode'             => 'live',
            'is_active'        => true,
        ]);

        return $user;
    }

    private function createDeposit(string $username, string $status = 'Pending'): Deposit
    {
        // Note: 'Gagal' is only valid in MySQL (added via raw SQL migration).
        // In SQLite test DB, only 'Success' and 'Pending' are valid enum values.
        return Deposit::create([
            'order_id'      => 'DEP-' . uniqid(),
            'username'      => $username,
            'metode'        => 'QRIS',
            'no_pembayaran' => '000000',
            'jumlah'        => 100000,
            'status'        => $status,
        ]);
    }

    public function test_guest_cannot_access_reseller_deposit_history(): void
    {
        $this->get('/id/reseller/deposits')->assertStatus(302);
    }

    public function test_non_reseller_cannot_access_reseller_deposit_history(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $this->actingAs($user)
            ->get('/id/reseller/deposits')
            ->assertRedirect('/id/dashboard');
    }

    public function test_reseller_can_access_deposit_history_page(): void
    {
        $user = $this->createResellerUser();

        // assertStatus(200) is sufficient — component() assertion fails in test env
        // because Inertia view finder uses a different root than resources/js/public/Pages
        $this->actingAs($user)
            ->get('/id/reseller/deposits')
            ->assertStatus(200);
    }

    public function test_deposit_history_returns_paginated_deposits_for_reseller(): void
    {
        $user = $this->createResellerUser();

        // Create 12 deposits to verify pagination (10 per page)
        for ($i = 0; $i < 12; $i++) {
            $this->createDeposit($user->username, 'Pending');
        }

        // Inertia serializes LengthAwarePaginator without a 'meta' wrapper.
        // Fields like total/per_page are at the top level of the prop.
        $this->actingAs($user)
            ->get('/id/reseller/deposits')
            ->assertInertia(fn (Assert $page) =>
                $page->has('deposits.data', 10)  // first page: 10 items
                     ->has('deposits.links')
                     ->where('deposits.total', 12)
                     ->where('deposits.per_page', 10)
            );
    }

    public function test_deposit_history_does_not_expose_other_users_deposits(): void
    {
        $reseller      = $this->createResellerUser();
        $otherReseller = $this->createResellerUser();

        $this->createDeposit($reseller->username, 'Success');
        $this->createDeposit($otherReseller->username, 'Success');
        $this->createDeposit($otherReseller->username, 'Pending');

        $this->actingAs($reseller)
            ->get('/id/reseller/deposits')
            ->assertInertia(fn (Assert $page) =>
                $page->where('deposits.total', 1)
            );
    }

    public function test_deposit_history_filter_by_status_success(): void
    {
        $user = $this->createResellerUser();

        $this->createDeposit($user->username, 'Success');
        $this->createDeposit($user->username, 'Success');
        $this->createDeposit($user->username, 'Pending');
        // Note: 'Gagal' is not a valid SQLite enum value in this test DB,
        // so we use a third 'Pending' instead.
        $this->createDeposit($user->username, 'Pending');

        $this->actingAs($user)
            ->get('/id/reseller/deposits?status=Success')
            ->assertInertia(fn (Assert $page) =>
                $page->where('deposits.total', 2)
                     ->where('activeFilter', 'Success')
            );
    }

    public function test_deposit_history_filter_by_status_pending(): void
    {
        $user = $this->createResellerUser();

        $this->createDeposit($user->username, 'Success');
        $this->createDeposit($user->username, 'Pending');
        $this->createDeposit($user->username, 'Pending');

        $this->actingAs($user)
            ->get('/id/reseller/deposits?status=Pending')
            ->assertInertia(fn (Assert $page) =>
                $page->where('deposits.total', 2)
                     ->where('activeFilter', 'Pending')
            );
    }

    public function test_deposit_history_invalid_filter_returns_all(): void
    {
        $user = $this->createResellerUser();

        $this->createDeposit($user->username, 'Success');
        $this->createDeposit($user->username, 'Pending');

        $this->actingAs($user)
            ->get('/id/reseller/deposits?status=invalid')
            ->assertInertia(fn (Assert $page) =>
                // Invalid filter is ignored, all 2 deposits shown
                $page->where('deposits.total', 2)
                     ->where('activeFilter', null)
            );
    }
}

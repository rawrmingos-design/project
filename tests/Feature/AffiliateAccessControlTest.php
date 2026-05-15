<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AffiliateAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_and_withdrawal_routes_have_expected_middlewares(): void
    {
        $depositMiddleware = Route::getRoutes()->getByName('deposit')?->gatherMiddleware() ?? [];
        $depositStoreMiddleware = Route::getRoutes()->getByName('deposit.store')?->gatherMiddleware() ?? [];
        $withdrawalMiddleware = Route::getRoutes()->getByName('withdrawal')?->gatherMiddleware() ?? [];
        $withdrawalStoreMiddleware = Route::getRoutes()->getByName('process.withdrawal')?->gatherMiddleware() ?? [];

        $this->assertTrue(
            in_array('non-affiliate.only', $depositMiddleware, true)
            || in_array(\App\Http\Middleware\EnsureNonAffiliateUser::class, $depositMiddleware, true)
        );
        $this->assertTrue(
            in_array('non-affiliate.only', $depositStoreMiddleware, true)
            || in_array(\App\Http\Middleware\EnsureNonAffiliateUser::class, $depositStoreMiddleware, true)
        );
        $this->assertTrue(
            in_array('affiliate.only', $withdrawalMiddleware, true)
            || in_array(\App\Http\Middleware\EnsureAffiliateUser::class, $withdrawalMiddleware, true)
        );
        $this->assertTrue(
            in_array('affiliate.only', $withdrawalStoreMiddleware, true)
            || in_array(\App\Http\Middleware\EnsureAffiliateUser::class, $withdrawalStoreMiddleware, true)
        );
        $this->assertContains('throttle:public-deposit-submit', $depositStoreMiddleware);
        $this->assertContains('throttle:public-affiliate-request', Route::getRoutes()->getByName('affiliate.request')?->gatherMiddleware() ?? []);
        $this->assertContains('throttle:public-withdrawal-submit', $withdrawalStoreMiddleware);
    }

    public function test_active_affiliate_is_redirected_from_deposit_pages(): void
    {
        $affiliate = User::factory()->create([
            'role' => 'Member',
            'affiliate_status' => 'active',
        ]);

        $this->actingAs($affiliate)
            ->get('/id/deposit')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($affiliate)
            ->post('/id/deposit', [
                'jumlah' => 10000,
                'metode' => 'QRIS',
                'no_pembayaran' => '628123456789',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_non_affiliate_is_redirected_from_withdrawal_pages(): void
    {
        $member = User::factory()->create([
            'role' => 'Member',
            'affiliate_status' => 'inactive',
        ]);

        $this->actingAs($member)
            ->get('/id/withdrawal')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($member)
            ->post('/id/withdrawal', [
                'bank_destination' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'John Doe',
                'amount' => 10000,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_affiliate_cannot_withdraw_more_than_current_balance(): void
    {
        $affiliate = User::factory()->create([
            'role' => 'Member',
            'affiliate_status' => 'active',
            'balance' => 20000,
        ]);

        $this->actingAs($affiliate)
            ->post('/id/withdrawal', [
                'bank_destination' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'John Doe',
                'amount' => 30000,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount((new Withdrawal())->getTable(), 0);
    }

    public function test_affiliate_can_only_submit_one_withdrawal_request_per_day(): void
    {
        $affiliate = User::factory()->create([
            'role' => 'Member',
            'affiliate_status' => 'active',
            'balance' => 100000,
        ]);

        $payload = [
            'bank_destination' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'John Doe',
            'amount' => 50000,
        ];

        $this->actingAs($affiliate)
            ->post('/id/withdrawal', $payload)
            ->assertSessionHas('success');

        $this->actingAs($affiliate)
            ->post('/id/withdrawal', $payload)
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount((new Withdrawal())->getTable(), 1);
        $this->assertDatabaseHas('users', [
            'id' => $affiliate->id,
            'balance' => 50000,
        ]);
    }
}

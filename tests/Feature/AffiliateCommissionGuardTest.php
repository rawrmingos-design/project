<?php

namespace Tests\Feature;

use App\Models\AffiliateHistory;
use App\Models\Pembelian;
use App\Models\User;
use App\Services\AffiliateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateCommissionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_is_skipped_when_uplink_is_not_active(): void
    {
        $uplink = User::factory()->create([
            'username' => 'uplink.inactive',
            'affiliate_status' => 'inactive',
            'balance' => 50_000,
        ]);

        $downline = User::factory()->create([
            'username' => 'downline.member',
            'uplink' => $uplink->username,
        ]);

        $order = $this->makeSuccessfulOrder($downline, 'AFF-NO-PAYOUT-001', 2_000);

        app(AffiliateService::class)->processCommission($order);

        $this->assertDatabaseMissing('affiliate_histories', [
            'order_id' => 'AFF-NO-PAYOUT-001',
        ]);
        $this->assertSame(50_000, (int) $uplink->fresh()->balance);
    }

    public function test_commission_is_paid_when_uplink_is_active(): void
    {
        $uplink = User::factory()->create([
            'username' => 'uplink.active',
            'affiliate_status' => 'active',
            'balance' => 10_000,
        ]);

        $downline = User::factory()->create([
            'username' => 'downline.payout',
            'uplink' => $uplink->username,
        ]);

        $order = $this->makeSuccessfulOrder($downline, 'AFF-PAYOUT-001', 2_000);

        app(AffiliateService::class)->processCommission($order);

        $this->assertDatabaseHas('affiliate_histories', [
            'order_id' => 'AFF-PAYOUT-001',
            'uplink_id' => (string) $uplink->id,
            'downlink_id' => (string) $downline->id,
            'amount' => 400, // Default commission 20% from profit 2,000.
        ]);
        $this->assertSame(10_400, (int) $uplink->fresh()->balance);
    }

    public function test_commission_is_blocked_for_self_referral(): void
    {
        $user = User::factory()->create([
            'username' => 'self.ref',
            'uplink' => 'self.ref',
            'affiliate_status' => 'active',
            'balance' => 70_000,
        ]);

        $order = $this->makeSuccessfulOrder($user, 'AFF-SELF-001', 3_000);

        app(AffiliateService::class)->processCommission($order);

        $this->assertDatabaseMissing('affiliate_histories', [
            'order_id' => 'AFF-SELF-001',
        ]);
        $this->assertSame(70_000, (int) $user->fresh()->balance);
    }

    public function test_commission_processing_remains_idempotent_for_same_order(): void
    {
        $uplink = User::factory()->create([
            'username' => 'uplink.idempotent',
            'affiliate_status' => 'active',
            'balance' => 15_000,
        ]);

        $downline = User::factory()->create([
            'username' => 'downline.idempotent',
            'uplink' => $uplink->username,
        ]);

        $order = $this->makeSuccessfulOrder($downline, 'AFF-IDEMPOTENT-001', 4_000);
        $service = app(AffiliateService::class);

        $service->processCommission($order);
        $service->processCommission($order);

        $this->assertSame(
            1,
            AffiliateHistory::query()->where('order_id', 'AFF-IDEMPOTENT-001')->count()
        );
        $this->assertSame(15_800, (int) $uplink->fresh()->balance);
    }

    private function makeSuccessfulOrder(User $downline, string $orderId, int $profit): Pembelian
    {
        $order = new Pembelian();
        $order->order_id = $orderId;
        $order->username = (string) $downline->username;
        $order->status = 'Success';
        $order->profit = $profit;
        $order->harga = $profit + 1_000;
        $order->setRelation('user', $downline);

        return $order;
    }
}

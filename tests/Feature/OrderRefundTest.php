<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\ResellerIntegration;
use App\Models\User;
use App\Services\OrderRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRefundTest extends TestCase
{
    use RefreshDatabase;

    private OrderRefundService $refundService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refundService = app(OrderRefundService::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUser(int $balance = 100_000): User
    {
        return User::factory()->create([
            'username' => 'reseller.refund.' . uniqid(),
            'balance'  => $balance,
        ]);
    }

    private function makeH2hOrder(User $user, string $status = 'Gagal', int $harga = 10_000): Pembelian
    {
        return Pembelian::factory()->create([
            'username'                => $user->username,
            'reseller_integration_id' => ResellerIntegration::factory()->create([
                'user_id'          => $user->getKey(),
                'integration_code' => 'live-' . uniqid(),
                'mode'             => 'live',
                'is_active'        => true,
            ])->getKey(),
            'traffic_source' => 'reseller_h2h',
            'harga'          => $harga,
            'status'         => $status,
            'is_sandbox'     => false,
        ]);
    }

    private function makeWebOrder(User $user, string $status = 'Gagal', int $harga = 10_000): Pembelian
    {
        return Pembelian::factory()->create([
            'username'                => $user->username,
            'reseller_integration_id' => null,      // web order — no integration
            'traffic_source'          => 'direct',
            'harga'                   => $harga,
            'status'                  => $status,
            'is_sandbox'              => false,
        ]);
    }

    // ── Tests: Eligibility guards ─────────────────────────────────────────────

    public function test_h2h_gagal_order_triggers_refund(): void
    {
        $user  = $this->makeUser(100_000);
        $order = $this->makeH2hOrder($user, 'Gagal', 15_000);

        $result = $this->refundService->refundIfEligible($order);

        $this->assertTrue($result, 'refundIfEligible should return true for eligible H2H Gagal order');
        $this->assertEquals(115_000, $user->fresh()->balance,
            'User balance should be restored after refund');
    }

    public function test_h2h_batal_order_triggers_refund(): void
    {
        $user  = $this->makeUser(50_000);
        $order = $this->makeH2hOrder($user, 'Batal', 20_000);

        $result = $this->refundService->refundIfEligible($order);

        $this->assertTrue($result);
        $this->assertEquals(70_000, $user->fresh()->balance);
    }

    public function test_h2h_sukses_order_does_not_trigger_refund(): void
    {
        $user  = $this->makeUser(100_000);
        $order = $this->makeH2hOrder($user, 'Sukses', 10_000);

        $result = $this->refundService->refundIfEligible($order);

        $this->assertFalse($result, 'Successful order should not be refunded');
        $this->assertEquals(100_000, $user->fresh()->balance,
            'Balance should be unchanged for successful order');
    }

    public function test_h2h_pending_order_does_not_trigger_refund(): void
    {
        $user  = $this->makeUser(100_000);
        $order = $this->makeH2hOrder($user, 'Pending', 10_000);

        $result = $this->refundService->refundIfEligible($order);

        $this->assertFalse($result);
        $this->assertEquals(100_000, $user->fresh()->balance);
    }

    public function test_web_order_does_not_trigger_refund(): void
    {
        $user  = $this->makeUser(100_000);
        $order = $this->makeWebOrder($user, 'Gagal', 10_000);

        $result = $this->refundService->refundIfEligible($order);

        $this->assertFalse($result, 'Web orders (no integration) should not auto-refund');
        $this->assertEquals(100_000, $user->fresh()->balance);
    }

    // ── Tests: Idempotency ────────────────────────────────────────────────────

    public function test_refund_is_idempotent_second_call_skipped(): void
    {
        $user  = $this->makeUser(100_000);
        $order = $this->makeH2hOrder($user, 'Gagal', 10_000);

        // First call — should refund
        $first = $this->refundService->refundIfEligible($order);
        $this->assertTrue($first);
        $this->assertEquals(110_000, $user->fresh()->balance);

        // Second call with fresh model — should be skipped (already refunded)
        $second = $this->refundService->refundIfEligible($order->fresh());
        $this->assertFalse($second, 'Second refund call should be skipped (idempotency)');
        $this->assertEquals(110_000, $user->fresh()->balance,
            'Balance should not change on second refund attempt');
    }

    public function test_refunded_at_is_set_after_successful_refund(): void
    {
        $user  = $this->makeUser(100_000);
        $order = $this->makeH2hOrder($user, 'Gagal', 10_000);

        $this->assertNull($order->refunded_at, 'refunded_at should be null before refund');

        $this->refundService->refundIfEligible($order);

        $fresh = $order->fresh();
        $this->assertNotNull($fresh->refunded_at, 'refunded_at should be set after refund');
        $this->assertEquals(10_000, $fresh->refund_amount, 'refund_amount should match harga');
    }

    public function test_refund_amount_stored_correctly(): void
    {
        $user  = $this->makeUser(200_000);
        $order = $this->makeH2hOrder($user, 'Batal', 35_000);

        $this->refundService->refundIfEligible($order);

        $fresh = $order->fresh();
        $this->assertEquals(35_000, $fresh->refund_amount);
    }

    // ── Tests: Webhook integration ────────────────────────────────────────────

    public function test_digiflazz_webhook_with_gagal_status_triggers_refund(): void
    {
        $secret  = 'test-webhook-refund-secret';
        $user    = $this->makeUser(100_000);

        // Create a live H2H order in pending state (as if it was just submitted)
        $order = $this->makeH2hOrder($user, 'Pending', 25_000);

        config(['providers.digiflazz.webhook_secret' => $secret]);

        // We need to compute a signature that matches what $request->getContent() will return.
        // Laravel's json() helper encodes the array consistently so we sign that encoded form.
        $data    = ['ref_id' => $order->order_id, 'status' => 'Gagal', 'sn' => null];
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sig     = hash_hmac('sha256', $encoded, $secret);

        // withoutMiddleware('inbound.whitelist') to skip IP whitelist in test env
        $this->withoutMiddleware('inbound.whitelist')
             ->withoutMiddleware(\App\Http\Middleware\InboundSourceWhitelist::class)
             ->withHeaders(['X-Digiflazz-Signature' => $sig])
             ->json('POST', '/api/webhooks/digiflazz', $data)
             ->assertOk();

        $this->assertEquals(125_000, $user->fresh()->balance,
            'Balance should be restored after Digiflazz webhook reports Gagal');
        $this->assertNotNull($order->fresh()->refunded_at,
            'refunded_at should be set after webhook-triggered refund');
    }

    public function test_digiflazz_webhook_with_sukses_does_not_refund(): void
    {
        $secret = 'test-webhook-refund-secret';
        $user   = $this->makeUser(100_000);
        $order  = $this->makeH2hOrder($user, 'Pending', 25_000);

        config(['providers.digiflazz.webhook_secret' => $secret]);

        $data    = ['ref_id' => $order->order_id, 'status' => 'Sukses', 'sn' => 'SN-123456'];
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sig     = hash_hmac('sha256', $encoded, $secret);

        $this->withoutMiddleware('inbound.whitelist')
             ->withoutMiddleware(\App\Http\Middleware\InboundSourceWhitelist::class)
             ->withHeaders(['X-Digiflazz-Signature' => $sig])
             ->json('POST', '/api/webhooks/digiflazz', $data)
             ->assertOk();

        $this->assertEquals(100_000, $user->fresh()->balance,
            'Balance should NOT change when order succeeds');
        $this->assertNull($order->fresh()->refunded_at);
    }
}

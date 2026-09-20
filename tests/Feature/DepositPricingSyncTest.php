<?php

namespace Tests\Feature;

use App\Http\Controllers\TriPayController;
use App\Models\Deposit;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\User;
use App\Services\Deposit\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Deposit pricing must be consistent between what the form promises and what the
 * customer is actually charged — and it must follow the SAME convention as the order
 * checkout, so the two flows cannot drift apart.
 *
 * Real bug (staging, QRIS via Tripay): the form showed Rp 50.450 (nominal 50.000 + admin
 * fee 450) while Tripay charged Rp 51.554, because Tripay adds its own customer fee
 * (flat 750 + 0.70%) ON TOP of the amount we ask it to collect.
 *
 * Convention (matching OrderController + GatewayPricingService):
 *   - the customer pays exactly `nominal + admin fee`
 *   - the request sent to Tripay is REDUCED by the gateway fee so that lands on that total
 *   - the gateway's own fee is absorbed by the store, never shown as a second charge
 */
class DepositPricingSyncTest extends TestCase
{
    use RefreshDatabase;

    private const NET_AMOUNT = 50000;

    /** ceil(50000 * 0.70%) + 100 = 350 + 100 */
    private const ADMIN_FEE = 450;

    /** What the customer pays and the invoice displays: nominal + admin fee. */
    private const TOTAL_AMOUNT = 50450;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('setting_webs')->insert([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test,web',
            'url_wa' => 'https://wa.me/123',
            'url_ig' => 'https://ig.com/test',
            'url_tiktok' => 'https://tiktok.com/test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://fb.com/test',
            'topupindo_api' => 'fake_api',
            'warna1' => '#fff',
            'warna2' => '#fff',
            'warna3' => '#fff',
            'warna4' => '#fff',
            'paydisini_apikey' => 'fake',
            'order_prefik' => 'TRX',
            'deposit_jalur' => 'tripay',
            'tripay_api' => 'fake_api_key',
            'tripay_merchant_code' => 'T1234',
            'tripay_private_key' => 'fake_private',
        ]);

        Method::create([
            'name' => 'QRIS',
            'code' => 'QRIS',
            'tipe' => 'qris',
            'images' => 'qris.png',
            'keterangan' => 'QRIS',
            'payment' => 'tripay',
            'fee_percent' => 0.70,
            'fix_fee' => 100,
            'min_pembelian' => 100,
            'max_pembelian' => 15000000,
            'statuspayment' => 1,
        ]);
    }

    /**
     * Tripay adds the customer fee on top of the requested amount, exactly like the real
     * gateway (verified: requested 50.450 -> amount 51.554, fee_customer 1.104). The fake
     * mirrors that so a wrong request amount cannot silently pass.
     */
    private function fakeGateways(): void
    {
        $this->app->instance(TriPayController::class, new class extends TriPayController
        {
            private function customerFeeFor(int $amount): int
            {
                return 750 + (int) ceil($amount * 0.007);
            }

            public function feeBreakdown($jumlah, $code): array
            {
                $customer = $this->customerFeeFor((int) $jumlah);

                return ['customer' => $customer, 'merchant' => 0, 'total' => $customer];
            }

            public function request($idOrder, $jumlah, $method, $dataUser, $nohp)
            {
                return [
                    'success' => true,
                    // The gateway bills its own fee on top of what we request.
                    'amount' => (int) $jumlah + $this->customerFeeFor((int) $jumlah),
                    'no_pembayaran' => 'QR-' . $idOrder,
                    'payment_code' => null,
                    'qr_url' => 'https://tripay.test/qr/' . $idOrder,
                    'qr_payload' => 'PAYLOAD-' . $idOrder,
                    'pay_url' => 'https://tripay.test/checkout/' . $idOrder,
                    'reference' => 'REF-' . $idOrder,
                    'expired_at' => now()->addDay()->toIso8601String(),
                ];
            }
        });
    }

    public function test_the_quote_returns_the_amount_the_customer_pays(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($user)->postJson(route('deposit.quote'), [
            'jumlah' => self::NET_AMOUNT,
            'metode' => 'QRIS',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $data = $response->json('data');

        $this->assertSame(self::NET_AMOUNT, $data['net_amount']);
        $this->assertSame(self::ADMIN_FEE, $data['admin_fee']);

        // The customer pays nominal + admin fee — the same rule as the order checkout.
        $this->assertSame(self::TOTAL_AMOUNT, $data['total_amount']);
        $this->assertSame(
            $data['net_amount'] + $data['admin_fee'],
            $data['total_amount'],
            'displayed total must equal nominal + admin fee',
        );

        // We must ask the gateway for LESS than the total, so that its own fee lands on it.
        $this->assertLessThan($data['total_amount'], $data['gateway_amount']);
        $this->assertGreaterThan(0, $data['gateway_fee']);

        // ...and the absorbed fee must be exactly the gap.
        $this->assertSame($data['total_amount'] - $data['gateway_amount'], $data['gateway_fee']);
    }

    public function test_the_gateway_is_asked_for_an_amount_that_lands_on_the_displayed_total(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'Member']);

        $result = app(DepositService::class)->create($user, [
            'jumlah' => self::NET_AMOUNT,
            'metode' => 'QRIS',
            'source' => 'web',
        ]);

        $this->assertTrue($result['success'], 'deposit creation failed');

        // The fake gateway charges request + its own fee. That final charge is what the
        // customer is billed, and it must be exactly the total we displayed.
        $this->assertSame(self::TOTAL_AMOUNT, (int) $result['total_amount']);
        $this->assertSame(
            (int) $result['gateway_amount'] + (int) $result['gateway_fee'],
            (int) $result['total_amount'],
        );
    }

    public function test_the_json_response_exposes_the_total_the_customer_pays(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'Member']);

        // The reseller deposit modal renders `total_amount` as "Total Payment".
        $response = $this->actingAs($user)->postJson(route('deposit.store'), [
            'jumlah' => self::NET_AMOUNT,
            'metode' => 'QRIS',
            'no_telfon' => '6281200000001',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('amount', self::NET_AMOUNT)
            ->assertJsonPath('fee', self::ADMIN_FEE)
            ->assertJsonPath('total_amount', self::TOTAL_AMOUNT)
            ->assertJsonPath('gross_amount', self::TOTAL_AMOUNT - 1096);
    }

    public function test_the_quote_endpoint_rejects_unknown_payment_methods(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'Member']);

        $this->actingAs($user)
            ->postJson(route('deposit.quote'), ['jumlah' => self::NET_AMOUNT, 'metode' => 'NOPE'])
            ->assertStatus(422);
    }

    /**
     * Duitku collects exactly what we ask for, so we must request the full total — adding a
     * gateway fee term would invent a charge the gateway never takes.
     */
    public function test_duitku_deposits_carry_no_gateway_fee(): void
    {
        $this->fakeGateways();
        Method::create([
            'name' => 'DANA',
            'code' => 'DANA',
            'tipe' => 'e-walet',
            'images' => 'dana.png',
            'keterangan' => 'DANA',
            'payment' => 'duitku',
            'fee_percent' => 1.5,
            'fix_fee' => 0,
            'min_pembelian' => 1000,
            'statuspayment' => 1,
        ]);
        DB::table('setting_webs')->where('id', 1)->update(['deposit_jalur' => 'duitku']);

        $user = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($user)->postJson(route('deposit.quote'), [
            'jumlah' => self::NET_AMOUNT,
            'metode' => 'DANA',
        ]);

        $response->assertOk();

        $data = $response->json('data');
        $this->assertSame(750, $data['admin_fee']);   // ceil(50000 * 1.5%)
        $this->assertSame(0, $data['gateway_fee']);
        $this->assertSame(50750, $data['total_amount']);

        // No fee to reverse: the gateway is asked for the full displayed total.
        $this->assertSame(50750, $data['gateway_amount']);
    }

    /**
     * The invoice is what the customer pays against, and it must show exactly ONE fee row
     * ("Biaya") like the order invoice — showing the absorbed gateway fee as a second line
     * double-counts it and is what made the previous screen confusing.
     */
    public function test_the_invoice_shows_one_fee_row_that_adds_up_to_the_total(): void
    {
        $this->fakeGateways();
        DB::table('setting_webs')->where('id', 1)->update(['public_theme' => 'istanatopup']);
        $user = User::factory()->create(['role' => 'Member']);

        $result = app(DepositService::class)->create($user, [
            'jumlah' => self::NET_AMOUNT,
            'metode' => 'QRIS',
            'source' => 'web',
        ]);

        $response = $this->actingAs($user)->get(route('deposit.invoice', [
            'order' => $result['order_id'],
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/DepositInvoice')
                ->where('invoice.amount.subtotal', self::NET_AMOUNT)
                ->where('invoice.amount.adminFee', self::ADMIN_FEE)
                ->where('invoice.amount.fee', self::ADMIN_FEE)
                ->where('invoice.amount.total', self::TOTAL_AMOUNT)
            );

        $amount = $response->viewData('page')['props']['invoice']['amount'];

        // The rows the customer reads must sum to the total they are charged.
        $this->assertSame($amount['total'], $amount['subtotal'] + $amount['fee']);
        $this->assertSame(self::ADMIN_FEE, $amount['fee']);
    }

    public function test_deposit_result_reports_the_amount_the_customer_actually_pays(): void
    {
        $this->fakeGateways();
        $user = User::factory()->create(['role' => 'Member']);

        $result = app(DepositService::class)->create($user, [
            'jumlah' => self::NET_AMOUNT,
            'metode' => 'QRIS',
            'source' => 'web',
        ]);

        $this->assertTrue($result['success'], 'deposit creation failed');

        $payment = Pembayaran::query()->where('order_id', $result['order_id'])->firstOrFail();
        $deposit = Deposit::query()->where('order_id', $result['order_id'])->firstOrFail();

        // Balance credited is still the nominal the user asked for.
        $this->assertSame(self::NET_AMOUNT, (int) $deposit->jumlah);

        // The invoice total is what the customer pays: nominal + admin fee.
        $this->assertSame(self::TOTAL_AMOUNT, (int) $payment->harga);

        // ...and the service result must agree with the invoice, otherwise the form and the
        // invoice disagree again.
        $this->assertSame((int) $payment->harga, (int) $result['total_amount']);
        $this->assertSame(self::ADMIN_FEE, (int) $result['fee']);
    }
}

<?php

namespace Tests\Feature;

use App\Jobs\SendTikTokConversionJob;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\TikTokConversionDelivery;
use App\Services\TikTokDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TikTokConversionDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.tiktok.pixel_id' => 'C123456789012345',
            'services.tiktok.access_token' => 'tiktok-test-token',
        ]);
    }

    public function test_successful_paid_web_order_dispatches_exactly_one_delivery(): void
    {
        Queue::fake();

        $order = $this->createOrder(['status' => 'Pending']);
        $this->createPayment($order, 'Lunas');

        $order->update(['status' => 'Sukses']);
        $order->update(['status' => 'Success']);

        $this->assertDatabaseCount('tiktok_conversion_deliveries', 1);
        $this->assertDatabaseHas('tiktok_conversion_deliveries', [
            'pembelian_id' => $order->id,
            'event_name' => 'CompletePayment',
            'event_id' => $order->display_invoice_id,
            'delivery_status' => 'pending',
        ]);

        Queue::assertPushed(SendTikTokConversionJob::class, 1);
    }

    public function test_legacy_web_order_without_traffic_source_remains_eligible(): void
    {
        Queue::fake();

        $order = $this->createOrder([
            'order_id' => 'INV-TIKTOK-LEGACY',
            'status' => 'Sukses',
            'traffic_source' => null,
        ]);
        $this->createPayment($order, 'Lunas');

        $this->assertDatabaseCount('tiktok_conversion_deliveries', 1);
        Queue::assertPushed(SendTikTokConversionJob::class, 1);
    }

    public function test_payment_becoming_paid_after_success_order_dispatches_delivery(): void
    {
        Queue::fake();

        $order = $this->createOrder(['status' => 'Sukses']);
        $payment = $this->createPayment($order, 'Belum Lunas');

        Queue::assertNothingPushed();

        $payment->update(['status' => 'Lunas', 'paid_at' => now()]);

        $this->assertDatabaseCount('tiktok_conversion_deliveries', 1);
        Queue::assertPushed(SendTikTokConversionJob::class, 1);
    }

    public function test_non_web_tenant_and_sandbox_orders_are_excluded(): void
    {
        Queue::fake();

        $apiOrder = $this->createOrder([
            'order_id' => 'INV-TIKTOK-API',
            'status' => 'Sukses',
            'traffic_source' => 'reseller_h2h',
        ]);
        $this->createPayment($apiOrder, 'Lunas');

        $tenant = Tenant::query()->create([
            'name' => 'Tenant TikTok',
            'subdomain' => 'tenant-tiktok',
            'status' => 'active',
        ]);
        $tenantOrder = $this->createOrder([
            'order_id' => 'INV-TIKTOK-TENANT',
            'tenant_id' => $tenant->id,
            'status' => 'Sukses',
        ]);
        $this->createPayment($tenantOrder, 'Lunas', ['tenant_id' => $tenant->id]);

        $sandboxOrder = $this->createOrder([
            'order_id' => 'INV-TIKTOK-SANDBOX',
            'status' => 'Sukses',
            'environment' => 'sandbox',
            'is_sandbox' => true,
        ]);
        $this->createPayment($sandboxOrder, 'Lunas');

        $this->assertDatabaseCount('tiktok_conversion_deliveries', 0);
        Queue::assertNothingPushed();
    }

    public function test_delivery_payload_uses_complete_payment_numeric_value_and_hashed_identity(): void
    {
        Queue::fake();
        Http::fake([
            'business-api.tiktok.com/*' => Http::response([
                'code' => 0,
                'message' => 'OK',
                'data' => [],
            ], 200),
        ]);

        $order = $this->createOrder([
            'status' => 'Sukses',
            'email_pembeli' => ' Buyer@Example.COM ',
        ]);
        $this->createPayment($order, 'Lunas', [
            'harga' => 27_500,
            'no_pembeli' => '0812-3456-7890',
            'paid_at' => now()->subMinute(),
        ]);

        $delivery = TikTokConversionDelivery::query()->firstOrFail();
        app(TikTokDeliveryService::class)->executeDelivery($delivery->id);

        Http::assertSent(function ($request) use ($order): bool {
            $payload = $request->data();
            $event = $payload['data'][0];

            return $request->url() === 'https://business-api.tiktok.com/open_api/v1.3/event/track/'
                && $request->hasHeader('Access-Token', 'tiktok-test-token')
                && $payload['event_source'] === 'web'
                && $event['event'] === 'CompletePayment'
                && $event['event_id'] === $order->display_invoice_id
                && $event['properties']['value'] === 27_500
                && is_int($event['properties']['value'])
                && $event['properties']['currency'] === 'IDR'
                && $event['user']['email'] === hash('sha256', 'buyer@example.com')
                && $event['user']['phone'] === hash('sha256', '+6281234567890')
                && $event['user']['ttclid'] === 'CLICK-123'
                && $event['user']['ttp'] === 'TTP-123';
        });

        $this->assertSame('delivered', $delivery->fresh()->delivery_status);
        $this->assertSame(1, $delivery->fresh()->attempts);
    }

    public function test_delivery_uses_latest_database_token_and_test_event_code(): void
    {
        Queue::fake();
        Http::fake([
            'business-api.tiktok.com/*' => Http::response([
                'code' => 0,
                'message' => 'OK',
                'data' => [],
            ], 200),
        ]);

        $settings = $this->createSettings([
            'tiktok_tracking_enabled' => true,
            'tiktok_pixel_id' => 'CDB1234567890123',
            'tiktok_access_token' => 'database-token-old',
            'tiktok_test_event_code' => 'DB-TEST-CODE',
        ]);
        $order = $this->createOrder(['status' => 'Sukses']);
        $this->createPayment($order, 'Lunas');
        $delivery = TikTokConversionDelivery::query()->firstOrFail();

        $settings->fill(['tiktok_access_token' => 'database-token-latest'])->save();
        app(TikTokDeliveryService::class)->executeDelivery($delivery->id);

        Http::assertSent(fn ($request): bool => $request->hasHeader('Access-Token', 'database-token-latest')
            && $request->data()['test_event_code'] === 'DB-TEST-CODE');
    }

    public function test_pending_delivery_is_failed_when_effective_pixel_changes(): void
    {
        Queue::fake();
        Http::fake();

        $settings = $this->createSettings([
            'tiktok_tracking_enabled' => true,
            'tiktok_pixel_id' => 'CDB1234567890123',
            'tiktok_access_token' => 'database-token',
        ]);
        $order = $this->createOrder(['status' => 'Sukses']);
        $this->createPayment($order, 'Lunas');
        $delivery = TikTokConversionDelivery::query()->firstOrFail();

        $settings->update(['tiktok_pixel_id' => 'CDB9999999999999']);
        app(TikTokDeliveryService::class)->executeDelivery($delivery->id);

        Http::assertNothingSent();
        $this->assertSame('failed', $delivery->fresh()->delivery_status);
    }

    public function test_connection_timeout_is_marked_ambiguous_and_rethrown_for_retry(): void
    {
        Queue::fake();
        Http::fake([
            'business-api.tiktok.com/*' => fn () => throw new ConnectionException('timeout'),
        ]);

        $order = $this->createOrder(['status' => 'Sukses']);
        $this->createPayment($order, 'Lunas');
        $delivery = TikTokConversionDelivery::query()->firstOrFail();

        try {
            app(TikTokDeliveryService::class)->executeDelivery($delivery->id);
            $this->fail('Expected connection exception was not thrown.');
        } catch (ConnectionException) {
            $delivery->refresh();
            $this->assertSame('ambiguous', $delivery->delivery_status);
            $this->assertSame(1, $delivery->attempts);
        }
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'TikTok Delivery Test',
            'deskripsi_web' => 'TikTok delivery test',
            'keywords' => 'tiktok,test',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => '-',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => '-',
            'order_prefik' => 'INV',
        ], $overrides));
    }

    private function createOrder(array $overrides = []): Pembelian
    {
        return Pembelian::query()->create(array_merge([
            'order_id' => 'INV-TIKTOK-001',
            'username' => 'Anonim',
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'TikTok Buyer',
            'layanan' => 'Membership Mingguan',
            'active_provider_sku' => 'SKU-WEEKLY',
            'harga' => 27_500,
            'profit' => 1_000,
            'provider_order_id' => '',
            'status' => 'Pending',
            'traffic_source' => 'TikTok',
            'tipe_transaksi' => 'game',
            'email_pembeli' => 'buyer@example.com',
            'ip_address' => '203.0.113.10',
            'client_user_agent' => 'Mozilla/5.0 TikTok Test',
            'ttclid' => 'CLICK-123',
            'ttp' => 'TTP-123',
            'environment' => 'live',
            'is_sandbox' => false,
        ], $overrides));
    }

    private function createPayment(Pembelian $order, string $status, array $overrides = []): Pembayaran
    {
        return Pembayaran::query()->create(array_merge([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->order_id,
            'harga' => 27_500,
            'no_pembayaran' => 'QRIS-TIKTOK-001',
            'no_pembeli' => '081234567890',
            'status' => $status,
            'metode' => 'QRIS',
            'reference' => 'REF-' . $order->order_id,
        ], $overrides));
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\OrderController;
use App\Jobs\PollSufPaymentStatusJob;
use App\Jobs\SendPembelianToProviderJob;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\ProviderPath;
use App\Models\SettingWeb;
use App\Models\User;
use App\Services\OrderProcessingService;
use App\Services\ProviderRoutingService;
use App\Services\ProviderStatusUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SufPaymentOrderProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockSuccessfulAccountValidation();
    }

    public function test_it_dispatches_sufpayment_order_with_settings_credentials(): void
    {
        config()->set('providers.sufpayment.order_cmd', 'order');
        config()->set('providers.sufpayment.target_separator', '|');

        $this->seedSettingWeb();
        $layanan = $this->createLayanan();

        Http::fake([
            'https://sufpayment.com/api/v1/orders' => Http::response([
                'response' => true,
                'data' => [
                    'id' => 'SUF-ID-001',
                    'trxid' => 'SUF-TRX-001',
                    'status' => 'Pending',
                    'msg' => 'Order diterima',
                ],
            ]),
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-SUFPAYMENT-001',
            'username' => 'sufpayment-user',
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'SufPayment User',
            'layanan' => 'ML 86',
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'sufpayment',
            'active_provider_sku' => 'SUF-ML86',
            'status' => 'Pending',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
            'display_order_id' => 'INV-SUFPAYMENT-001',
            'active_attempt_reference' => 'INV-SUFPAYMENT-001',
        ]);

        $result = app(OrderProcessingService::class)->process($pembelian);

        $this->assertTrue($result['success']);
        $this->assertSame('Pending', $result['order_status']);
        $this->assertSame('SUF-ID-001', $result['transaction_id']);
        $this->assertSame('Sedang Diproses', $result['sn']);
        $this->assertSame('Order diterima', $result['message']);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'https://sufpayment.com/api/v1/orders'
                && ($payload['api_id'] ?? null) === '713'
                && ($payload['api_key'] ?? null) === 'settings-sufpayment-key'
                && ($payload['secret_key'] ?? null) === 'settings-sufpayment-secret'
                && ($payload['service'] ?? null) === 'SUF-ML86'
                && ($payload['target'] ?? null) === '12345678|2001'
                && ! array_key_exists('ref_id', $payload)
                && ($payload['cmd'] ?? null) === 'order';
        });
    }

    public function test_it_returns_failed_result_when_sufpayment_rejects_order(): void
    {
        $this->seedSettingWeb();
        $layanan = $this->createLayanan();

        Http::fake([
            'https://sufpayment.com/api/v1/orders' => Http::response([
                'response' => false,
                'data' => [
                    'msg' => 'Product not found',
                ],
            ]),
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-SUFPAYMENT-002',
            'username' => 'sufpayment-user',
            'user_id' => '12345678',
            'zone' => null,
            'nickname' => 'SufPayment User',
            'layanan' => 'ML 86',
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'sufpayment',
            'active_provider_sku' => 'BAD-SKU',
            'status' => 'Pending',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
            'display_order_id' => 'INV-SUFPAYMENT-002',
            'active_attempt_reference' => 'INV-SUFPAYMENT-002',
        ]);

        $result = app(OrderProcessingService::class)->process($pembelian);

        $this->assertFalse($result['success']);
        $this->assertSame('Pending', $result['order_status']);
        $this->assertSame('Product not found', $result['message']);
    }

    public function test_retry_status_uses_sufpayment_status_endpoint(): void
    {
        $this->seedSettingWeb();
        $layanan = $this->createLayanan();

        Http::fake([
            'https://sufpayment.com/api/v1/status' => Http::response([
                'response' => true,
                'data' => [
                    'id' => 'SUF-TRX-OLD',
                    'price' => 15000,
                    'status' => 'Success',
                    'message' => 'pesanan berhasil',
                ],
            ]),
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-SUFPAYMENT-003',
            'username' => 'sufpayment-user',
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'SufPayment User',
            'layanan' => 'ML 86',
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'sufpayment',
            'active_provider_sku' => 'SUF-ML86',
            'provider_order_id' => 'SUF-TRX-OLD',
            'status' => 'Processing',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
            'display_order_id' => 'INV-SUFPAYMENT-003',
            'active_attempt_reference' => 'INV-SUFPAYMENT-003',
        ]);

        $result = app(OrderProcessingService::class)->process($pembelian, 'retry_status');

        $this->assertTrue($result['success']);
        $this->assertSame('Sukses', $result['order_status']);
        $this->assertSame('SUF-TRX-OLD', $result['transaction_id']);
        $this->assertSame('pesanan berhasil', $result['message']);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'https://sufpayment.com/api/v1/status'
                && ($payload['api_id'] ?? null) === '713'
                && ($payload['api_key'] ?? null) === 'settings-sufpayment-key'
                && ($payload['secret_key'] ?? null) === 'settings-sufpayment-secret'
                && ($payload['id'] ?? null) === 'SUF-TRX-OLD'
                && ! array_key_exists('ref_id', $payload);
        });
    }

    public function test_provider_routing_resolves_sufpayment_credentials(): void
    {
        config()->set('providers.sufpayment.order_cmd', 'order');
        config()->set('providers.sufpayment.target_separator', '|');

        $this->seedSettingWeb();

        $route = app(ProviderRoutingService::class)->resolveExplicitProvider('sufpayment', 'SUF-ML86');

        $this->assertSame('sufpayment', $route['provider_code']);
        $this->assertSame('SUF-ML86', $route['sku']);
        $this->assertSame('713', $route['credentials']['api_id']);
        $this->assertSame('settings-sufpayment-key', $route['credentials']['api_key']);
        $this->assertSame('settings-sufpayment-secret', $route['credentials']['secret_key']);
        $this->assertSame('order', $route['credentials']['order_cmd']);
        $this->assertSame('|', $route['credentials']['target_separator']);
    }

    public function test_h2h_api_order_uses_sufpayment_provider_path(): void
    {
        config()->set('providers.sufpayment.order_cmd', 'order');
        config()->set('providers.sufpayment.target_separator', '|');

        $this->seedSettingWeb();
        Queue::fake();

        $token = 'token-sufpayment-order';
        $integration = \App\Models\ResellerIntegration::factory()->create([
            'api_key_hash' => hash('sha256', $token),
            'mode' => 'live',
            'is_active' => true,
        ]);
        $user = $integration->user;
        $user->update([
            'balance' => 50000,
            'no_wa' => '08123456789',
        ]);

        $layanan = $this->createLayanan([
            'provider_id' => 'DG-ML86',
            'harga' => 10000,
            'harga_member' => 12000,
        ]);

        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'sufpayment',
            'provider_sku' => 'SUF-ML86',
            'modal_price' => 9000,
            'priority' => 1,
            'status' => 'available',
            'metadata' => ['source' => 'sufpayment_catalog'],
        ]);

        Http::fake([
            'https://sufpayment.com/api/v1/orders' => Http::response([
                'response' => true,
                'data' => [
                    'trxid' => 'SUF-TRX-H2H-001',
                    'status' => 'Pending',
                    'msg' => 'Order diterima',
                ],
            ], 200),
        ]);

        $request = Request::create('/api/order', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'code' => 'SUF-ML86',
            'referenceNumber' => 'EXT-REF-SUF-001',
            'user_id' => '12345678',
            'zone_id' => '2001',
        ]));
        $request->attributes->set('api_user', $user);
        $request->attributes->set('live_reseller_integration', $integration);

        $response = app(OrderApiController::class)->order($request);
        $payload = $response->getData(true);
        $pembelian = Pembelian::query()->firstOrFail();

        $this->assertFalse($payload['error']);
        $this->assertSame('Pending', $payload['data']['status']);
        $this->assertSame('Pending', $pembelian->status);
        $this->assertSame('sufpayment', $pembelian->active_provider_code);
        $this->assertSame('SUF-ML86', $pembelian->active_provider_sku);
        $this->assertSame('SUF-TRX-H2H-001', $pembelian->provider_order_id);
        $this->assertSame('SUF-TRX-H2H-001', $pembelian->active_attempt_token);
        $this->assertSame(3000, $pembelian->profit);
        $this->assertSame(38000, $user->fresh()->balance);

        Queue::assertPushed(PollSufPaymentStatusJob::class, function (PollSufPaymentStatusJob $job) use ($pembelian): bool {
            return $job->pembelianId === $pembelian->id
                && $job->providerOrderId === 'SUF-TRX-H2H-001'
                && $job->attempt === 1;
        });

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'https://sufpayment.com/api/v1/orders'
                && ($payload['api_id'] ?? null) === '713'
                && ($payload['api_key'] ?? null) === 'settings-sufpayment-key'
                && ($payload['secret_key'] ?? null) === 'settings-sufpayment-secret'
                && ($payload['service'] ?? null) === 'SUF-ML86'
                && ($payload['target'] ?? null) === '12345678|2001'
                && ! array_key_exists('ref_id', $payload)
                && ($payload['cmd'] ?? null) === 'order';
        });
    }

    public function test_send_pembelian_to_provider_job_enqueues_polling_for_pending_sufpayment(): void
    {
        $this->seedSettingWeb();
        Queue::fake();

        $pembelian = $this->createSufPaymentPembelian([
            'order_id' => 'INV-SUF-JOB-PENDING-001',
            'provider_order_id' => null,
            'active_attempt_token' => null,
            'status' => 'Pending',
        ]);

        $processor = \Mockery::mock(OrderProcessingService::class);
        $processor->shouldReceive('process')
            ->once()
            ->withArgs(function (Pembelian $record): bool {
                return $record->order_id === 'INV-SUF-JOB-PENDING-001';
            })
            ->andReturn([
                'success' => true,
                'order_status' => 'Pending',
                'transaction_id' => 'SUF-JOB-PENDING-001',
                'sn' => 'Sedang Diproses',
                'message' => 'Order diterima',
                'provider' => 'sufpayment',
            ]);

        (new SendPembelianToProviderJob($pembelian->id))->handle($processor);

        $pembelian->refresh();

        $this->assertSame('Pending', $pembelian->status);
        $this->assertSame('SUF-JOB-PENDING-001', $pembelian->provider_order_id);
        $this->assertSame('SUF-JOB-PENDING-001', $pembelian->active_attempt_token);
        Queue::assertPushed(PollSufPaymentStatusJob::class, function (PollSufPaymentStatusJob $job) use ($pembelian): bool {
            return $job->pembelianId === $pembelian->id
                && $job->providerOrderId === 'SUF-JOB-PENDING-001'
                && $job->attempt === 1;
        });
    }

    public function test_public_checkout_dispatch_uses_sufpayment_provider_path(): void
    {
        config()->set('providers.sufpayment.order_cmd', 'order');
        config()->set('providers.sufpayment.target_separator', '|');

        $this->seedSettingWeb();

        $layanan = $this->createLayanan([
            'provider' => 'digiflazz',
            'provider_id' => 'DG-ML86',
        ]);

        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'sufpayment',
            'provider_sku' => 'SUF-ML86',
            'modal_price' => 9000,
            'priority' => 1,
            'status' => 'available',
            'metadata' => ['source' => 'sufpayment_catalog'],
        ]);

        Http::fake([
            'https://sufpayment.com/api/v1/orders' => Http::response([
                'response' => true,
                'data' => [
                    'trxid' => 'SUF-TRX-PUBLIC-001',
                    'status' => 'Pending',
                    'msg' => 'Order diterima',
                ],
            ], 200),
        ]);

        $request = Request::create('/id/order', 'POST', [
            'uid' => '12345678',
            'zone' => '2001',
        ]);

        $method = new \ReflectionMethod(OrderController::class, 'processGameProvider');

        $result = $method->invoke(app(OrderController::class), $layanan, $request, 'INV-PUBLIC-SUF-001');

        $this->assertTrue($result['status']);
        $this->assertSame('Pending', $result['order_status']);
        $this->assertSame('SUF-TRX-PUBLIC-001', $result['provider_order_id']);
        $this->assertSame('sufpayment', $result['provider_code']);
        $this->assertSame('SUF-ML86', $result['provider_sku']);
        $this->assertSame('SUF-TRX-PUBLIC-001', $result['order_data']['transactionId'] ?? null);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'https://sufpayment.com/api/v1/orders'
                && ($payload['api_id'] ?? null) === '713'
                && ($payload['api_key'] ?? null) === 'settings-sufpayment-key'
                && ($payload['secret_key'] ?? null) === 'settings-sufpayment-secret'
                && ($payload['service'] ?? null) === 'SUF-ML86'
                && ($payload['target'] ?? null) === '12345678|2001'
                && ! array_key_exists('ref_id', $payload)
                && ($payload['cmd'] ?? null) === 'order';
        });
    }

    public function test_poll_attempt_maps_pending_and_redispatches(): void
    {
        config()->set('providers.sufpayment.polling.interval_seconds', 120);
        $this->seedSettingWeb();
        Queue::fake();

        $pembelian = $this->createSufPaymentPembelian([
            'order_id' => 'INV-SUF-POLL-PENDING-001',
            'provider_order_id' => 'SUF-POLL-PENDING-001',
            'active_attempt_token' => 'SUF-POLL-PENDING-001',
            'status' => 'Pending',
        ]);

        Http::fake([
            'https://sufpayment.com/api/v1/status' => Http::response([
                'response' => true,
                'data' => [
                    'id' => 'SUF-POLL-PENDING-001',
                    'status' => 'Pending',
                    'msg' => 'Masih diproses',
                ],
            ]),
        ]);

        $this->handlePoll(new PollSufPaymentStatusJob($pembelian->id, 'SUF-POLL-PENDING-001', 1));

        $pembelian->refresh();

        $this->assertSame('Pending', $pembelian->status);
        $this->assertStringContainsString('Masih diproses', (string) $pembelian->log);
        Queue::assertPushed(PollSufPaymentStatusJob::class, function (PollSufPaymentStatusJob $job) use ($pembelian): bool {
            return $job->pembelianId === $pembelian->id
                && $job->providerOrderId === 'SUF-POLL-PENDING-001'
                && $job->attempt === 2
                && $job->delay !== null;
        });
    }

    public function test_poll_attempt_maps_success_and_stops_polling(): void
    {
        $this->seedSettingWeb();
        Queue::fake();

        $pembelian = $this->createSufPaymentPembelian([
            'order_id' => 'INV-SUF-POLL-SUCCESS-001',
            'provider_order_id' => 'SUF-POLL-SUCCESS-001',
            'active_attempt_token' => 'SUF-POLL-SUCCESS-001',
            'status' => 'Pending',
        ]);

        Http::fake([
            'https://sufpayment.com/api/v1/status' => Http::response([
                'response' => true,
                'data' => [
                    'id' => 'SUF-POLL-SUCCESS-001',
                    'status' => 'Success',
                    'message' => 'Berhasil',
                    'sn' => 'SN-SUF-001',
                ],
            ]),
        ]);

        $this->handlePoll(new PollSufPaymentStatusJob($pembelian->id, 'SUF-POLL-SUCCESS-001', 1));

        $pembelian->refresh();

        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('SN-SUF-001', $pembelian->keterangan_sn);
        Queue::assertNotPushed(PollSufPaymentStatusJob::class);
    }

    public function test_poll_attempt_maps_error_and_refunds_once(): void
    {
        $this->seedSettingWeb();
        Queue::fake();

        $user = User::factory()->create([
            'username' => 'suf-refund-user',
            'balance' => 1000,
        ]);

        $pembelian = $this->createSufPaymentPembelian([
            'order_id' => 'INV-SUF-POLL-ERROR-001',
            'username' => $user->username,
            'provider_order_id' => 'SUF-POLL-ERROR-001',
            'active_attempt_token' => 'SUF-POLL-ERROR-001',
            'status' => 'Pending',
            'harga' => 15000,
            'traffic_source' => 'reseller_h2h',
        ], paymentMethod: 'SALDO');

        Http::fake([
            'https://sufpayment.com/api/v1/status' => Http::response([
                'response' => true,
                'data' => [
                    'id' => 'SUF-POLL-ERROR-001',
                    'status' => 'Error',
                    'message' => 'Provider gagal',
                ],
            ]),
        ]);

        $this->handlePoll(new PollSufPaymentStatusJob($pembelian->id, 'SUF-POLL-ERROR-001', 1));
        $this->handlePoll(new PollSufPaymentStatusJob($pembelian->id, 'SUF-POLL-ERROR-001', 2));

        $pembelian->refresh();

        $this->assertSame('Gagal', $pembelian->status);
        $this->assertSame(16000, $user->fresh()->balance);
        $this->assertNotNull($pembelian->refunded_at);
        $this->assertSame(15000, $pembelian->refund_amount);
        Queue::assertNotPushed(PollSufPaymentStatusJob::class);
    }

    public function test_polling_exhausted_leaves_status_pending_and_writes_log(): void
    {
        config()->set('providers.sufpayment.polling.max_attempts', 1);
        $this->seedSettingWeb();
        Queue::fake();

        $pembelian = $this->createSufPaymentPembelian([
            'order_id' => 'INV-SUF-POLL-EXHAUSTED-001',
            'provider_order_id' => 'SUF-POLL-EXHAUSTED-001',
            'active_attempt_token' => 'SUF-POLL-EXHAUSTED-001',
            'status' => 'Pending',
        ]);

        Http::fake([
            'https://sufpayment.com/api/v1/status' => Http::response([
                'response' => true,
                'data' => [
                    'id' => 'SUF-POLL-EXHAUSTED-001',
                    'status' => 'Pending',
                    'msg' => 'Masih pending',
                ],
            ]),
        ]);

        $this->handlePoll(new PollSufPaymentStatusJob($pembelian->id, 'SUF-POLL-EXHAUSTED-001', 1));

        $pembelian->refresh();

        $this->assertSame('Pending', $pembelian->status);
        $this->assertStringContainsString('polling exhausted', (string) $pembelian->log);
        Queue::assertNotPushed(PollSufPaymentStatusJob::class);
    }

    public function test_final_current_status_skips_provider_call(): void
    {
        $this->seedSettingWeb();

        $pembelian = $this->createSufPaymentPembelian([
            'order_id' => 'INV-SUF-POLL-FINAL-001',
            'provider_order_id' => 'SUF-POLL-FINAL-001',
            'active_attempt_token' => 'SUF-POLL-FINAL-001',
            'status' => 'Sukses',
        ]);

        Http::fake();

        $this->handlePoll(new PollSufPaymentStatusJob($pembelian->id, 'SUF-POLL-FINAL-001', 1));

        $this->assertSame('Sukses', $pembelian->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_missing_provider_order_id_exits_without_failure(): void
    {
        $this->seedSettingWeb();

        $pembelian = $this->createSufPaymentPembelian([
            'order_id' => 'INV-SUF-POLL-MISSING-ID-001',
            'provider_order_id' => null,
            'active_attempt_token' => null,
            'status' => 'Pending',
        ]);

        Http::fake();

        $this->handlePoll(new PollSufPaymentStatusJob($pembelian->id, null, 1));

        $pembelian->refresh();

        $this->assertSame('Pending', $pembelian->status);
        $this->assertStringContainsString('provider_order_id kosong', (string) $pembelian->log);
        Http::assertNothingSent();
    }

    public function test_public_saldo_paid_order_enqueues_polling(): void
    {
        $this->seedSettingWeb();
        Queue::fake();

        $pembelian = $this->createSufPaymentPembelian([
            'order_id' => 'INV-SUF-POLL-PUBLIC-SALDO-001',
            'provider_order_id' => 'SUF-POLL-PUBLIC-SALDO-001',
            'active_attempt_token' => 'SUF-POLL-PUBLIC-SALDO-001',
            'status' => 'Proses',
            'traffic_source' => 'Direct',
        ], paymentMethod: 'SALDO');

        PollSufPaymentStatusJob::dispatchIfNeeded($pembelian, 'SUF-POLL-PUBLIC-SALDO-001', 'Pending');

        Queue::assertPushed(PollSufPaymentStatusJob::class, function (PollSufPaymentStatusJob $job) use ($pembelian): bool {
            return $job->pembelianId === $pembelian->id
                && $job->providerOrderId === 'SUF-POLL-PUBLIC-SALDO-001'
                && $job->attempt === 1;
        });
    }

    public function test_external_gateway_unpaid_order_does_not_enqueue_polling(): void
    {
        $this->seedSettingWeb();
        Queue::fake();

        $pembelian = $this->createSufPaymentPembelian([
            'order_id' => 'INV-SUF-POLL-UNPAID-001',
            'provider_order_id' => 'SUF-POLL-UNPAID-001',
            'active_attempt_token' => 'SUF-POLL-UNPAID-001',
            'status' => 'Pending',
        ], paymentStatus: 'Belum Lunas');

        PollSufPaymentStatusJob::dispatchIfNeeded($pembelian, 'SUF-POLL-UNPAID-001', 'Pending');

        Queue::assertNotPushed(PollSufPaymentStatusJob::class);
    }

    private function handlePoll(PollSufPaymentStatusJob $job): void
    {
        $job->handle(app(ProviderRoutingService::class), app(ProviderStatusUpdateService::class));
    }

    private function createSufPaymentPembelian(array $overrides = [], string $paymentStatus = 'Lunas', string $paymentMethod = 'QRIS'): Pembelian
    {
        $defaults = [
            'order_id' => 'INV-SUF-POLL-DEFAULT-001',
            'username' => 'sufpayment-user',
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'SufPayment User',
            'layanan' => 'ML 86',
            'active_provider_code' => 'sufpayment',
            'active_provider_sku' => 'SUF-ML86',
            'provider_order_id' => 'SUF-POLL-DEFAULT-001',
            'active_attempt_token' => 'SUF-POLL-DEFAULT-001',
            'status' => 'Pending',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
            'display_order_id' => 'INV-SUF-POLL-DEFAULT-001',
            'active_attempt_reference' => 'INV-SUF-POLL-DEFAULT-001',
        ];

        $pembelian = Pembelian::create(array_merge($defaults, $overrides));

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => $pembelian->harga,
            'no_pembayaran' => $paymentMethod,
            'no_pembeli' => '08123456789',
            'status' => $paymentStatus,
            'metode' => $paymentMethod,
            'reference' => 'REF-' . $pembelian->order_id,
        ]);

        return $pembelian->fresh(['pembayaran', 'user']);
    }

    private function createLayanan(array $overrides = []): Layanan
    {
        $kategori = Kategori::create([
            'nama' => 'Mobile Legends',
            'sub_nama' => 'Mobile Legends',
            'kode' => 'mobile-legends',
            'status' => 'active',
            'thumbnail' => 'assets/thumbnail/mobile-legends.png',
            'banner' => 'assets/banner/mobile-legends.png',
            'tipe' => 'game',
            'server_id' => true,
            'require_user_id' => true,
        ]);

        return Layanan::create(array_merge([
            'kategori_id' => (string) $kategori->id,
            'layanan' => 'ML 86',
            'provider_id' => 'SUF-ML86',
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'available',
            'provider' => 'sufpayment',
            'catatan' => 'SufPayment service',
            'is_flash_sale' => 0,
        ], $overrides));
    }

    private function seedSettingWeb(): void
    {
        SettingWeb::create([
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'url_wa' => 'wa.me/test',
            'url_ig' => 'instagram.com/test',
            'url_tiktok' => 'tiktok.com/test',
            'url_youtube' => 'youtube.com/test',
            'url_fb' => 'facebook.com/test',
            'topupindo_api' => 'test_api',
            'warna1' => '#222222',
            'warna2' => '#d06800',
            'warna3' => '#ffa54a',
            'warna4' => '#ff8040',
            'paydisini_apikey' => 'test_paydisini',
            'tripay_api' => 'test_api_key',
            'tripay_merchant_code' => 'test_merchant',
            'tripay_private_key' => 'test_private',
            'username_digi' => 'test_digi',
            'api_key_digi' => 'test_digi_key',
            'apigames_secret' => 'secret-123',
            'apigames_merchant' => 'merchant-123',
            'sufpayment_api_id' => '713',
            'sufpayment_api_key' => 'settings-sufpayment-key',
            'sufpayment_secret_key' => 'settings-sufpayment-secret',
            'vip_apiid' => 'test_vip_id',
            'vip_apikey' => 'test_vip_key',
            'apikey_bangjeff' => 'test_bangjeff_key',
            'order_prefik' => 'INV',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\Payments\DuitkuPopClient;
use App\Services\Payments\DuitkuReconciliationService;
use Duitku\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DuitkuReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingWebsSeeder::class);
        DB::table('setting_webs')->where('id', 1)->update([
            'duitku_merchant_key' => 'test-merchant-key',
            'duitku_merchant_code' => 'TEST123',
            'duitku_mode' => 'sandbox',
        ]);
    }

    public function test_direct_payment_reconciliation_marks_payment_paid(): void
    {
        [$order, $payment] = $this->createOrderAndPayment('DIRECT-001', 'direct', 'BR');

        $client = $this->mock(DuitkuPopClient::class);
        $client->shouldReceive('transactionStatusForPayment')
            ->once()
            ->with('DUITKU-DIRECT-001', \Mockery::type(Config::class), 'direct', 'BR')
            ->andReturn([
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
                'amount' => 50000,
                'reference' => 'REF-DIRECT-001',
            ]);

        $result = app(DuitkuReconciliationService::class)->reconcile($payment);

        $this->assertSame('paid', $result['decision']);
        $this->assertSame('Lunas', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertNotSame('Gagal', $order->fresh()->status);
    }

    public function test_pop_pending_reconciliation_does_not_mutate_payment(): void
    {
        [, $payment] = $this->createOrderAndPayment('POP-001', 'pop', 'DUITKU');

        $client = $this->mock(DuitkuPopClient::class);
        $client->shouldReceive('transactionStatusForPayment')
            ->once()
            ->with('DUITKU-POP-001', \Mockery::type(Config::class), 'pop', 'DUITKU')
            ->andReturn([
                'statusCode' => '01',
                'statusMessage' => 'PENDING',
            ]);

        $result = app(DuitkuReconciliationService::class)->reconcile($payment);

        $this->assertSame('pending', $result['decision']);
        $this->assertSame('Belum Lunas', $payment->fresh()->status);
    }

    public function test_unknown_reconciliation_status_is_non_terminal(): void
    {
        [, $payment] = $this->createOrderAndPayment('UNKNOWN-001', 'direct', 'DM');

        $client = $this->mock(DuitkuPopClient::class);
        $client->shouldReceive('transactionStatusForPayment')
            ->once()
            ->andReturn([
                'statusCode' => '99',
                'statusMessage' => 'UNKNOWN',
            ]);

        $result = app(DuitkuReconciliationService::class)->reconcile($payment);

        $this->assertSame('unknown', $result['decision']);
        $this->assertSame('Belum Lunas', $payment->fresh()->status);
    }

    public function test_polling_reconciliation_is_throttled(): void
    {
        [, $payment] = $this->createOrderAndPayment('THROTTLE-001', 'direct', 'BR');

        $client = $this->mock(DuitkuPopClient::class);
        $client->shouldReceive('transactionStatusForPayment')
            ->once()
            ->andReturn([
                'statusCode' => '01',
                'statusMessage' => 'PENDING',
            ]);

        $service = app(DuitkuReconciliationService::class);
        $first = $service->reconcileByOrderId($payment->order_id, true);
        $second = $service->reconcileByOrderId($payment->order_id, true);

        $this->assertSame('pending', $first['decision']);
        $this->assertSame('throttled', $second['decision']);
    }

    private function createOrderAndPayment(string $suffix, string $apiMode, string $paymentCode): array
    {
        $orderId = 'DUITKU-' . $suffix;
        $order = Pembelian::factory()->create([
            'order_id' => $orderId,
            'layanan' => 'Voucher',
            'harga' => 50000,
            'status' => 'Pending',
        ]);
        $payment = Pembayaran::query()->create([
            'order_id' => $orderId,
            'harga' => 50000,
            'metode' => $paymentCode,
            'status' => 'Belum Lunas',
            'no_pembayaran' => 'VA-' . $suffix,
            'no_pembeli' => '081234567890',
            'reference' => 'REF-' . $suffix,
            'duitku_reference' => 'REF-' . $suffix,
            'duitku_merchant_order_id' => 'DUITKU-' . $suffix,
            'duitku_api_mode' => $apiMode,
            'duitku_payment_code' => $paymentCode,
            'expired_at' => now()->addHour(),
        ]);

        return [$order, $payment];
    }
}

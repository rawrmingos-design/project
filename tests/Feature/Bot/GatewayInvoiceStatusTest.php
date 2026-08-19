<?php

namespace Tests\Feature\Bot;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\Gateway\GatewayInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GatewayInvoiceStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(array $overrides = []): Pembelian
    {
        $order = Pembelian::create(array_merge([
            'order_id' => 'BOT-TEST-001',
            'username' => 'Anonim',
            'user_id' => '12345',
            'zone' => '6789',
            'nickname' => 'Player',
            'layanan' => '100 Diamond',
            'harga' => 10500,
            'profit' => 500,
            'provider_order_id' => '',
            'status' => 'Pending',
            'log' => json_encode(['source' => 'whatsapp_gateway_checkout']),
            'traffic_source' => 'whatsapp_gateway',
            'tipe_transaksi' => 'game',
            'active_layanan_id' => 1,
            'active_provider_code' => 'manual',
            'active_provider_sku' => 'manual',
            'environment' => 'live',
            'is_sandbox' => false,
        ], $overrides));

        Pembayaran::create([
            'order_id' => $order->order_id,
            'harga' => 10500,
            'no_pembeli' => '6285792464508',
            'status' => 'Belum Lunas',
            'metode' => 'QRIS',
        ]);

        return $order;
    }

    public function test_status_allows_whatsapp_sender_when_gateway_context_missing(): void
    {
        $order = $this->createOrder();

        // Simulate provider dispatch overwriting the log (gateway_context lost)
        $order->update([
            'log' => json_encode(['result' => ['success' => true, 'order_status' => 'Sukses']]),
        ]);

        $service = app(GatewayInvoiceService::class);
        $result = $service->status($order->order_id, null, [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6285792464508',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame($order->order_id, $result['data']['order_id']);
    }

    public function test_status_rejects_different_whatsapp_sender(): void
    {
        $this->createOrder();

        $service = app(GatewayInvoiceService::class);

        $this->expectException(ValidationException::class);

        $service->status('BOT-TEST-001', null, [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281111111111',
        ]);
    }

    public function test_latest_for_sender_returns_most_recent_order(): void
    {
        $this->createOrder(['order_id' => 'BOT-TEST-001']);
        $this->createOrder(['order_id' => 'BOT-TEST-002']);

        $service = app(GatewayInvoiceService::class);
        $latest = $service->latestForSender('whatsapp_gateway', 'whatsapp:6285792464508');

        $this->assertNotNull($latest);
        $this->assertSame('BOT-TEST-002', $latest->order_id);
    }

    public function test_latest_for_sender_returns_null_for_unknown_sender(): void
    {
        $this->createOrder();

        $service = app(GatewayInvoiceService::class);
        $latest = $service->latestForSender('whatsapp_gateway', 'whatsapp:6289999999999');

        $this->assertNull($latest);
    }
}

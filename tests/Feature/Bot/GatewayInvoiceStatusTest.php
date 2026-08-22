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
            'no_pembayaran' => 'TEST-VA-001',
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

    public function test_active_orders_for_sender_filters_expired_and_success(): void
    {
        // Expired (lama) — harus TIDAK masuk daftar aktif
        $this->createOrder([
            'order_id' => 'BOT-TEST-EXP',
            'status' => 'Expired',
        ])->pembayaran()->update(['status' => 'Expired']);

        // Lunas + Sukses (tuntas) — harus TIDAK masuk daftar aktif
        $this->createOrder([
            'order_id' => 'BOT-TEST-DONE',
            'status' => 'Sukses',
        ])->pembayaran()->update(['status' => 'Lunas']);

        // Belum Lunas (aktif) — harus MASUK
        $this->createOrder([
            'order_id' => 'BOT-TEST-ACTIVE',
            'status' => 'Pending',
        ]);

        $service = app(GatewayInvoiceService::class);
        $orders = $service->activeOrdersForSender('whatsapp_gateway', 'whatsapp:6285792464508');

        $this->assertCount(1, $orders);
        $this->assertSame('BOT-TEST-ACTIVE', $orders->first()->order_id);
    }

    private function createTelegramOrder(array $overrides = []): Pembelian
    {
        return Pembelian::create(array_merge([
            'order_id' => 'TG-TEST-001',
            'username' => 'Anonim',
            'user_id' => '12345',
            'zone' => '',
            'nickname' => 'Player',
            'layanan' => '100 Diamond',
            'harga' => 10500,
            'profit' => 500,
            'provider_order_id' => '',
            'status' => 'Pending',
            'log' => json_encode(['source' => 'telegram_gateway_checkout']),
            'traffic_source' => 'telegram_gateway',
            'gateway_principal' => 'telegram:98765',
            'email_pembeli' => '98765@telegram.user',
            'tipe_transaksi' => 'game',
            'active_layanan_id' => 1,
            'active_provider_code' => 'manual',
            'active_provider_sku' => 'manual',
            'environment' => 'live',
            'is_sandbox' => false,
        ], $overrides));
    }

    public function test_recent_orders_for_sender_matches_telegram_principal(): void
    {
        $this->createTelegramOrder(['order_id' => 'TG-TEST-001', 'status' => 'Sukses']);
        $this->createTelegramOrder(['order_id' => 'TG-TEST-002', 'status' => 'Expired']);
        // Order WhatsApp dengan no_pembeli sama pun tidak boleh bocor.
        $waOrder = $this->createOrder(['order_id' => 'WA-NOT-TG']);
        $waOrder->pembayaran()->update(['no_pembeli' => '98765']);

        $service = app(GatewayInvoiceService::class);

        $recent = $service->recentOrdersForSender('telegram_gateway', 'telegram:98765');
        $this->assertCount(2, $recent);
        $this->assertSame('TG-TEST-002', $recent->first()->order_id);

        // Bentuk raw numeric juga dinormalisasi ke principal yang sama.
        $recentRaw = $service->recentOrdersForSender('telegram_gateway', '98765');
        $this->assertCount(2, $recentRaw);
    }

    public function test_recent_orders_for_sender_falls_back_to_legacy_telegram_email(): void
    {
        // Order lama sebelum kolom gateway_principal ada.
        $legacy = $this->createTelegramOrder([
            'order_id' => 'TG-LEGACY-001',
            'gateway_principal' => '',
        ]);
        $legacy->update([
            'gateway_principal' => null,
            'email_pembeli' => '98765@telegram.user',
        ]);

        $service = app(GatewayInvoiceService::class);
        $recent = $service->recentOrdersForSender('telegram_gateway', 'telegram:98765');

        $this->assertCount(1, $recent);
        $this->assertSame('TG-LEGACY-001', $recent->first()->order_id);
    }

    public function test_active_orders_for_sender_filters_final_statuses_on_telegram(): void
    {
        $this->createTelegramOrder(['order_id' => 'TG-ACT-001', 'status' => 'Pending']);
        $this->createTelegramOrder(['order_id' => 'TG-DONE-001', 'status' => 'Sukses']);
        $this->createTelegramOrder(['order_id' => 'TG-EXP-001', 'status' => 'Expired']);

        $service = app(GatewayInvoiceService::class);
        $active = $service->activeOrdersForSender('telegram_gateway', 'telegram:98765');

        $this->assertCount(1, $active);
        $this->assertSame('TG-ACT-001', $active->first()->order_id);
    }

    public function test_recent_orders_never_leak_between_telegram_principals(): void
    {
        $this->createTelegramOrder();

        $service = app(GatewayInvoiceService::class);
        $other = $service->recentOrdersForSender('telegram_gateway', 'telegram:11111');

        $this->assertCount(0, $other);
    }
}

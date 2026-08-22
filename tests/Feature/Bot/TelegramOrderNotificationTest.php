<?php

namespace Tests\Feature\Bot;

use App\Events\InvoiceStatusUpdated;
use App\Listeners\NotifyBotOrderStatusListener;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createTelegramOrder(array $overrides = []): Pembelian
    {
        $order = Pembelian::create(array_merge([
            'order_id' => 'TG-NOTIF-001',
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

        Pembayaran::create([
            'order_id' => $order->order_id,
            'harga' => 10500,
            'no_pembayaran' => 'TG-VA-001',
            'no_pembeli' => '-',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        return $order;
    }

    public function test_telegram_order_sends_proactive_notification_on_success(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        SettingWeb::query()->create(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->createTelegramOrder(['status' => 'Sukses']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && $request['chat_id'] === '98765'
                && str_contains((string) $request['text'], 'Top Up Berhasil')
                && str_contains((string) $request['text'], 'Terima kasih sudah berbelanja');
        });

        // Anti-spam: transisi yang sama terkirim sekali.
        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertSentCount(1);
    }

    public function test_whatsapp_orders_do_not_hit_telegram_api(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        SettingWeb::query()->create(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake();

        $order = Pembelian::create([
            'order_id' => 'WA-NOTIF-001',
            'username' => 'Anonim',
            'user_id' => '12345',
            'nickname' => 'Player',
            'layanan' => '100 Diamond',
            'harga' => 10500,
            'profit' => 500,
            'status' => 'Sukses',
            'log' => json_encode([]),
            'traffic_source' => 'whatsapp_gateway',
            'tipe_transaksi' => 'game',
            'environment' => 'live',
            'is_sandbox' => false,
        ]);

        Pembayaran::create([
            'order_id' => $order->order_id,
            'harga' => 10500,
            'no_pembayaran' => 'WA-VA-001',
            'no_pembeli' => '6285792464508',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        $this->mock(\App\Services\WhatsappNotificationService::class, function (\Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once()->andReturn(['success' => true]);
        });

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'WA-NOTIF-001',
        ]));

        Http::assertNothingSent();
    }

    public function test_notification_skipped_when_telegram_token_missing(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        SettingWeb::query()->create(['telegram_bot_token' => null]);

        Cache::flush();
        Http::fake();

        $this->createTelegramOrder(['status' => 'Sukses']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertNothingSent();

        // Tanpa cache key → event berikutnya masih mencoba kirim setelah token ada.
        $this->assertFalse(Cache::has('bot:notif:TG-NOTIF-001:success'));
    }

    public function test_non_lunas_payment_is_not_notified(): void
    {
        config(['services.telegram-bot-api.token' => null]);
        SettingWeb::query()->create(['telegram_bot_token' => 'TEST-TG-TOKEN']);

        Cache::flush();
        Http::fake();

        $this->createTelegramOrder(['status' => 'Pending'])
            ->pembayaran()->update(['status' => 'Belum Lunas']);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated([
            'order_id' => 'TG-NOTIF-001',
        ]));

        Http::assertNothingSent();
    }
}

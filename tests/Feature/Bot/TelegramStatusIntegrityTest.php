<?php

namespace Tests\Feature\Bot;

use App\Http\Controllers\DigiFlazzController;
use App\Models\InboundSourcePolicy;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\ProviderOrderStatusSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regresi untuk tiga bug yang dilaporkan 2026-09-24:
 *
 *  1. `status` memunculkan "Verifikasi Keanggotaan Bermasalah" — gate
 *     keanggotaan memblokir perintah milik-sendiri saat verifikasi gagal.
 *  2. Daftar transaksi user kosong padahal order ada — jalur `status` tanpa
 *     argumen hanya menampilkan order "aktif"; order berstatus final
 *     (mis. Gagal/Expired) tidak pernah muncul.
 *  3. Order yang SUDAH dibayar ditandai `Gagal` karena error konfigurasi
 *     provider (Digiflazz rc=41 "Signature Anda salah") dianggap vonis order.
 */
class TelegramStatusIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.webhook_secret' => 'dummy-secret',
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.order_enabled' => true,
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.id' => '@testchannel',
            'services.telegram-bot-api.required_channel.url' => 'https://t.me/testchannel',
            'services.telegram-bot-api.required_channel.cache_seconds' => 120,
        ]);

        // AppServiceProvider membaca flag dari DB dan menimpa config().
        DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'Test Store',
                'deskripsi_web' => 'Test Description',
                'keywords' => 'test',
                'url_wa' => 'https://wa.me/628123456789',
                'url_ig' => 'https://instagram.com/test',
                'url_tiktok' => 'https://tiktok.com/@test',
                'url_youtube' => 'https://youtube.com/test',
                'url_fb' => 'https://facebook.com/test',
                'topupindo_api' => 'test',
                'warna1' => '#000000',
                'warna2' => '#000000',
                'warna3' => '#000000',
                'warna4' => '#000000',
                'paydisini_apikey' => 'test',
                'order_prefik' => 'TRX',
                'nomor_admin' => '628123456789',
                'bot_order_tg_enabled' => 1,
                'bot_order_wa_enabled' => 1,
            ],
        );
        config(['app.name' => 'Test Store']);

        // Kebijakan inbound source: mode disabled = lolos untuk request lokal.
        InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'telegram',
            'mode' => 'disabled',
            'is_active' => true,
        ]);
    }

    private function postTelegram(array $data)
    {
        return $this->postJson('/api/webhooks/bot/telegram', $data, [
            'X-Telegram-Bot-Api-Secret-Token' => config('services.telegram-bot-api.webhook_secret', ''),
        ]);
    }

    private function createTelegramOrder(array $overrides = []): Pembelian
    {
        // telegram_user_id adalah kunci HANYA untuk helper ini (bukan kolom
        // tabel) — dipakai membentuk principal/email legacy.
        $userId = (string) ($overrides['telegram_user_id'] ?? '6252007210');
        unset($overrides['telegram_user_id']);

        $principal = (string) ($overrides['gateway_principal'] ?? 'telegram:' . $userId);
        $legacyEmail = (string) ($overrides['email_pembeli'] ?? $userId . '@telegram.user');

        $order = Pembelian::query()->create(array_merge([
            'order_id' => 'TG-REG-001',
            'username' => 'Anonim',
            'user_id' => '1840180550',
            'zone' => '',
            'nickname' => '@Mavall',
            'layanan' => '40 Diamond Free Fire',
            'harga' => 7986,
            'profit' => 0,
            'provider_order_id' => 'TG-REG-001',
            'status' => 'Gagal',
            'log' => json_encode(['source' => 'telegram_gateway']),
            'traffic_source' => 'telegram_gateway',
            'tipe_transaksi' => 'game',
            'active_layanan_id' => 1,
            'active_provider_code' => 'digiflazz',
            'active_provider_sku' => 'FF40',
            'environment' => 'live',
            'is_sandbox' => false,
        ], $overrides, [
            'gateway_principal' => $principal,
            'email_pembeli' => $legacyEmail,
        ]));

        Pembayaran::query()->create([
            'order_id' => $order->order_id,
            'harga' => $order->harga,
            'no_pembayaran' => 'PAY-' . $order->order_id,
            'no_pembeli' => '-',
            'status' => 'Lunas',
            'metode' => 'QRIS',
            'paid_at' => now(),
        ]);

        return $order;
    }

    private function telegramContext(): array
    {
        return [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:default:6252007210',
            'telegram_user_id' => 6252007210,
            'telegram_bot_scope' => 'default',
            'telegram_chat_id' => 6252007210,
            'email' => '6252007210@telegram.user',
        ];
    }

    /** @test */
    public function status_command_is_not_blocked_when_membership_verification_fails(): void
    {
        $this->createTelegramOrder();

        // Telegram tidak terjangkau -> verifikasi UNAVAILABLE.
        Http::fake([
            'https://api.telegram.org/*' => Http::failedConnection(),
        ]);

        $response = $this->postTelegram([
            'update_id' => 9001,
            'message' => [
                'chat' => ['id' => 6252007210],
                'from' => ['id' => 6252007210],
                'text' => '/status',
                'message_id' => 501,
            ],
        ]);

        $response->assertOk();

        $sent = $this->sentTelegramTexts();

        $this->assertNotEmpty($sent, 'Bot harus membalas pesan.');
        $this->assertStringNotContainsString(
            'Verifikasi Keanggotaan Bermasalah',
            implode("\n", $sent),
            'Perintah `status` tidak boleh diblokir gate keanggotaan.',
        );
    }

    /** @test */
    public function menu_command_is_still_blocked_when_membership_verification_fails(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::failedConnection(),
        ]);

        $response = $this->postTelegram([
            'update_id' => 9002,
            'message' => [
                'chat' => ['id' => 6252007210],
                'from' => ['id' => 6252007210],
                'text' => '/menu',
                'message_id' => 502,
            ],
        ]);

        $response->assertOk();

        $sent = implode("\n", $this->sentTelegramTexts());

        $this->assertStringContainsString(
            'Keanggotaan channel Anda belum dapat diverifikasi',
            $sent,
            'Gate keanggotaan harus tetap berlaku untuk membuka katalog.',
        );
    }

    /** @test */
    public function final_paid_order_appears_in_sender_transaction_list(): void
    {
        // Kasus nyata: order sudah Lunas, provider gagal kirim -> status Gagal.
        $order = $this->createTelegramOrder([
            'order_id' => 'EM260924210551WYRPN95X',
            'status' => 'Gagal',
        ]);

        $service = app(GatewayInvoiceService::class);
        $list = $service->senderOrdersForSender('telegram_gateway', 'telegram:6252007210');

        $this->assertSame(1, $list['total']);
        $this->assertSame($order->order_id, $list['items']->first()->order_id);

        // Order final TIDAK dianggap aktif.
        $active = $service->activeOrdersForSender('telegram_gateway', 'telegram:6252007210');
        $this->assertCount(0, $active);
    }

    /** @test */
    public function sender_transaction_list_is_paginated_and_never_leaks_between_senders(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->createTelegramOrder([
                'order_id' => 'TG-PAGE-00' . $i,
                'status' => 'Expired',
            ]);
        }

        $this->createTelegramOrder([
            'order_id' => 'TG-OTHER-001',
            'status' => 'Sukses',
            'telegram_user_id' => '11111',
            'gateway_principal' => 'telegram:11111',
            'email_pembeli' => '11111@telegram.user',
        ]);

        $service = app(GatewayInvoiceService::class);

        $page1 = $service->senderOrdersForSender('telegram_gateway', 'telegram:6252007210', 1);
        $this->assertSame(7, $page1['total']);
        $this->assertSame(2, $page1['total_pages']);
        $this->assertCount(5, $page1['items']);

        $page2 = $service->senderOrdersForSender('telegram_gateway', 'telegram:6252007210', 2);
        $this->assertSame(2, $page2['page']);
        $this->assertCount(2, $page2['items']);

        $ids = $page1['items']->pluck('order_id')
            ->merge($page2['items']->pluck('order_id'))
            ->all();

        $this->assertNotContains('TG-OTHER-001', $ids, 'Order sender lain tidak boleh bocor.');
        $this->assertCount(7, array_unique($ids));
    }

    /** @test */
    public function digiflazz_status_and_balance_paths_send_testing_flag_like_order_path(): void
    {
        // Bila akun ber-mode testing, `order` mengirim testing=true. Jalur
        // status/cek-saldo sebelumnya TIDAK, dan Digiflazz membalas rc=41.
        config(['providers.digiflazz.testing' => true]);

        Http::fake([
            'https://api.digiflazz.com/*' => Http::response([
                'data' => ['status' => 'Pending', 'rc' => '', 'message' => ''],
            ], 200),
        ]);

        $client = new DigiFlazzController([
            'username' => 'tester',
            'api_key' => 'secret',
            'endpoint' => 'https://api.digiflazz.com',
        ]);

        $client->order('1840180550', '', 'FF40', 'REF-ORDER');
        $client->status('REF-ORDER', 'FF40', '1840180550', '');
        $client->cekSaldo();

        $payloads = [];
        Http::assertSent(function ($request) use (&$payloads): bool {
            $payloads[] = $request->data();

            return true;
        });

        $this->assertCount(3, $payloads);

        foreach ($payloads as $index => $payload) {
            $this->assertArrayHasKey('testing', $payload, "Payload #{$index} wajib memuat flag testing.");
            $this->assertTrue($payload['testing'], "Payload #{$index} wajib testing=true.");
        }
    }

    /** @test */
    public function all_digiflazz_paths_send_identical_testing_flag_in_production_mode(): void
    {
        // Mode produksi: `order()` selalu mengirim testing=false. Jalur status
        // dan cek-saldo harus memakai bentuk payload yang SAMA supaya tidak
        // ada lagi jalur yang berperilaku beda dari jalur order.
        config(['providers.digiflazz.testing' => false]);

        Http::fake([
            'https://api.digiflazz.com/*' => Http::response([
                'data' => ['status' => 'Pending', 'rc' => '', 'message' => ''],
            ], 200),
        ]);

        $client = new DigiFlazzController([
            'username' => 'tester',
            'api_key' => 'secret',
            'endpoint' => 'https://api.digiflazz.com',
        ]);

        $client->order('1840180550', '', 'FF40', 'REF-ORDER');
        $client->status('REF-ORDER', 'FF40', '1840180550', '');
        $client->cekSaldo();

        $payloads = [];
        Http::assertSent(function ($request) use (&$payloads): bool {
            $payloads[] = $request->data();

            return true;
        });

        $this->assertCount(3, $payloads);

        foreach ($payloads as $index => $payload) {
            $this->assertArrayHasKey('testing', $payload, "Payload #{$index} wajib memuat key testing.");
            $this->assertFalse($payload['testing'], "Payload #{$index} wajib testing=false.");
        }
    }

    /** @test */
    public function provider_configuration_error_does_not_mark_order_failed_on_poll(): void
    {
        $this->createTelegramOrder([
            'order_id' => 'TG-SIG-001',
            'status' => 'Pending',
        ]);

        // Polling mengembalikan rc=41 "Signature Anda salah" + status Gagal.
        Http::fake([
            'https://api.digiflazz.com/*' => Http::response([
                'data' => [
                    'ref_id' => 'TG-SIG-001',
                    'status' => 'Gagal',
                    'rc' => '41',
                    'message' => 'Signature Anda salah',
                    'sn' => '',
                ],
            ], 400),
        ]);

        $sync = app(ProviderOrderStatusSyncService::class);
        $sync->sync('digiflazz');

        $order = Pembelian::query()->where('order_id', 'TG-SIG-001')->first();

        $this->assertSame(
            'Pending',
            $order->status,
            'Error konfigurasi provider tidak boleh mengubah order jadi Gagal.',
        );
    }

    /**
     * @return array<int, string>
     */
    private function sentTelegramTexts(): array
    {
        $texts = [];

        Http::assertSent(function ($request) use (&$texts): bool {
            if (str_contains($request->url(), 'sendMessage')) {
                $texts[] = (string) ($request['text'] ?? '');
            }

            return true;
        });

        return $texts;
    }
}

<?php

namespace Tests\Feature\Bot;

use App\Events\InvoiceStatusUpdated;
use App\Listeners\NotifyBotOrderStatusListener;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Services\Bot\Adapters\TelegramAdapter;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotCommandParser;
use App\Services\Bot\BotMessageFormatter;
use App\Support\TelegramIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Kontrak identitas Telegram lintas gateway.
 *
 * Regresi yang dikunci di sini: `TelegramAdapter` mengirim identitas
 * ber-scope `telegram:<scope>:<id>`, sementara
 * `CheckoutOrderService::gatewayPrincipal()` hanya menerima
 * `/^telegram:\d+$/`. Akibatnya `gateway_principal` NULL → listener mengirim
 * `chat_id` kosong → Telegram API menolak → user tidak pernah dapat notif
 * status order (12 dari 19 order Telegram di staging).
 *
 * ATURAN: nilai kontrak (external_user_id) diambil DARI adapter, tidak
 * ditulis literal — kalau adapter mengubah format, test ini gagal.
 */
class TelegramIdentityContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Cache::flush();
    }

    public function test_identity_normalizes_every_producer_form_to_one_canonical_principal(): void
    {
        // Bentuk ber-scope = keluaran TelegramAdapter / handleDeposit.
        $this->assertSame('telegram:6252007210', TelegramIdentity::principal('telegram:default:6252007210'));
        $this->assertSame('telegram:6252007210', TelegramIdentity::principal('telegram:bot2:6252007210'));
        // Bentuk lama (order sebelum kolom principal ada).
        $this->assertSame('telegram:6252007210', TelegramIdentity::principal('telegram:6252007210'));
        // Bentuk mentah.
        $this->assertSame('telegram:6252007210', TelegramIdentity::principal('6252007210'));
        $this->assertSame('telegram:6252007210', TelegramIdentity::principal(6252007210));

        // Non-identitas → null (jangan pernah simpan principal palsu).
        $this->assertNull(TelegramIdentity::principal(''));
        $this->assertNull(TelegramIdentity::principal('   '));
        $this->assertNull(TelegramIdentity::principal(null));
        $this->assertNull(TelegramIdentity::principal('telegram:default:'));
        $this->assertNull(TelegramIdentity::principal('whatsapp:6281234567890'));

        // chat_id = ID numerik; email legacy = bentuk synthetic lama.
        $this->assertSame('6252007210', TelegramIdentity::chatId('telegram:default:6252007210'));
        $this->assertSame('6252007210', TelegramIdentity::chatId('telegram:6252007210'));
        $this->assertSame('telegram:6252007210@telegram.user', TelegramIdentity::legacyEmail('telegram:default:6252007210'));
    }

    /**
     * Chain penuh: adapter → invoice → principal kanonik → notifikasi terkirim.
     * Identitas diambil dari context yang BENAR-BENAR dikirim adapter.
     */
    public function test_adapter_identity_produces_canonical_principal_and_reaches_telegram_notification(): void
    {
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.bot_scope' => 'default',
        ]);

        // 1) Identitas nyata dari adapter (bukan literal di test).
        $adapterIdentity = $this->captureAdapterIdentity();

        $this->assertSame('telegram:default:6252007210', $adapterIdentity['external_user_id']);

        [$service] = $this->createManualCheckoutFixtures();

        // 2) Order dibuat dengan identitas persis seperti yang adapter kirim.
        $orderId = $this->postJson('/api/gateway/invoices', [
            'source' => 'telegram_gateway',
            'service' => $service->id,
            'payment_method' => 'MANUAL',
            'nomor' => '081234567890',
            'uid' => '123456',
            'zone' => '1234',
            'external_user_id' => $adapterIdentity['external_user_id'],
        ])->assertOk()->json('data.order_id');

        $order = Pembelian::query()->where('order_id', $orderId)->firstOrFail();
        $this->assertSame('telegram:6252007210', $order->gateway_principal);

        // 3) `status` harus bisa diakses pemilik dengan identitas ber-scope.
        $this->getJson('/api/gateway/invoices/' . $orderId
            . '?source=telegram_gateway&external_user_id=' . urlencode($adapterIdentity['external_user_id']))
            ->assertOk()
            ->assertJsonPath('ok', true);

        // 4) Notifikasi status order sampai ke chat pribadi user.
        $order->pembayaran()->update(['status' => 'Lunas']);
        $order->update(['status' => 'Sukses']);

        SettingWeb::query()->create($this->settingPayload(['telegram_bot_token' => 'TEST-TG-TOKEN']));
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);
        config(['services.telegram-bot-api.token' => null]);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated(['order_id' => $orderId]));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && $request['chat_id'] === '6252007210';
        });
    }

    /**
     * Order lama (principal NULL) tetap dapat notif lewat email legacy.
     * Ini data nyata di staging: 12 order Telegram principal NULL.
     */
    public function test_telegram_notification_falls_back_to_legacy_email_when_principal_is_null(): void
    {
        $order = Pembelian::query()->create([
            'order_id' => 'TG-LEGACY-001',
            'username' => 'Anonim',
            'user_id' => '12345',
            'nickname' => 'Player',
            'layanan' => '100 Diamond',
            'harga' => 10500,
            'profit' => 500,
            'provider_order_id' => '',
            'status' => 'Sukses',
            'log' => json_encode([]),
            'traffic_source' => 'telegram_gateway',
            'gateway_principal' => null,
            'email_pembeli' => '6252007210@telegram.user',
            'tipe_transaksi' => 'game',
            'environment' => 'live',
            'is_sandbox' => false,
        ]);

        $order->pembayaran()->create([
            'order_id' => $order->order_id,
            'harga' => 10500,
            'no_pembayaran' => 'TG-VA-LEGACY',
            'no_pembeli' => '-',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        SettingWeb::query()->create($this->settingPayload(['telegram_bot_token' => 'TEST-TG-TOKEN']));
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);
        config(['services.telegram-bot-api.token' => null]);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated(['order_id' => 'TG-LEGACY-001']));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && $request['chat_id'] === '6252007210';
        });
    }

    public function test_telegram_notification_skips_order_without_any_resolvable_identity(): void
    {
        $order = Pembelian::query()->create([
            'order_id' => 'TG-NOID-001',
            'username' => 'Anonim',
            'user_id' => '12345',
            'nickname' => 'Player',
            'layanan' => '100 Diamond',
            'harga' => 10500,
            'profit' => 500,
            'provider_order_id' => '',
            'status' => 'Sukses',
            'log' => json_encode([]),
            'traffic_source' => 'telegram_gateway',
            'gateway_principal' => null,
            'email_pembeli' => null,
            'tipe_transaksi' => 'game',
            'environment' => 'live',
            'is_sandbox' => false,
        ]);

        $order->pembayaran()->create([
            'order_id' => $order->order_id,
            'harga' => 10500,
            'no_pembayaran' => 'TG-VA-NOID',
            'no_pembeli' => '-',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        SettingWeb::query()->create($this->settingPayload(['telegram_bot_token' => 'TEST-TG-TOKEN']));
        Http::fake();
        config(['services.telegram-bot-api.token' => null]);

        (new NotifyBotOrderStatusListener)->handle(new InvoiceStatusUpdated(['order_id' => 'TG-NOID-001']));

        // Tidak ada identitas → jangan kirim ke chat_id kosong, dan jangan
        // cache key (event berikutnya masih boleh mencoba).
        Http::assertNothingSent();
        $this->assertFalse(Cache::has('bot:notif:TG-NOID-001:success'));
    }

    /**
     * Jalankan TelegramAdapter dengan payload update Telegram nyata dan
     * tangkap context yang dikirim ke handler.
     *
     * @return array<string, mixed>
     */
    private function captureAdapterIdentity(): array
    {
        $captured = [];

        $handler = $this->mock(BotCommandHandler::class, function (MockInterface $mock) use (&$captured): void {
            $mock->shouldReceive('handle')
                ->once()
                ->andReturnUsing(function ($command, $args, array $context) use (&$captured): array {
                    $captured = $context;

                    return ['text' => 'ok', 'buttons' => []];
                });
        });

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $adapter = new TelegramAdapter(
            app(BotCommandParser::class),
            $handler,
            app(BotMessageFormatter::class),
        );

        $response = $adapter->handle(Request::create('/api/webhooks/bot/telegram', 'POST', [
            'update_id' => 900001,
            'message' => [
                'message_id' => 111,
                'chat' => ['id' => 6252007210],
                'from' => ['id' => 6252007210, 'first_name' => 'Mings'],
                'text' => '/start',
            ],
        ]));

        $this->assertSame('ok', $response->getData(true)['status'] ?? null);
        $this->assertNotEmpty($captured, 'Adapter tidak memanggil handler.');

        return $captured;
    }

    /**
     * @return array{0: Layanan, 1: Method}
     */
    private function createManualCheckoutFixtures(): array
    {
        $category = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'tipe' => 'game',
            'require_user_id' => true,
        ]);

        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '100 Diamonds',
            'provider' => 'manual',
            'provider_id' => 'ml-100',
            'harga_member' => 10000,
            'harga_platinum' => 10000,
            'harga_gold' => 10000,
            'profit_member' => 1000,
            'profit_platinum' => 1000,
            'profit_gold' => 1000,
        ]);

        $method = Method::query()->create([
            'name' => 'Manual Transfer',
            'code' => 'MANUAL',
            'payment' => 'manual',
            'tipe' => 'manual',
            'images' => 'manual.png',
            'keterangan' => 'Manual transfer desc',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);

        return [$service, $method];
    }

    /**
     * @return array<string, mixed>
     */
    private function settingPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Test description',
            'keywords' => 'topup,test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'warna1' => '#123456',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#654321',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
        ], $overrides);
    }
}

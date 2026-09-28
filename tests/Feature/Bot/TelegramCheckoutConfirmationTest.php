<?php

namespace Tests\Feature\Bot;

use App\Models\BotCheckoutIntent;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembelian;
use App\Services\Bot\Adapters\TelegramAdapter;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotCommandParser;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Checkout\BotCheckoutIntentService;
use App\Services\Gateway\GatewayInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regresi: checkout Telegram bot GAGAL di langkah konfirmasi.
 *
 * Gejala nyata (staging, 24/09/2026 19:52): user mengirim UID, bot
 * menampilkan ringkasan + "Konfirmasi berlaku 15 menit.", lalu setelah
 * konfirmasi bot membalas "Validasi gagal: Checkout belum dikonfirmasi
 * atau tidak valid."
 *
 * AKAR MASALAH — basis hashing identitas tidak konsisten:
 *
 *   - Intent dibuat dari context MENTAH adapter:
 *       external_user_id = telegram:default:6252007210
 *   - prepareMutation() menerima context yang SUDAH dinormalisasi oleh
 *     GatewayInvoiceService::normalizeContext():
 *       external_user_id = telegram:6252007210
 *
 *   sender_fingerprint = HMAC(source|external_user_id). Dua basis berbeda
 *   -> dua hash berbeda -> intent yang sudah ter-claim TIDAK ditemukan.
 *
 * KENAPA TEST LAMA LOLOS: test sebelumnya menembak endpoint publik
 * `/api/gateway/invoices` yang memaksa `require_bot_intent => false`,
 * sehingga jalur intent tidak pernah dieksekusi sama sekali.
 *
 * ATURAN: test di sini WAJIB lewat jalur intent asli (create -> claim ->
 * prepareMutation -> createInvoice), dan identitas diambil DARI adapter.
 */
class TelegramCheckoutConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Cache::flush();
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.order_enabled' => true,
        ]);
    }

    /**
     * INTI REGRESI: identitas mentah dari adapter harus menghasilkan
     * fingerprint yang SAMA dengan identitas yang sudah dinormalisasi,
     * supaya prepareMutation() menemukan intent yang baru di-claim.
     */
    public function test_sender_fingerprint_survives_identity_normalization(): void
    {
        $service = app(BotCheckoutIntentService::class);
        [$service_, $method] = $this->fixtures();

        $adapterIdentity = $this->adapterIdentity();

        // 1) Intent dibuat dengan identitas MENTAH (persis seperti adapter).
        $created = $service->create(
            $this->payload($service_, $method),
            $this->quote($service_, $method),
            [
                'source' => 'telegram_gateway',
                'external_user_id' => $adapterIdentity,
                'message_id' => 'telegram:default:6252007210:111',
            ],
        );

        $intent = $created['intent'];
        $this->assertSame('telegram:default:6252007210', $adapterIdentity);

        // 2) Claim lewat jalur konfirmasi bot.
        $claim = $service->claim($created['token'], [
            'source' => 'telegram_gateway',
            'external_user_id' => $adapterIdentity,
            'message_id' => 'telegram:default:6252007210:112',
        ]);
        $this->assertSame('claimed', $claim['status']);

        // 3) prepareMutation MENERIMA context yang sudah dinormalisasi —
        //    inilah yang dilakukan GatewayInvoiceService::normalizeContext().
        $gateway = app(GatewayInvoiceService::class);
        $normalize = new \ReflectionMethod($gateway, 'normalizeExternalUserId');
        $normalize->setAccessible(true);
        $normalizedIdentity = $normalize->invoke($gateway, 'telegram_gateway', $adapterIdentity);

        $this->assertSame('telegram:6252007210', $normalizedIdentity, 'Normalisasi mengubah bentuk identitas.');
        $this->assertNotSame($adapterIdentity, $normalizedIdentity, 'Prasyarat regresi: bentuk memang berbeda.');

        // INI yang sebelumnya melempar "Checkout belum dikonfirmasi atau tidak valid."
        $mutated = $service->prepareMutation($intent->intent_id, [
            'source' => 'telegram_gateway',
            'external_user_id' => $normalizedIdentity,
            'message_id' => 'telegram:default:6252007210:112',
        ]);

        $this->assertSame($intent->intent_id, $mutated->intent_id);
        $this->assertSame(BotCheckoutIntent::STATUS_PROCESSING, $mutated->status);
        $this->assertNotEmpty($mutated->merchant_reference, 'merchant_reference harus ter-generate.');
    }

    /**
     * Chain penuh lewat HTTP: webhook Telegram → intent → konfirmasi →
     * invoice jadi. Ini yang gagal di staging.
     */
    public function test_full_telegram_confirmation_chain_creates_invoice(): void
    {
        [$service_, $method] = $this->fixtures();

        $service = app(BotCheckoutIntentService::class);
        $adapterIdentity = $this->adapterIdentity();

        // Buat intent seperti handleUnknownInput (UID) melakukannya.
        $created = $service->create(
            $this->payload($service_, $method),
            $this->quote($service_, $method),
            [
                'source' => 'telegram_gateway',
                'external_user_id' => $adapterIdentity,
                'message_id' => 'telegram:default:6252007210:111',
            ],
        );

        // Konfirmasi via command handler — context mentah dari adapter.
        $handler = app(BotCommandHandler::class);
        $response = $handler->handle('konfirmasi', [(string) $created['token']], [
            'source' => 'telegram_gateway',
            'external_user_id' => $adapterIdentity,
            'telegram_user_id' => 6252007210,
            'telegram_bot_scope' => 'default',
            'telegram_chat_id' => 6252007210,
            'message_id' => 'telegram:default:6252007210:112',
            'email' => '6252007210@telegram.user',
        ]);

        $text = (string) ($response['text'] ?? '');

        // Pesan kegagalan asli TIDAK boleh muncul lagi.
        $this->assertStringNotContainsString('Checkout belum dikonfirmasi atau tidak valid', $text);
        $this->assertStringNotContainsString('Validasi gagal', $text);

        // Invoice benar-benar dibuat.
        $order = Pembelian::query()
            ->where('traffic_source', 'telegram_gateway')
            ->latest('id')
            ->first();

        $this->assertNotNull($order, 'Invoice Telegram tidak terbentuk. Respons bot: ' . $text);
        $this->assertSame('telegram:6252007210', $order->gateway_principal);

        $intent = BotCheckoutIntent::query()->where('intent_id', $created['intent']->intent_id)->firstOrFail();
        $this->assertSame(BotCheckoutIntent::STATUS_COMPLETED, $intent->status);
        $this->assertSame((string) $order->order_id, (string) $intent->order_id);
    }

    /**
     * Intent yang macet di `processing` tanpa dispatch provider harus bisa
     * dikonfirmasi ulang (data nyata staging: intent 18 & 19, ter-claim
     * tapi tidak pernah sampai provider).
     */
    public function test_stuck_processing_intent_can_be_recovered(): void
    {
        $service = app(BotCheckoutIntentService::class);
        [$service_, $method] = $this->fixtures();
        $adapterIdentity = $this->adapterIdentity();

        $created = $service->create(
            $this->payload($service_, $method),
            $this->quote($service_, $method),
            [
                'source' => 'telegram_gateway',
                'external_user_id' => $adapterIdentity,
                'message_id' => 'telegram:default:6252007210:111',
            ],
        );

        $service->claim($created['token'], [
            'source' => 'telegram_gateway',
            'external_user_id' => $adapterIdentity,
            'message_id' => 'telegram:default:6252007210:112',
        ]);

        // Simulasi macet: ter-claim, tidak ada dispatch provider, lewat 1 menit.
        $intent = $created['intent']->fresh();
        $intent->forceFill(['processing_at' => now()->subMinutes(5)])->save();

        $recovered = $service->claim($created['token'], [
            'source' => 'telegram_gateway',
            'external_user_id' => $adapterIdentity,
            'message_id' => 'telegram:default:6252007210:113',
        ]);

        $this->assertSame('claimed', $recovered['status'], 'Intent macet harus bisa dipulihkan.');
        $this->assertSame(BotCheckoutIntent::STATUS_PROCESSING, $recovered['intent']->status);
        $this->assertNull($recovered['intent']->provider_dispatched_at);
    }

    /**
     * Pengaman: intent yang SUDAH dispatch provider TIDAK boleh di-claim
     * ulang — hasilnya ambigu dan wajib direkonsiliasi.
     */
    public function test_processing_intent_after_provider_dispatch_is_not_replayable(): void
    {
        $service = app(BotCheckoutIntentService::class);
        [$service_, $method] = $this->fixtures();
        $adapterIdentity = $this->adapterIdentity();

        $created = $service->create(
            $this->payload($service_, $method),
            $this->quote($service_, $method),
            [
                'source' => 'telegram_gateway',
                'external_user_id' => $adapterIdentity,
                'message_id' => 'telegram:default:6252007210:111',
            ],
        );

        $service->claim($created['token'], [
            'source' => 'telegram_gateway',
            'external_user_id' => $adapterIdentity,
            'message_id' => 'telegram:default:6252007210:112',
        ]);

        $intent = $created['intent']->fresh();
        $intent->forceFill([
            'processing_at' => now()->subMinutes(5),
            'provider_dispatched_at' => now()->subMinutes(4),
        ])->save();

        $again = $service->claim($created['token'], [
            'source' => 'telegram_gateway',
            'external_user_id' => $adapterIdentity,
            'message_id' => 'telegram:default:6252007210:113',
        ]);

        $this->assertSame('processing', $again['status'], 'Provider sudah dipanggil — jangan diulang.');
    }

    /**
     * Identitas NYATA dari TelegramAdapter — bukan literal di test.
     */
    private function adapterIdentity(): string
    {
        $captured = null;

        $handler = new class extends BotCommandHandler {
            public array $captured = [];

            public function __construct() {}

            public function handle(?string $command, array $args, array $context): array
            {
                $this->captured = $context;

                return ['text' => 'ok', 'buttons' => []];
            }
        };

        $adapter = new TelegramAdapter(
            app(BotCommandParser::class),
            $handler,
            app(BotMessageFormatter::class),
        );

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $adapter->handle(Request::create('/api/webhooks/bot/telegram', 'POST', [
            'update_id' => 910001,
            'message' => [
                'message_id' => 111,
                'chat' => ['id' => 6252007210],
                'from' => ['id' => 6252007210, 'first_name' => 'Mings'],
                'text' => '1840180550',
            ],
        ]));

        $identity = (string) ($handler->captured['external_user_id'] ?? '');
        $this->assertNotSame('', $identity, 'Adapter tidak mengirim external_user_id.');

        return $identity;
    }

    /**
     * @return array{0: Layanan, 1: Method}
     */
    private function fixtures(): array
    {
        $category = Kategori::factory()->create([
            'kode' => 'free-fire',
            'tipe' => 'game',
            'require_user_id' => true,
        ]);

        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '40 Diamond Free Fire',
            'provider' => 'manual',
            'provider_id' => 'ff-40',
            'harga_member' => 7036,
            'harga_platinum' => 7036,
            'harga_gold' => 7036,
            'profit_member' => 950,
            'profit_platinum' => 950,
            'profit_gold' => 950,
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
     * Payload persis seperti yang dikirim handler saat user memasukkan UID.
     * Handler menambahkan field kontak dari context — di Telegram selalu ada
     * `email` (`<id>@telegram.user`), bukan `nomor`.
     */
    private function payload(Layanan $service, Method $method): array
    {
        return [
            'service' => (string) $service->id,
            'payment_method' => $method->code,
            'uid' => '1840180550',
            'zone' => null,
            'nickname' => '@Mavall',
            'input_label' => 'UID',
            'email' => '6252007210@telegram.user',
        ];
    }

    /**
     * Quote NYATA dari pricing service — bukan literal, supaya fingerprint
     * quote tidak pernah menyimpang dari yang dihitung saat invoice dibuat.
     */
    private function quote(Layanan $service, Method $method): array
    {
        return app(\App\Services\Gateway\GatewayPricingService::class)
            ->quote($this->payload($service, $method), null);
    }
}

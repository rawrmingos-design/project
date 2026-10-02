<?php

namespace Tests\Feature\Bot;

use App\Models\BotCheckoutIntent;
use App\Models\CustomInput;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\SettingWeb;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotLocale;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\BotNumericMenuStore;
use App\Services\Bot\TelegramChannelMembershipService;
use App\Services\Deposit\DepositService;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayCheckIdService;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\LeaderboardService;
use App\Services\Order\OrderHistoryNavigationStateService;
use App\Services\Order\OrderHistoryService;
use App\Services\PaymentMethodCatalogService;
use App\Services\Telegram\TelegramLinkService;
use App\Services\Telegram\TelegramUserResolver;
use App\Services\Whatsapp\WhatsappLinkService;
use App\Services\Whatsapp\WhatsappUserResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Produk yang meminta NOMOR WHATSAPP (bukan User ID) harus:
 *
 *  1. menampilkan petunjuk yang benar — label "No WhatsApp" TIDAK boleh
 *     disusul `Format: \`UID\`` / `Contoh: \`12345\``,
 *  2. memakai format internasional (+62) sebagai contoh,
 *  3. MENOLAK input yang bukan nomor WhatsApp yang sah (mis. `123456` yang
 *     selama ini lolos sebagai "UID"), dan
 *  4. menyimpan nomor dalam digit telanjang tanpa mengubah prefix-nya.
 *
 * Gejala nyata (laporan pemilik produk): layar checkout produk Alight Motion
 * menulis "🎮 Masukkan No WhatsApp" lalu "Format: `UID`" + "Contoh: `12345`" —
 * pengguna dipandu mengisi game ID ke kolom nomor telepon. Di DB staging
 * memang ada order produk itu dengan `user_id = 123456`.
 *
 * ⚠️ Prefix TIDAK diubah jadi +62, dan deteksi sengaja HANYA pada kata
 * "whatsapp" (bukan "nomor"/"telepon"). Alasannya sama: nomor tujuan
 * diteruskan apa adanya ke Digiflazz sebagai `customer_no`, dan provider itu
 * menerima nomor lokal — order prod terbukti Sukses dengan `08116073802`, dan
 * responsnya menggema `customer_no` itu apa adanya (`rc: 00`). Rewriting ke
 * `+62` berisiko kena rc 52 "Prefix Tidak Sesuai Dengan Operator" pada produk
 * topup, dan produk pulsa/voucher (Google Play/Steam) memang berlabel
 * "No WhatsApp" tetapi berisi nomor pelanggan. Karena itu ada test negatif
 * untuk keduanya di bawah.
 */
class TelegramWhatsappInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.bot_scope' => 'default',
            'services.telegram-bot-api.order_enabled' => true,
        ]);

        // Saklar order dibaca dari DB di jalur web; tanpa baris ini handler
        // menjawab "Fitur order belum tersedia" dan test tidak pernah
        // menyentuh layar checkout sama sekali.
        SettingWeb::query()->updateOrInsert(['id' => 1], [
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
        ]);
        config(['app.name' => 'Test Store']);

        // Layar harga butuh satu metode pembayaran yang terlihat.
        Method::query()->updateOrCreate(['code' => 'QRIS'], [
            'name' => 'QRIS',
            'payment' => 'qris',
            'tipe' => 'qris',
            'images' => 'qris.png',
            'keterangan' => 'QRIS',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);
    }

    private function context(): array
    {
        return [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:default:6252007210',
            'message_id' => 'telegram:default:6252007210:9001',
            'email' => '6252007210@telegram.user',
        ];
    }

    /**
     * Handler membaca label & placeholder dari tabel `custom_inputs` (lewat
     * katalog), BUKAN dari respons quote — jadi fixture-nya harus baris nyata.
     * `field_1` formatnya `Label,Placeholder,type`.
     */
    private function makeProduct(string $code, string $name, string $tipe, string $field1): Layanan
    {
        $category = Kategori::factory()->create([
            'kode' => $code,
            'nama' => $name,
            'tipe' => $tipe,
            'status' => 'active',
            'server_id' => 0,
            'require_user_id' => true,
        ]);

        CustomInput::query()->updateOrCreate(
            ['kategori_id' => (string) $category->id],
            [
                'field_1' => $field1,
                'field_2' => null,
                'field_select_title' => null,
                'field_select' => null,
            ],
        );

        return Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => $name . ' Paket Uji',
            'status' => 'available',
        ]);
    }

    private function handler(): BotCommandHandler
    {
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });
        $checkId = $this->mock(GatewayCheckIdService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('check')->andReturn([
                'ok' => true,
                'data' => ['skip_check' => true, 'valid' => null, 'nickname' => null],
            ]);
        });

        return new BotCommandHandler(
            app(GatewayCatalogService::class),
            app(PaymentMethodCatalogService::class),
            app(GatewayPricingService::class),
            $checkId,
            $invoice,
            app(BotMessageFormatter::class),
            app(TelegramChannelMembershipService::class),
            app(LeaderboardService::class),
            app(WhatsappUserResolver::class),
            app(WhatsappLinkService::class),
            app(DepositService::class),
            app(OrderHistoryService::class),
            app(TelegramUserResolver::class),
            app(TelegramLinkService::class),
            app(OrderHistoryNavigationStateService::class),
            app(BotLocale::class),
            app(BotNumericMenuStore::class),
        );
    }

    /**
     * Layar harga produk "No WhatsApp" harus memandu ke FORMAT NOMOR, bukan UID.
     */
    public function test_layar_harga_produk_nomor_whatsapp_memakai_format_internasional(): void
    {
        $service = $this->makeProduct('alight-motion-vip', 'Alight Motion', 'app', 'No WhatsApp,Ketikan No,number');

        $response = $this->handler()->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $text = (string) $response['text'];

        $this->assertStringContainsString('Masukkan No WhatsApp', $text);
        $this->assertStringContainsString('Format: `+62xxxxxxxxxx`', $text);
        $this->assertStringContainsString('Contoh: `+628123456789`', $text);

        // Inilah bug yang dilaporkan: petunjuk UID muncul di kolom nomor.
        $this->assertStringNotContainsString('Format: `UID`', $text);
        $this->assertStringNotContainsString('Contoh: `12345`', $text);
        $this->assertStringNotContainsString('🎮', $text);
    }

    /**
     * Bot berbahasa Inggris harus memakai kunci lang `_whatsapp`, bukan
     * `_uid` — inilah kelas bug yang sama dengan kebocoran Indonesia
     * sebelumnya (literal di cabang bahasa).
     */
    public function test_petunjuk_nomor_whatsapp_ikut_bahasa_aktif(): void
    {
        $service = $this->makeProduct('alight-motion-vip', 'Alight Motion', 'app', 'No WhatsApp,Ketikan No,number');
        $quote = app(GatewayPricingService::class)->quote([
            'service' => (string) $service->id,
            'payment_method' => 'QRIS',
        ], null);

        // Handler langsung melewati adapter (yang menerapkan locale), jadi
        // lokalisasi diuji lewat formatter — pola yang sama dipakai
        // TelegramCopyPhaseOneTest.
        $formatter = app(BotMessageFormatter::class);

        app()->setLocale('id');
        $id = (string) $formatter->formatPriceQuote($quote, true, 'telegram_gateway')['text'];

        app()->setLocale('en');
        $en = (string) $formatter->formatPriceQuote($quote, true, 'telegram_gateway')['text'];

        $this->assertStringContainsString('📱 *Masukkan No WhatsApp*', $id);
        $this->assertStringContainsString('Contoh: `+628123456789`', $id);

        $this->assertStringContainsString('📱 *Enter No WhatsApp*', $en);
        $this->assertStringContainsString('Example: `+628123456789`', $en);

        // Petunjuk UID tidak boleh muncul di kolom nomor, di bahasa mana pun.
        foreach ([$id, $en] as $teks) {
            $this->assertStringNotContainsString('Format: `UID`', $teks);
            $this->assertStringNotContainsString('Contoh: `12345`', $teks);
            $this->assertStringNotContainsString('Example: `12345`', $teks);
            $this->assertStringNotContainsString('🎮', $teks);
        }
    }

    /**
     * Input yang BUKAN nomor WhatsApp sah harus ditolak (retry), bukan
     * diteruskan sebagai nomor tujuan.
     */
    public function test_input_bukan_nomor_whatsapp_ditolak_dan_tidak_membuat_intent(): void
    {
        $service = $this->makeProduct('alight-motion-vip', 'Alight Motion', 'app', 'No WhatsApp,Ketikan No,number');

        $handler = $this->handler();
        $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());

        // `123456` inilah yang dulu lolos dan tersimpan sebagai nomor WA.
        $retry = $handler->handle('123456', [], $this->context());

        $this->assertStringContainsString('Format ID belum sesuai', (string) $retry['text']);
        $this->assertStringContainsString('+62xxxxxxxxxx', (string) $retry['text']);
        $this->assertSame(0, BotCheckoutIntent::query()->count(), 'Intent tidak boleh dibuat dari nomor tidak sah.');
    }

    /**
     * Nomor lokal (08xx) dibuang separatornya dan disimpan dalam digit
     * telanjang — prefix TIDAK diubah, karena itulah bentuk yang selama ini
     * diterima Digiflazz.
     */
    public function test_nomor_lokal_disimpan_digit_telanjang_tanpa_ubah_prefix(): void
    {
        $service = $this->makeProduct('alight-motion-vip', 'Alight Motion', 'app', 'No WhatsApp,Ketikan No,number');

        $handler = $this->handler();
        $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $confirmation = $handler->handle('0812-3456-789', [], $this->context());

        $intent = BotCheckoutIntent::query()->firstOrFail();
        $this->assertSame('08123456789', $intent->payload['uid'], 'Prefix lokal dipertahankan; hanya separator yang dibuang.');

        // Ringkasan konfirmasi memakai label produk + nomor — bukan 'UID'.
        $this->assertStringContainsString('No WhatsApp', (string) $confirmation['text']);
        $this->assertStringNotContainsString('UID', (string) $confirmation['text']);
    }

    /**
     * Voucher yang meminta nomor pelanggan (Google Play/Steam) bukan nomor
     * WhatsApp pribadi: digitnya harus diteruskan apa adanya ke provider,
     * bukan ditulis ulang ke +62.
     */
    public function test_voucher_nomor_pelanggan_tidak_ditulis_ulang_ke_62(): void
    {
        $service = $this->makeProduct('google-play', 'Google Play (ID)', 'voucher', 'No WhatsApp,Masukkan Nomor Telp,number');

        $handler = $this->handler();
        $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $handler->handle('08788761254', [], $this->context());

        $intent = BotCheckoutIntent::query()->firstOrFail();
        $this->assertSame('08788761254', $intent->payload['uid'], 'Nomor pelanggan voucher diteruskan apa adanya.');
    }

    /**
     * Nomor yang sudah dalam format internasional tetap diterima, dan
     * disimpan sebagai digit telanjang (tanpa `+`).
     */
    public function test_nomor_internasional_diterima(): void
    {
        $service = $this->makeProduct('alight-motion-vip', 'Alight Motion', 'app', 'No WhatsApp,Ketikan No,number');

        $handler = $this->handler();
        $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $handler->handle('+628123456789', [], $this->context());

        $intent = BotCheckoutIntent::query()->firstOrFail();
        $this->assertSame('628123456789', $intent->payload['uid']);
    }

    /**
     * TEST NEGATIF — produk PULSA berlabel "Nomor Telepon" TIDAK boleh
     * dianggap WhatsApp: provider (Digiflazz) menerima nomornya apa adanya.
     */
    public function test_produk_pulsa_tidak_ikut_dinormalkan_sebagai_whatsapp(): void
    {
        $service = $this->makeProduct('telkomsel', 'TELKOMSEL', 'pulsa', 'Nomor Telepon,0857******,number');

        $handler = $this->handler();
        $quote = $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());

        // Petunjuk pulsa tetap jalur ID biasa, bukan format nomor WA.
        $this->assertStringNotContainsString('+62xxxxxxxxxx', (string) $quote['text']);

        $handler->handle('08123456789', [], $this->context());

        $intent = BotCheckoutIntent::query()->firstOrFail();
        $this->assertSame('08123456789', $intent->payload['uid'], 'Nomor pulsa harus diteruskan apa adanya ke provider.');
    }

    /**
     * Produk EMAIL tidak boleh terpengaruh perubahan ini.
     */
    public function test_produk_email_tetap_memakai_jalur_email(): void
    {
        $service = $this->makeProduct('spotify-premium-vip', 'Spotify Premium', 'app', 'Email,Ketikan Email,text');

        $handler = $this->handler();
        $quote = $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());

        $this->assertStringContainsString('email@contoh.com', (string) $quote['text']);
        $this->assertStringNotContainsString('+62xxxxxxxxxx', (string) $quote['text']);

        // Email tetap divalidasi sebagai email, bukan nomor.
        $retry = $handler->handle('123456', [], $this->context());
        $this->assertStringContainsString('Format ID belum sesuai', (string) $retry['text']);

        $handler->handle('nama@email.com', [], $this->context());
        $intent = BotCheckoutIntent::query()->firstOrFail();
        $this->assertSame('nama@email.com', $intent->payload['uid']);
        $this->assertStringContainsString('Email', (string) $quote['text']);
    }
}

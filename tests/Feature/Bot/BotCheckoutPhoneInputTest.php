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
 * Produk yang tujuan pembeliannya NOMOR TELEPON — pulsa/kuota (Telkomsel,
 * XL, Indosat) dan produk app berbasis nomor (Getcontact) — harus:
 *
 *  1. menampilkan petunjuk NOMOR, bukan `Format: \`UID\`` + `Contoh: \`12345\``,
 *  2. MENOLAK nomor yang panjangnya tidak sah, dan
 *  3. meneruskan nomor apa adanya (digit telanjang, prefix TIDAK ditulis ulang).
 *
 * Gejala nyata: layar checkout menulis "🎮 *Masukkan Nomor Telepon*" lalu
 * "Format: `UID`" + "Contoh: `12345`" — pengguna dipandu mengisi game ID ke
 * kolom nomor telepon. Bot memang tidak memvalidasi apa pun di jalur ini
 * (hanya membuang karakter non-digit), dan itu sudah meninggalkan jejak di DB:
 *
 *     pulsa-telkomsel  EM221973570225  uid=081399910772   (12 digit, kurang 1)
 *     pulsa-xl         uid=087730734911 (11 digit — pernah Sukses)
 *
 * ⚠️ Dua hal yang SENGAJA tidak dilakukan:
 *
 *  - Prefix TIDAK ditulis ulang ke `+62`. Nomor tujuan diteruskan apa adanya
 *    sebagai `customer_no` dan Digiflazz menerima format LOKAL — order prod
 *    terbukti Sukses dengan `08…`, dan placeholder di `custom_inputs` untuk
 *    ketiga kategori pulsa pun memakai bentuk lokal (`0857******`).
 *  - Deteksi TIDAK diperluas ke label `ID`/`UID`/`Server` yang bertipe
 *    `number`. Field game memang bertipe number, jadi tipe TIDAK boleh
 *    dipakai sebagai penanda; hanya kata pada LABEL/PLACEHOLDER yang aman
 *    (itulah yang dipakai jalur email dan WhatsApp sejak awal).
 */
class BotCheckoutPhoneInputTest extends TestCase
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
        // menjawab "Fitur order belum tersedia" dan checkout tidak tersentuh.
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
     * Label & placeholder dibaca dari tabel `custom_inputs` (lewat katalog),
     * BUKAN dari respons quote — jadi fixture-nya wajib baris nyata.
     * Format `field_1`: `Label,Placeholder,type`.
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
     * Getcontact memakai label `Nomor` (bukan `No WhatsApp`): layar harga harus
     * memandu ke format nomor.
     */
    public function test_label_nomor_memakai_petunjuk_nomor_bukan_uid(): void
    {
        $service = $this->makeProduct('Getcontact-premium-vip', 'Getcontact Premium', 'app', 'Nomor,Ketikan Nomor,number');

        $response = $this->handler()->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $text = (string) $response['text'];

        $this->assertStringContainsString('Masukkan Nomor', $text);
        $this->assertStringContainsString('Format: `08xxxxxxxxxx`', $text);
        $this->assertStringContainsString('Contoh: `08123456789`', $text);

        // Inilah bug-nya: petunjuk UID muncul di kolom nomor.
        $this->assertStringNotContainsString('Format: `UID`', $text);
        $this->assertStringNotContainsString('Contoh: `12345`', $text);
        $this->assertStringNotContainsString('🎮', $text);

        // Prefix TIDAK dipromosikan ke +62 di layar nomor telepon.
        $this->assertStringNotContainsString('+62xxxxxxxxxx', $text);
    }

    /**
     * Produk pulsa berlabel `Nomor Telepon` (placeholder dari data: `0857******`).
     */
    public function test_produk_pulsa_memakai_petunjuk_nomor(): void
    {
        $service = $this->makeProduct('pulsa-telkomsel', 'TELKOMSEL', 'pulsa', 'Nomor Telepon,0857******,number');

        $response = $this->handler()->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $text = (string) $response['text'];

        $this->assertStringContainsString('Masukkan Nomor Telepon', $text);
        $this->assertStringContainsString('Format: `08xxxxxxxxxx`', $text);
        $this->assertStringNotContainsString('Format: `UID`', $text);
        $this->assertStringNotContainsString('Contoh: `12345`', $text);
    }

    /**
     * Label berbahasa Inggris (`Number Phone`) juga harus dikenali — datanya
     * memang begitu di `pulsa-xl`.
     */
    public function test_label_number_phone_ikut_dikenali(): void
    {
        $service = $this->makeProduct('pulsa-xl', 'XL', 'pulsa', 'Number Phone,0857******,number');

        $response = $this->handler()->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $text = (string) $response['text'];

        $this->assertStringContainsString('Masukkan Number Phone', $text);
        $this->assertStringContainsString('Format: `08xxxxxxxxxx`', $text);
        $this->assertStringNotContainsString('Format: `UID`', $text);
    }

    /**
     * Nomor terlalu pendek harus DITOLAK, bukan diteruskan ke provider.
     * `0812345` (7 digit) adalah bentuk yang dulu lolos apa adanya.
     */
    public function test_nomor_terlalu_pendek_ditolak_tanpa_membuat_intent(): void
    {
        $service = $this->makeProduct('pulsa-telkomsel', 'TELKOMSEL', 'pulsa', 'Nomor Telepon,0857******,number');

        $handler = $this->handler();
        $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $retry = $handler->handle('0812345', [], $this->context());

        $this->assertStringContainsString('Format ID belum sesuai', (string) $retry['text']);
        $this->assertStringContainsString('08xxxxxxxxxx', (string) $retry['text']);
        $this->assertSame(0, BotCheckoutIntent::query()->count(), 'Intent tidak boleh dibuat dari nomor tidak sah.');
    }

    /**
     * Nomor terlalu panjang (17 digit) juga ditolak.
     */
    public function test_nomor_terlalu_panjang_ditolak(): void
    {
        $service = $this->makeProduct('pulsa-telkomsel', 'TELKOMSEL', 'pulsa', 'Nomor Telepon,0857******,number');

        $handler = $this->handler();
        $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $handler->handle('08123456789012345', [], $this->context());

        $this->assertSame(0, BotCheckoutIntent::query()->count());
    }

    /**
     * Nomor sah diteruskan APA ADANYA: digit telanjang, prefix tidak diubah.
     * Sepanjang riwayat prod, order pulsa/game tidak pernah memakai `+62`.
     */
    public function test_nomor_sah_diteruskan_tanpa_ubah_prefix(): void
    {
        $service = $this->makeProduct('pulsa-telkomsel', 'TELKOMSEL', 'pulsa', 'Nomor Telepon,0857******,number');

        $handler = $this->handler();
        $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $handler->handle('0813-9991-0772', [], $this->context());

        $intent = BotCheckoutIntent::query()->firstOrFail();
        $this->assertSame(
            '081399910772',
            $intent->payload['uid'],
            'Separator dibuang, prefix lokal dipertahankan (Digiflazz menerima format lokal).',
        );
    }

    /**
     * Nomor yang sudah internasional tetap diterima sebagai digit telanjang.
     */
    public function test_nomor_internasional_diterima_tanpa_plus(): void
    {
        $service = $this->makeProduct('pulsa-telkomsel', 'TELKOMSEL', 'pulsa', 'Nomor Telepon,0857******,number');

        $handler = $this->handler();
        $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $handler->handle('+6281234567890', [], $this->context());

        $intent = BotCheckoutIntent::query()->firstOrFail();
        $this->assertSame('6281234567890', $intent->payload['uid']);
    }

    /**
     * Label konfirmasi memakai 'Nomor', bukan 'UID'.
     */
    public function test_ringkasan_konfirmasi_memakai_label_nomor(): void
    {
        $service = $this->makeProduct('pulsa-telkomsel', 'TELKOMSEL', 'pulsa', 'Nomor Telepon,0857******,number');

        $handler = $this->handler();
        $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $confirmation = $handler->handle('081399910772', [], $this->context());

        $this->assertStringContainsString('Nomor', (string) $confirmation['text']);
        $this->assertStringNotContainsString('UID:', (string) $confirmation['text']);
    }

    /**
     * Petunjuk nomor ikut bahasa aktif (kunci lang, bukan literal di cabang).
     */
    public function test_petunjuk_nomor_ikut_bahasa_aktif(): void
    {
        $service = $this->makeProduct('pulsa-telkomsel', 'TELKOMSEL', 'pulsa', 'Nomor Telepon,0857******,number');
        $quote = app(GatewayPricingService::class)->quote([
            'service' => (string) $service->id,
            'payment_method' => 'QRIS',
        ], null);

        $formatter = app(BotMessageFormatter::class);

        app()->setLocale('id');
        $id = (string) $formatter->formatPriceQuote($quote, true, 'telegram_gateway')['text'];

        app()->setLocale('en');
        $en = (string) $formatter->formatPriceQuote($quote, true, 'telegram_gateway')['text'];

        $this->assertStringContainsString('📞 *Masukkan Nomor Telepon*', $id);
        $this->assertStringContainsString('Contoh: `08123456789`', $id);
        $this->assertStringContainsString('📞 *Enter Nomor Telepon*', $en);
        $this->assertStringContainsString('Example: `08123456789`', $en);

        foreach ([$id, $en] as $teks) {
            $this->assertStringNotContainsString('Format: `UID`', $teks);
            $this->assertStringNotContainsString('🎮', $teks);
        }
    }

    /**
     * TEST NEGATIF PALING PENTING — field GAME bertipe `number` berlabel
     * `ID`/`UID` TIDAK boleh ikut jadi kelas nomor telepon. Kalau tipe dipakai
     * sebagai penanda, seluruh katalog game ikut salah.
     */
    public function test_field_game_bertipe_number_tidak_ikut_jadi_nomor(): void
    {
        $service = $this->makeProduct('free-fire', 'FREE FIRE', 'populer', 'ID,Ketikan ID,number');

        $handler = $this->handler();
        $response = $handler->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $text = (string) $response['text'];

        $this->assertStringContainsString('Format: `UID`', $text);
        $this->assertStringContainsString('Contoh: `12345`', $text);
        $this->assertStringNotContainsString('08xxxxxxxxxx', $text);

        // UID game tetap boleh angka pendek (bukan aturan panjang nomor).
        $handler->handle('12345', [], $this->context());
        $intent = BotCheckoutIntent::query()->firstOrFail();
        $this->assertSame('12345', $intent->payload['uid']);
    }

    /**
     * Jalur zone (game yang benar-benar punya server) tidak boleh tersentuh.
     */
    public function test_jalur_zone_tidak_tersentuh(): void
    {
        $category = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'nama' => 'MOBILE LEGENDS',
            'tipe' => 'populer',
            'status' => 'active',
            'server_id' => 1,
            'require_user_id' => true,
        ]);
        CustomInput::query()->updateOrCreate(
            ['kategori_id' => (string) $category->id],
            [
                'field_1' => 'ID,Ketikan ID,number',
                'field_2' => 'Server,Ketikan Server,number',
                'field_select_title' => null,
                'field_select' => null,
            ],
        );
        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'ML Paket Uji',
            'status' => 'available',
        ]);

        $response = $this->handler()->handle('harga', [(string) $service->id, 'QRIS'], $this->context());
        $text = (string) $response['text'];

        $this->assertStringContainsString('UID <Server>', $text);
        $this->assertStringContainsString('Contoh: `12345 6789`', $text);
        $this->assertStringNotContainsString('08xxxxxxxxxx', $text);
    }
}

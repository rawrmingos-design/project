<?php

namespace Tests\Feature\Bot;

use App\Models\SettingWeb;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Banner gambar di layar Menu Utama bot (`formatCategories`).
 *
 * Banner berlaku untuk KEDUA channel sejak permintaan pemilik produk: gambar
 * yang diunggah admin dulu hanya muncul di Telegram, sehingga Menu Utama
 * WhatsApp polos tanpa gambar. Yang dijaga test ini sekarang:
 *
 * 1. **Kedua channel mengirim banner** saat field diisi dan berkasnya ada.
 * 2. **Fail-safe saat berkas hilang.** Kalau `photo_url` diisi padahal berkasnya
 *    tidak ada di disk, Telegram menolak SELURUH pesan dan menu user hilang
 *    total — bukan sekadar gambar yang tidak muncul. Karena itu URL hanya diisi
 *    kalau berkasnya benar-benar ada (`PublicUploadUrlService::existingUrl()`).
 *    Guard ini WAJIB berlaku juga di WhatsApp, bukan cuma Telegram.
 *
 * Fixture memakai BERKAS NYATA di `public/assets/bot/` dan dihapus di tearDown.
 */
class BotMenuBannerTest extends TestCase
{
    use RefreshDatabase;

    private const BANNER = 'assets/bot/test-menu-banner.webp';

    protected function setUp(): void
    {
        parent::setUp();

        $absolute = public_path(self::BANNER);
        @mkdir(dirname($absolute), 0775, true);
        file_put_contents($absolute, 'fixture');
    }

    protected function tearDown(): void
    {
        @unlink(public_path(self::BANNER));
        @rmdir(public_path('assets/bot'));

        parent::tearDown();
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Topup',
            'deskripsi_web' => 'Deskripsi test',
            'keywords' => 'topup,test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
            'public_theme' => 'default',
        ], $overrides));
    }

    private function catalogPayload(): array
    {
        return [
            'ok' => true,
            'data' => [
                ['name' => 'Top Up Games', 'slug' => 'top-up-games'],
                ['name' => 'Aplikasi Premium', 'slug' => 'app-premium'],
            ],
        ];
    }

    private function formatFor(string $source): array
    {
        return app(BotMessageFormatter::class)->formatCategories(
            $this->catalogPayload(),
            1,
            BotGatewayCapabilities::forSource($source),
        );
    }

    public function test_menu_utama_telegram_mengirim_banner_saat_diisi(): void
    {
        $this->createSettings(['bot_menu_banner' => self::BANNER]);

        $response = $this->formatFor(BotGatewayCapabilities::SOURCE_TELEGRAM);

        $this->assertArrayHasKey('photo_url', $response, 'Banner terisi harus mengirim gambar.');
        $this->assertStringContainsString('/assets/bot/', $response['photo_url']);
        $this->assertStringStartsWith(
            'http',
            $response['photo_url'],
            'Telegram butuh URL ABSOLUT, bukan path relatif.'
        );
    }

    public function test_menu_utama_telegram_tanpa_banner_tetap_perilaku_lama(): void
    {
        // NULL = perilaku LAMA persis: menu dikirim sebagai teks tanpa gambar.
        $this->createSettings(['bot_menu_banner' => null]);

        $response = $this->formatFor(BotGatewayCapabilities::SOURCE_TELEGRAM);

        $this->assertArrayNotHasKey('photo_url', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertStringContainsString('[1]. Top Up Games', $response['text']);
        // Item pindah dari tombol ke TEKS, jadi yang wajib terisi sekarang peta
        // nomornya — tanpa itu daftar tampil tapi tidak bisa dipilih.
        $this->assertSame('kategori top-up-games', $response['numeric_menu']['entries']['1']['command']);
    }

    public function test_whatsapp_ikut_kirim_banner(): void
    {
        // Kebalikan dari perilaku lama (dulu banner dikunci Telegram-only):
        // `photo_url` dibaca KETIGA adapter dan `formatCategories()` method
        // DIWI-CHANNEL, jadi banner yang sama harus ikut ke WhatsApp.
        $this->createSettings(['bot_menu_banner' => self::BANNER]);

        $response = $this->formatFor(BotGatewayCapabilities::SOURCE_WHATSAPP);

        $this->assertArrayHasKey(
            'photo_url',
            $response,
            'Banner Menu Utama harus ikut terkirim ke WhatsApp, bukan Telegram saja.'
        );
        $this->assertStringContainsString('/assets/bot/', $response['photo_url']);
        $this->assertStringStartsWith(
            'http',
            $response['photo_url'],
            'Adapter WA butuh URL ABSOLUT untuk send-image.'
        );
    }

    public function test_whatsapp_tanpa_banner_tetap_perilaku_lama(): void
    {
        // Banner tidak diisi = menu WA tetap teks + tombol seperti sebelumnya.
        $this->createSettings(['bot_menu_banner' => null]);

        $response = $this->formatFor(BotGatewayCapabilities::SOURCE_WHATSAPP);

        $this->assertArrayNotHasKey('photo_url', $response);
        $this->assertStringContainsString('🏠 *Menu Utama*', $response['text']);
        $this->assertNotEmpty($response['buttons'], 'Tombol kategori WA tidak boleh ikut hilang.');
    }

    public function test_whatsapp_banner_hilang_di_disk_tidak_dikirim(): void
    {
        // Guard keberadaan berkas WAJIB berlaku di WA juga. Kalau URL tetap
        // dikirim padahal berkasnya tidak ada, WA menerima pesan gambar kosong
        // dan Menu Utama user berisiko tidak terbaca.
        $this->createSettings(['bot_menu_banner' => 'assets/bot/tidak-ada.webp']);

        $response = $this->formatFor(BotGatewayCapabilities::SOURCE_WHATSAPP);

        $this->assertArrayNotHasKey(
            'photo_url',
            $response,
            'Berkas tidak ada = jangan kirim gambar ke WA.'
        );
        $this->assertNotEmpty($response['text'], 'Teks menu WA tetap harus terkirim.');
        $this->assertNotEmpty($response['buttons'], 'Tombol kategori WA tetap harus terkirim.');
    }

    public function test_banner_hilang_di_disk_tidak_dikirim(): void
    {
        // Kalau URL tetap diisi padahal berkasnya tidak ada, Telegram menolak
        // SELURUH pesan -> menu user hilang total. Lebih baik kirim teks saja.
        $this->createSettings(['bot_menu_banner' => 'assets/bot/tidak-ada.webp']);

        $response = $this->formatFor(BotGatewayCapabilities::SOURCE_TELEGRAM);

        $this->assertArrayNotHasKey(
            'photo_url',
            $response,
            'Berkas tidak ada = jangan kirim gambar, jangan sampai menu hilang.'
        );
        $this->assertNotEmpty($response['text'], 'Teks menu tetap harus terkirim.');
    }

    public function test_banner_tidak_mengganggu_tombol_dan_teks_menu(): void
    {
        $this->createSettings(['bot_menu_banner' => self::BANNER]);

        $withBanner = $this->formatFor(BotGatewayCapabilities::SOURCE_TELEGRAM);

        SettingWeb::query()->where('id', 1)->update(['bot_menu_banner' => null]);
        $withoutBanner = $this->formatFor(BotGatewayCapabilities::SOURCE_TELEGRAM);

        // Menambah gambar tidak boleh mengubah teks maupun susunan tombol.
        $this->assertSame($withoutBanner['text'], $withBanner['text']);
        $this->assertSame($withoutBanner['buttons'], $withBanner['buttons']);
    }
}

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
 * Dua hal yang dijaga test ini:
 *
 * 1. **Gate Telegram.** `photo_url` dibaca KETIGA adapter (Telegram, Fonnte,
 *    OpenWa). `formatCategories()` adalah method DWI-CHANNEL, jadi tanpa gate
 *    di formatter, banner otomatis ikut terkirim ke WhatsApp. Gate-nya harus di
 *    sini, bukan diasumsikan dari adapter.
 *
 * 2. **Fail-safe saat berkas hilang.** Kalau `photo_url` diisi padahal berkasnya
 *    tidak ada di disk, Telegram menolak SELURUH pesan dan menu user hilang
 *    total — bukan sekadar gambar yang tidak muncul. Karena itu URL hanya diisi
 *    kalau berkasnya benar-benar ada (`PublicUploadUrlService::existingUrl()`).
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

    public function test_whatsapp_tidak_ikut_kirim_banner(): void
    {
        // `photo_url` dibaca ketiga adapter. Tanpa gate di formatter, banner
        // Telegram bocor ke WhatsApp.
        $this->createSettings(['bot_menu_banner' => self::BANNER]);

        $response = $this->formatFor(BotGatewayCapabilities::SOURCE_WHATSAPP);

        $this->assertArrayNotHasKey(
            'photo_url',
            $response,
            'Banner Telegram tidak boleh ikut terkirim ke WhatsApp.'
        );
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

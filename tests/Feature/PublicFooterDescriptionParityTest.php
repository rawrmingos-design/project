<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Services\PublicSiteConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Prop `siteConfig.footerDescriptionHtml` dipakai dua renderer: Blade
 * (`resources/views/footer.blade.php`) dan React (`Footer.jsx` lewat
 * `FooterSeoDescription`). Sebelumnya service hanya mengirim `deskripsi_web`,
 * sehingga jalur React kehilangan deskripsi footer khusus beranda.
 *
 * Spec ini mengunci kontrak bersama: beranda memakai
 * `deskripsi_footer_beranda` saat `aktif_footer_beranda` menyala dan isinya
 * tidak kosong; halaman lain (dan saat toggle mati / isi kosong) memakai
 * `deskripsi_web`.
 */
class PublicFooterDescriptionParityTest extends TestCase
{
    use RefreshDatabase;

    private function setSettings(array $overrides = []): void
    {
        SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Egy Market',
            'deskripsi_web' => '<p>Deskripsi website umum.</p>',
            'deskripsi_footer_beranda' => '<p>Deskripsi footer khusus beranda.</p>',
            'aktif_footer_beranda' => true,
            'keywords' => 'topup,game',
            'logo_header' => 'assets/logo/header.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#f97316',
            'warna4' => '#444444',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
            'public_theme' => 'istanatopup',
            'home_popup_enabled' => true,
            'live_sales_enabled' => true,
            'captcha_enabled' => true,
            'captcha_bypass' => false,
        ], $overrides));
    }

    private function footerHtmlAt(string $path): string
    {
        // Service membaca `request()`; daftarkan probe route supaya request
        // yang sedang aktif benar-benar ber-path seperti halaman asli.
        Route::get($path, fn () => 'ok');

        $this->get($path);

        return app(PublicSiteConfigService::class)->sharedProps()['siteConfig']['footerDescriptionHtml'];
    }

    public function test_beranda_pakai_deskripsi_footer_beranda_saat_toggle_menyala(): void
    {
        $this->setSettings();

        $this->assertStringContainsString('khusus beranda', $this->footerHtmlAt('/id'));
    }

    public function test_halaman_lain_pakai_deskripsi_web(): void
    {
        $this->setSettings();

        $html = $this->footerHtmlAt('/id/artikel');

        $this->assertStringContainsString('website umum', $html);
        $this->assertStringNotContainsString('khusus beranda', $html);
    }

    public function test_toggle_mati_jatuh_ke_deskripsi_web_walau_beranda(): void
    {
        $this->setSettings(['aktif_footer_beranda' => false]);

        $this->assertStringContainsString('website umum', $this->footerHtmlAt('/id'));
    }

    public function test_deskripsi_beranda_kosong_jatuh_ke_deskripsi_web(): void
    {
        $this->setSettings(['deskripsi_footer_beranda' => '   ']);

        $this->assertStringContainsString('website umum', $this->footerHtmlAt('/id'));
    }

    public function test_deskripsi_footer_tetap_disinarisasi(): void
    {
        $this->setSettings([
            'deskripsi_footer_beranda' => '<p>Aman</p><p onclick="alert(1)">jahat</p><script>alert("xss")</script>',
        ]);

        $html = $this->footerHtmlAt('/id');

        $this->assertStringContainsString('<p>Aman</p>', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('<script', $html);
    }
}

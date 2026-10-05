<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Models\User;
use App\Support\DocsBranding;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Env;
use Tests\TestCase;

/**
 * Logo & favicon dokumentasi harus mengikuti Settings Web (`logo_header` / `logo_favicon`),
 * bukan logo bawaan hasil build.
 *
 * Kenapa test ini ada: hasil build Docusaurus bersifat statis — logo navbar dan faviconnya
 * dibakar saat `npm run build`. Kalau tidak ada yang menulis ulang token-nya saat disajikan,
 * mengganti logo di panel admin tidak berpengaruh apa pun pada dokumentasi.
 */
class DocsBrandingTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Cache::flush();
    }

    public function createApplication()
    {
        $this->savedEnv = [
            'APP_URL' => getenv('APP_URL'),
            'FILAMENT_ADMIN_DOMAIN' => getenv('FILAMENT_ADMIN_DOMAIN'),
            'DOCS_DOMAIN' => getenv('DOCS_DOMAIN'),
        ];

        $this->setEnvironment('APP_URL', 'http://public.istanatopup.test');
        $this->setEnvironment('FILAMENT_ADMIN_DOMAIN', 'admin.istanatopup.test');
        $this->setEnvironment('DOCS_DOMAIN', 'docs.istanatopup.test');
        Env::enablePutenv();

        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                $this->setEnvironment($key, $value);
            }
        }

        Env::enablePutenv();
    }

    private function seedSettings(array $overrides = []): void
    {
        SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'ISTANATOPUP',
            'deskripsi_web' => 'Top up game',
            'keywords' => 'top up',
            'logo_header' => 'assets/logo/header-custom.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon-custom.png',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/testweb',
            'url_tiktok' => 'https://tiktok.com/@testweb',
            'url_youtube' => 'https://youtube.com/@testweb',
            'url_fb' => 'https://facebook.com/testweb',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'TST',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
            'public_theme' => 'default',
        ], $overrides));
    }

    public function test_both_branding_tokens_are_replaced_in_the_real_build(): void
    {
        if (! is_file(base_path('resources/docs-api/index.html'))) {
            $this->markTestSkipped('Build docs tidak ada.');
        }

        $this->seedSettings();

        $html = (string) file_get_contents(base_path('resources/docs-api/index.html'));

        // PENJAGA KONTRAK: token di DocsBranding harus benar-benar ada di build. Tanpa ini,
        // kalau Docusaurus mengganti nama token, semua assertion "tidak mengandung" di bawah
        // lolos palsu (tokennya memang sudah tidak ada) sementara logo di layar kembali ke
        // bawaan tanpa ada test yang gagal.
        $this->assertStringContainsString(
            '/img/logo.svg',
            $html,
            'Build docs tidak lagi memuat token /img/logo.svg — nama token di DocsBranding harus disesuaikan.'
        );
        $this->assertStringContainsString(
            '/img/favicon.ico',
            $html,
            'Build docs tidak lagi memuat token /img/favicon.ico — nama token di DocsBranding harus disesuaikan.'
        );

        $rewritten = app(DocsBranding::class)->rewriteHtml($html);

        // Favicon bawaan build harus hilang seluruhnya, diganti favicon dari Settings Web.
        $this->assertStringNotContainsString('/img/favicon.ico', $rewritten);
        $this->assertStringContainsString('public.istanatopup.test/assets/logo/favicon-custom.png', $rewritten);

        // Logo bawaan juga hilang, diganti logo header dari Settings Web.
        $this->assertStringNotContainsString('/img/logo.svg', $rewritten);
        $this->assertStringContainsString('public.istanatopup.test/assets/logo/header-custom.webp', $rewritten);
    }

    public function test_logo_value_comes_from_settings_not_from_code(): void
    {
        // Nilai yang tidak ada di kode mana pun: kalau ada yang di-hardcode, test ini gagal.
        $this->seedSettings(['logo_header' => 'assets/logo/merek-baru-2026.webp']);

        $rewritten = app(DocsBranding::class)->rewriteHtml('<img src=/img/logo.svg alt=Logo>');

        $this->assertStringContainsString('merek-baru-2026.webp', $rewritten);
        $this->assertStringNotContainsString('/img/logo.svg', $rewritten);
    }

    public function test_absolute_url_in_settings_is_kept_as_is(): void
    {
        $this->seedSettings(['logo_header' => 'https://cdn.contoh.test/merek.png']);

        $rewritten = app(DocsBranding::class)->rewriteHtml('<img src=/img/logo.svg>');

        $this->assertStringContainsString('https://cdn.contoh.test/merek.png', $rewritten);
        $this->assertStringNotContainsString('assets/logo/header-custom.webp', $rewritten);
    }

    public function test_unquoted_and_quoted_attributes_are_both_handled(): void
    {
        $this->seedSettings();

        $html = '<link rel=icon href=/img/favicon.ico /><img src="/img/logo.svg" alt=Logo>';

        $rewritten = app(DocsBranding::class)->rewriteHtml($html);

        $this->assertStringNotContainsString('/img/favicon.ico', $rewritten);
        $this->assertStringNotContainsString('/img/logo.svg', $rewritten);
        $this->assertStringContainsString('favicon-custom.png', $rewritten);
        $this->assertStringContainsString('header-custom.webp', $rewritten);
    }

    public function test_script_payload_is_not_touched(): void
    {
        $this->seedSettings();

        // Token di dalam <script> (payload hidrasi React) TIDAK boleh diganti: React akan
        // mengembalikannya saat hidrasi. Karena itu penggantian dibatasi ke atribut src/href.
        $html = '<script>window.__DATA__={"logo":"/img/logo.svg"}</script><img src="/img/logo.svg">';

        $rewritten = app(DocsBranding::class)->rewriteHtml($html);

        $this->assertStringContainsString('{"logo":"/img/logo.svg"}', $rewritten);
        $this->assertStringNotContainsString('src="/img/logo.svg"', $rewritten);
    }

    public function test_falls_back_to_default_logo_when_settings_row_is_missing(): void
    {
        // Tanpa baris setting_webs: harus fallback, bukan URL kosong / logo bawaan yang bocor.
        $rewritten = app(DocsBranding::class)->rewriteHtml('<img src=/img/logo.svg>');

        $this->assertStringNotContainsString('/img/logo.svg', $rewritten);
        $this->assertStringContainsString('favicon.webp', $rewritten);
    }

    public function test_docs_page_served_to_logged_in_user_carries_settings_logo(): void
    {
        if (! is_file(base_path('resources/docs-api/index.html'))) {
            $this->markTestSkipped('Build docs tidak ada.');
        }

        $this->seedSettings();

        $user = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($user)->get('http://docs.istanatopup.test/');

        $response->assertOk();

        $body = (string) $response->getContent();

        $this->assertStringNotContainsString('/img/logo.svg', $body, 'Logo docs masih logo bawaan build.');
        $this->assertStringContainsString('header-custom.webp', $body, 'Logo dari Settings Web tidak dipakai.');
    }

    private function setEnvironment(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

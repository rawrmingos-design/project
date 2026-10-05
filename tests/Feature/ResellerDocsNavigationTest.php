<?php

namespace Tests\Feature;

use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Tests\TestCase;

/**
 * Navigasi panel reseller ke docs (Fase 4c).
 *
 * Keputusan user: `ApiDocs.jsx` + `Reseller\DocsController` DIHAPUS, tapi navigasinya
 * dipertahankan — diarahkan ke domain docs. Test ini mengunci tiga hal:
 *
 *   1. route lama `/id/reseller/docs` tetap ada dan redirect 301 ke domain docs
 *      (supaya bookmark/link lama tidak mati);
 *   2. `docsUrl` tersedia untuk panel hanya saat `DOCS_DOMAIN` diisi — kalau kosong,
 *      entri nav harus disembunyikan, bukan menampilkan link mati;
 *   3. controller & halaman lama benar-benar sudah tidak ada.
 */
class ResellerDocsNavigationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    public function createApplication()
    {
        $this->savedEnv = [
            'APP_URL' => getenv('APP_URL'),
            'DOCS_DOMAIN' => getenv('DOCS_DOMAIN'),
            'FILAMENT_ADMIN_DOMAIN' => getenv('FILAMENT_ADMIN_DOMAIN'),
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_reseller_docs_route_redirects_to_the_docs_domain(): void
    {
        $user = User::factory()->create(['role' => 'Reseller']);

        // Route ini di balik `reseller.only`: tanpa integrasi reseller, middleware
        // me-redirect ke dashboard (302) dan redirect 301 kita tidak pernah teruji.
        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-DOCS-NAV-01',
            'mode'             => 'live',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($user)->get('http://public.istanatopup.test/id/reseller/docs');

        $response->assertStatus(301);
        $this->assertSame(
            'https://docs.istanatopup.test',
            $response->headers->get('Location'),
            'Link lama /id/reseller/docs harus tetap mengantar ke domain docs.'
        );
    }

    public function test_docs_url_is_exposed_to_the_panel_only_when_configured(): void
    {
        $service = app(\App\Services\PublicSiteConfigService::class);

        $props = $service->sharedProps();

        $this->assertArrayHasKey(
            'docsUrl',
            $props['siteConfig'],
            'Panel reseller butuh `siteConfig.docsUrl` untuk memutuskan menampilkan entri nav.'
        );

        $this->assertSame('https://docs.istanatopup.test', $props['siteConfig']['docsUrl']);
    }

    public function test_docs_url_is_null_when_docs_domain_is_not_configured(): void
    {
        // Simulasi `DOCS_DOMAIN` kosong: entri nav harus disembunyikan, bukan link mati.
        config(['app.docs_domain' => '']);

        $this->assertNull(
            app(\App\Services\PublicSiteConfigService::class)->docsUrl(),
            'Tanpa DOCS_DOMAIN, docsUrl harus null supaya link mati tidak pernah dirender.'
        );
    }

    public function test_legacy_reseller_docs_surface_is_fully_removed(): void
    {
        $this->assertFileDoesNotExist(
            resource_path('js/public/Pages/Reseller/ApiDocs.jsx'),
            'ApiDocs.jsx harus sudah dihapus (digantikan docs Docusaurus).'
        );

        $this->assertFalse(
            class_exists(\App\Http\Controllers\Public\Reseller\DocsController::class),
            'Reseller\\DocsController harus sudah dihapus — route-nya kini closure redirect.'
        );

        $this->assertFileDoesNotExist(
            resource_path('js/public/Pages/Docs/Index.jsx'),
            'Portal docs lama (Docs/Index.jsx) harus sudah dihapus.'
        );
    }

    private function setEnvironment(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

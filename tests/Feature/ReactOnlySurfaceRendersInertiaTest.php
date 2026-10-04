<?php

namespace Tests\Feature;

use App\Http\Controllers\Controller;
use App\Models\SettingWeb;
use App\Support\PublicThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Tahap 3 — keputusan produk: halaman docs & reseller SENGAJA dibiarkan React.
 *
 * Test ini mengunci keputusan itu supaya tidak terkikis diam-diam:
 *
 *   1. permukaan React-only harus render Inertia pada KEDUA tema (default
 *      maupun modern) — jadi developer tidak bisa "memperbaikinya" dengan
 *      memaksa mereka ikut tema lalu 500 karena view Blade-nya tidak ada;
 *   2. permukaan React-only TIDAK boleh kena redirect 301 middleware
 *      `bangjeff.legacy.redirect`, yang akan membuat halaman itu tak
 *      terjangkau saat tema modern aktif;
 *   3. kalau kelak view Blade-nya dibuat, test ini MERAH dan memaksa entri
 *      dihapus dari INERTIA_ONLY_CONTROLLERS — keputusan tidak boleh basi.
 */
class ReactOnlySurfaceRendersInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
    }

    private function seedTheme(string $theme): void
    {
        SettingWeb::query()->delete();
        SettingWeb::create([
            'id' => 1,
            'judul_web' => 'Kontrak Topup',
            'deskripsi_web' => 'Storefront untuk test kontrak tema',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/kontrak',
            'url_tiktok' => 'https://tiktok.com/@kontrak',
            'url_youtube' => 'https://youtube.com/@kontrak',
            'url_fb' => 'https://facebook.com/kontrak',
            'topupindo_api' => 'kontrak-topupindo-key',
            'paydisini_apikey' => 'kontrak-paydisini-key',
            'order_prefik' => 'KTR',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
            'public_theme' => $theme,
        ]);
        Cache::flush();
    }

    /** @return array{status:int, location:?string, views:list<string>} */
    private function probe(string $path): array
    {
        $views = [];
        View::composer('*', function ($view) use (&$views): void {
            $views[] = $view->getName();
        });

        $response = $this->get($path);

        return [
            'status' => $response->getStatusCode(),
            'location' => $response->headers->get('Location'),
            'views' => array_values(array_unique($views)),
        ];
    }

    /**
     * Halaman publik React-only yang wajib tetap React di tema apa pun.
     *
     * @return list<string>
     */
    private function reactOnlyPublicPaths(): array
    {
        return [
            '/id/reseller',
            '/id/reseller/registry',
        ];
    }

    public function test_react_only_public_pages_render_inertia_under_every_theme(): void
    {
        $problems = [];

        foreach (PublicThemeRegistry::options() as $theme) {
            $this->seedTheme($theme);

            foreach ($this->reactOnlyPublicPaths() as $path) {
                $result = $this->probe($path);

                if ($result['status'] !== 200) {
                    $problems[] = "theme={$theme} {$path}: status {$result['status']} (harus 200)";
                    continue;
                }

                if (! in_array('app-inertia', $result['views'], true)) {
                    $problems[] = "theme={$theme} {$path}: tidak render Inertia (views=" . json_encode($result['views']) . ')';
                }

                $blade = array_values(array_filter(
                    $result['views'],
                    fn (string $view): bool => str_starts_with($view, 'template.')
                ));

                if ($blade !== []) {
                    $problems[] = "theme={$theme} {$path}: ikut render Blade (" . implode(', ', $blade) . ')';
                }
            }
        }

        $this->assertSame([], $problems, "Permukaan React-only tidak konsisten:\n  - " . implode("\n  - ", $problems));
    }

    public function test_react_only_public_pages_are_never_redirected_away(): void
    {
        $this->seedTheme(PublicThemeRegistry::BANGJEFF);

        foreach ($this->reactOnlyPublicPaths() as $path) {
            $result = $this->probe($path);

            $this->assertSame(
                200,
                $result['status'],
                "{$path} kena redirect (status {$result['status']}, Location " . ($result['location'] ?? '-')
                    . ') padahal sudah diputuskan tetap React. Cek middleware bangjeff.legacy.redirect.'
            );

            $this->assertNull(
                $result['location'],
                "{$path} mengembalikan Location " . ($result['location'] ?? '-') . ' padahal harus render langsung.'
            );
        }
    }

    /**
     * Sumber PHP tanpa komentar — supaya `// return view(...)` atau docblock
     * yang menyebut `rendersLegacyBlade` tidak dianggap bukti nyata.
     */
    private function codeWithoutComments(string $source): string
    {
        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }

                $code .= $token[1];
                continue;
            }

            $code .= $token;
        }

        return $code;
    }

    public function test_every_declared_react_only_controller_has_no_blade_view_branch(): void
    {
        $hasBladeBranch = [];

        foreach (PublicThemeRegistry::INERTIA_ONLY_CONTROLLERS as $class) {
            $reflection = new \ReflectionClass($class);
            $file = $reflection->getFileName();

            if ($file === false) {
                continue;
            }

            $code = $this->codeWithoutComments(File::get($file));

            // Kalau controller ini mulai punya view Blade, keputusan sudah berubah
            // dan entri ini harus dilepas dari daftar React-only.
            if (preg_match('/return\s+view\s*\(/', $code) === 1) {
                $hasBladeBranch[] = $class;
            }
        }

        $this->assertSame(
            [],
            $hasBladeBranch,
            "Controller React-only ini sudah punya view Blade, jadi keputusan berubah — hapus dari "
                . "INERTIA_ONLY_CONTROLLERS:\n  - " . implode("\n  - ", $hasBladeBranch)
        );
    }

    public function test_docs_route_lives_on_its_own_domain_and_is_not_theme_switched(): void
    {
        // Docs sengaja React-only: tidak ada view Blade-nya, dan route-nya
        // terpisah di domain docs (gated login), jadi tidak boleh ikut gerbang tema.
        $this->assertTrue(
            PublicThemeRegistry::isInertiaOnlyController(\App\Http\Controllers\Public\DocsController::class),
            'DocsController harus terdaftar sebagai React-only.'
        );

        $source = File::get((new \ReflectionClass(\App\Http\Controllers\Public\DocsController::class))->getFileName());

        $this->assertStringNotContainsString(
            'rendersLegacyBlade',
            $source,
            'DocsController tidak boleh ikut gerbang tema — belum ada view Blade-nya.'
        );

        // Docs shell adalah Inertia murni, bukan halaman Blade yang menyisipkan React.
        $this->assertStringContainsString(
            '@inertia',
            File::get(resource_path('views/docs.blade.php')),
            'views/docs.blade.php harus berupa shell Inertia murni.'
        );
    }

    public function test_contract_guards_against_a_react_only_controller_gaining_a_theme_branch_silently(): void
    {
        // Sanity: helper kontrak dan perilaku runtime harus sepakat. Controller
        // yang terdaftar React-only tidak boleh memanggil gerbang tema.
        $mismatch = [];

        foreach (PublicThemeRegistry::INERTIA_ONLY_CONTROLLERS as $class) {
            $source = File::get((new \ReflectionClass($class))->getFileName());

            if (str_contains($source, 'rendersLegacyBlade') || str_contains($source, 'activeForEnvironment')) {
                $mismatch[] = $class;
            }
        }

        $this->assertSame([], $mismatch, "Daftar React-only dan perilaku tidak sinkron:\n  - " . implode("\n  - ", $mismatch));
    }
}

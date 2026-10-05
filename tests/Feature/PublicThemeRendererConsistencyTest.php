<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Berita;
use App\Models\SettingWeb;
use App\Support\PublicThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Invariant tema: renderer SETIAP halaman publik harus mengikuti
 * `setting_webs.public_theme`.
 *
 *   default              -> legacy Blade (resources/views/template/...)
 *   bangjeff/istanatopup -> Inertia (React)
 *
 * Kenapa test ini ada: `LoginController` dan `RegisterController` hanya
 * mengenali theme `istanatopup` (`=== 'istanatopup'`). Pada instalasi dengan
 * `public_theme = bangjeff`, halaman login & daftar jadi BLADE sementara
 * seluruh halaman publik lain jadi INERTIA — satu halaman keluar dari tema.
 */
class PublicThemeRendererConsistencyTest extends TestCase
{
    use RefreshDatabase;

    /** Halaman publik yang harus IKUT theme. */
    private const THEMED_PATHS = [
        '/id/sign-in',
        '/id/sign-up',
        '/id/price-list',
        '/id/reviews',
        '/id/forgot-password',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Cache::flush();
    }

    private function seedSettings(string $theme): void
    {
        SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Demo storefront',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
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
            'public_theme' => $theme,
        ]);

        Berita::query()->create([
            'tipe' => 'banner',
            'judul' => 'Promo Banner',
            'deskripsi' => 'Banner test',
            'images' => 'assets/banner/test.webp',
        ]);

        Artikel::query()->create([
            'title' => 'Artikel Test',
            'slug' => 'artikel-test',
            'thumbnail' => 'assets/articles/test.webp',
            'status' => 'active',
        ]);
    }

    private function setTheme(string $theme): void
    {
        SettingWeb::query()->whereKey(1)->update(['public_theme' => $theme]);
        Cache::flush();
    }

    /**
     * Render path ini dan laporkan nama view yang benar-benar dirender.
     *
     * `app-inertia` = root view Inertia (React).
     * `template.*`  = legacy Blade.
     *
     * @return array{status:int, views:array<int,string>}
     */
    private function renderAndRecordViews(string $path): array
    {
        $views = [];

        View::composer('*', function ($view) use (&$views): void {
            $views[] = $view->getName();
        });

        $response = $this->get($path);

        return [
            'status' => $response->getStatusCode(),
            'views' => array_values(array_unique($views)),
        ];
    }

    // -----------------------------------------------------------------
    // Gate registry (kontrak murni, tanpa HTTP)
    // -----------------------------------------------------------------

    public function test_registry_only_renders_legacy_blade_for_the_default_theme(): void
    {
        $this->assertTrue(PublicThemeRegistry::rendersLegacyBlade(PublicThemeRegistry::DEFAULT));
        $this->assertFalse(PublicThemeRegistry::rendersLegacyBlade(PublicThemeRegistry::BANGJEFF));
        $this->assertFalse(PublicThemeRegistry::rendersLegacyBlade(PublicThemeRegistry::ISTANATOPUP));
    }

    public function test_registry_falls_back_to_legacy_blade_for_empty_or_unknown_theme(): void
    {
        // Theme kosong/tak dikenal → dinormalkan ke default → legacy Blade.
        // Ini jalur aman: instalasi lama tanpa public_theme tetap Blade.
        $this->assertTrue(PublicThemeRegistry::rendersLegacyBlade(null));
        $this->assertTrue(PublicThemeRegistry::rendersLegacyBlade(''));
        $this->assertTrue(PublicThemeRegistry::rendersLegacyBlade('tema-yang-tidak-ada'));
    }

    public function test_registry_treats_modern_alias_as_a_modern_theme(): void
    {
        // Alias 'modern' menormalkan ke bangjeff → Inertia, bukan Blade.
        $this->assertFalse(PublicThemeRegistry::rendersLegacyBlade('modern'));
    }

    // -----------------------------------------------------------------
    // Halaman login — tiga theme
    // -----------------------------------------------------------------

    public function test_sign_in_renders_blade_when_theme_is_default(): void
    {
        $this->seedSettings(PublicThemeRegistry::DEFAULT);

        $this->get('/id/sign-in')
            ->assertOk()
            ->assertViewIs('template.login');
    }

    public function test_sign_in_renders_inertia_when_theme_is_bangjeff(): void
    {
        $this->seedSettings(PublicThemeRegistry::BANGJEFF);

        $this->get('/id/sign-in')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/Auth/Login'));
    }

    public function test_sign_in_renders_inertia_when_theme_is_istanatopup(): void
    {
        $this->seedSettings(PublicThemeRegistry::ISTANATOPUP);

        $this->get('/id/sign-in')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/Auth/Login'));
    }

    // -----------------------------------------------------------------
    // Halaman daftar — tiga theme
    // -----------------------------------------------------------------

    public function test_sign_up_renders_blade_when_theme_is_default(): void
    {
        $this->seedSettings(PublicThemeRegistry::DEFAULT);

        $this->get('/id/sign-up')
            ->assertOk()
            ->assertViewIs('template.register');
    }

    public function test_sign_up_renders_inertia_when_theme_is_bangjeff(): void
    {
        $this->seedSettings(PublicThemeRegistry::BANGJEFF);

        $this->get('/id/sign-up')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/Auth/Register'));
    }

    public function test_sign_up_renders_inertia_when_theme_is_istanatopup(): void
    {
        $this->seedSettings(PublicThemeRegistry::ISTANATOPUP);

        $this->get('/id/sign-up')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/Auth/Register'));
    }

    // -----------------------------------------------------------------
    // Sapuan: theme modern tidak boleh menyisakan halaman Blade
    // -----------------------------------------------------------------

    public function test_modern_themes_never_render_a_legacy_blade_page(): void
    {
        $this->seedSettings(PublicThemeRegistry::BANGJEFF);

        foreach ([PublicThemeRegistry::BANGJEFF, PublicThemeRegistry::ISTANATOPUP] as $theme) {
            $this->setTheme($theme);

            foreach (self::THEMED_PATHS as $path) {
                $result = $this->renderAndRecordViews($path);

                $this->assertSame(200, $result['status'], "Theme {$theme}: {$path} tidak 200.");

                $legacyViews = array_values(array_filter(
                    $result['views'],
                    fn (string $view): bool => str_starts_with($view, 'template.')
                ));

                $this->assertSame(
                    [],
                    $legacyViews,
                    "Theme {$theme}: {$path} masih merender view legacy Blade: " . implode(', ', $legacyViews)
                );

                $this->assertContains(
                    'app-inertia',
                    $result['views'],
                    "Theme {$theme}: {$path} seharusnya merender root view Inertia 'app-inertia', dapat: "
                        . implode(', ', $result['views'])
                );
            }
        }
    }

    public function test_default_theme_never_renders_a_legacy_blade_page(): void
    {
        $this->seedSettings(PublicThemeRegistry::DEFAULT);

        foreach (self::THEMED_PATHS as $path) {
            $result = $this->renderAndRecordViews($path);

            $this->assertSame(200, $result['status'], "Theme default: {$path} tidak 200.");

            $this->assertNotContains(
                'app-inertia',
                $result['views'],
                "Theme default: {$path} merender root view Inertia, padahal harus legacy Blade."
            );

            $legacyViews = array_values(array_filter(
                $result['views'],
                fn (string $view): bool => str_starts_with($view, 'template.')
            ));

            $this->assertNotSame(
                [],
                $legacyViews,
                "Theme default: {$path} seharusnya merender view legacy Blade (template.*), dapat: "
                    . implode(', ', $result['views'])
            );
        }
    }
}

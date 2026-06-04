<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Berita;
use App\Models\SettingWeb;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThemeSwitchAuthRouteHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    public function createApplication()
    {
        // Save originals before overriding (reads from OS-level putenv, not Laravel's static repo)
        $this->savedEnv = [
            'APP_URL'               => getenv('APP_URL'),
            'FILAMENT_ADMIN_DOMAIN' => getenv('FILAMENT_ADMIN_DOMAIN'),
        ];

        putenv('APP_URL=http://public.istanatopup.test');
        putenv('FILAMENT_ADMIN_DOMAIN=admin.istanatopup.test');
        $_ENV['APP_URL']               = 'http://public.istanatopup.test';
        $_ENV['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';
        $_SERVER['APP_URL']               = 'http://public.istanatopup.test';
        $_SERVER['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';

        // Reset the STATIC Env::$repository so the next getRepository() call creates a fresh one
        // that reads from our putenv values above — not the cached values from a previous test class
        Env::enablePutenv();

        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure no stale setting_webs rows leak from prior test classes
        // (createApplication() override can break RefreshDatabase transaction boundaries)
        DB::table('setting_webs')->delete();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore original env values so subsequent test classes see the real .env values
        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
            }
        }

        // Reset static repository so the next test class bootstraps fresh from the restored putenv
        Env::enablePutenv();
    }

    public function test_sign_in_and_sign_up_are_accessible_on_public_host_when_theme_is_bangjeff(): void
    {
        $this->withoutVite();
        $this->seedBasePublicData('bangjeff');

        $this->get('http://public.istanatopup.test/id/sign-in')->assertOk();
        $this->get('http://public.istanatopup.test/id/sign-up')->assertOk();
    }

    public function test_admin_host_redirects_public_id_routes_to_login_to_avoid_loop(): void
    {
        $this->withoutVite();
        $this->seedBasePublicData('bangjeff');

        $this->get('http://admin.istanatopup.test/id')
            ->assertStatus(302)
            ->assertRedirect('/login');

        $this->get('http://admin.istanatopup.test/id/sign-in')
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    private function seedBasePublicData(string $theme): void
    {
        SettingWeb::create([
            'id' => 1,
            'judul_web' => 'PlayNusa Topup',
            'deskripsi_web' => 'Demo storefront',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/playnusa',
            'url_tiktok' => 'https://tiktok.com/@playnusa',
            'url_youtube' => 'https://youtube.com/@playnusa',
            'url_fb' => 'https://facebook.com/playnusa',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'DMO',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
            'public_theme' => $theme,
        ]);

        Berita::create([
            'tipe' => 'banner',
            'judul' => 'Promo Banner',
            'deskripsi' => 'Banner test',
            'images' => 'assets/banner/test.webp',
        ]);

        Artikel::create([
            'title' => 'Artikel Test',
            'slug' => 'artikel-test',
            'thumbnail' => 'assets/articles/test.webp',
            'status' => 'active',
        ]);
    }
}

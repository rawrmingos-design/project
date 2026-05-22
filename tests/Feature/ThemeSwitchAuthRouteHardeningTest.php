<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Berita;
use App\Models\SettingWeb;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeSwitchAuthRouteHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        putenv('APP_URL=http://public.istanatopup.test');
        putenv('FILAMENT_ADMIN_DOMAIN=admin.istanatopup.test');
        $_ENV['APP_URL'] = 'http://public.istanatopup.test';
        $_ENV['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';
        $_SERVER['APP_URL'] = 'http://public.istanatopup.test';
        $_SERVER['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';

        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
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

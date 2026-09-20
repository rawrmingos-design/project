<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Models\User;
use App\Support\PublicThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Google sign-up completion page must render in both themes:
 * Blade legacy for `public_theme = default`, Inertia for the modern themes.
 */
class GoogleAuthCompleteThemeTest extends TestCase
{
    use RefreshDatabase;

    private const GOOGLE_CLIENT_ID = 'test-google-client-id.apps.googleusercontent.com';

    private function createSettings(string $theme): void
    {
        SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Test store description',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/620000000000',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'test-topupindo-key',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'test-paydisini-key',
            'order_prefik' => 'INV',
            'google_client_id' => self::GOOGLE_CLIENT_ID,
            'public_theme' => $theme,
        ]);
    }

    private function startGoogleSignup(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response([
                'aud' => self::GOOGLE_CLIENT_ID,
                'iss' => 'https://accounts.google.com',
                'sub' => '117584989561986429719',
                'email' => 'alvinfrista@gmail.com',
                'email_verified' => 'true',
                'name' => 'Alvin Frista',
                'picture' => 'https://lh3.googleusercontent.com/a/avatar=s96-c',
            ], 200),
        ]);

        $this->post('/id/auth/google', ['credential' => 'fake-token']);
    }

    public function test_default_theme_renders_the_blade_completion_page(): void
    {
        $this->createSettings(PublicThemeRegistry::DEFAULT);
        $this->startGoogleSignup();

        $response = $this->get(route('auth.google.complete'));

        $response->assertOk();
        $response->assertSee('Satu langkah lagi', false);
        $response->assertSee('googleCompleteForm', false);
        $response->assertSee('name="no_wa"', false);
    }

    public function test_istanatopup_theme_renders_the_inertia_completion_page(): void
    {
        $this->createSettings(PublicThemeRegistry::ISTANATOPUP);
        $this->startGoogleSignup();

        $response = $this->get(route('auth.google.complete'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Public/Auth/CompleteGoogleSignup')
                ->where('email', 'alvinfrista@gmail.com')
        );
    }

    public function test_bangjeff_theme_renders_the_inertia_completion_page(): void
    {
        $this->createSettings(PublicThemeRegistry::BANGJEFF);
        $this->startGoogleSignup();

        $this->get(route('auth.google.complete'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/Auth/CompleteGoogleSignup'));
    }

    public function test_blade_completion_form_activates_the_account(): void
    {
        $this->createSettings(PublicThemeRegistry::DEFAULT);
        $this->startGoogleSignup();

        $this->post(route('auth.google.complete.post'), ['no_wa' => '081298765432'])
            ->assertRedirect('/id/dashboard');

        $user = User::query()->where('email', 'alvinfrista@gmail.com')->firstOrFail();
        $this->assertSame('6281298765432', $user->no_wa);
        $this->assertAuthenticatedAs($user);
    }
}

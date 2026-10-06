<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman reset kata sandi (`password-reset.blade.php`) dulu HTML telanjang:
 * tanpa navbar/footer, tanpa CSS, hanya <h1> + form. Kontrak keamanan dan
 * fungsionalnya lolos test, tapi tampilannya "berantakan" di produksi.
 *
 * Test ini mengunci presentasi baru: halaman wajib mewarisi `template.template`
 * (sehingga dapat chrome + stylesheet legacy) sambil TETAP mempertahankan
 * meta referrer anti-bocor-token dan atribut form (minlength 12).
 */
class PasswordResetPagePresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SettingWeb::create([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test',
            'keywords' => 'test',
            'logo_header' => 'logo.webp',
            'logo_footer' => 'footer.webp',
            'logo_favicon' => 'favicon.webp',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'test',
            'paydisini_apikey' => 'test',
            'order_prefik' => 'TST',
            'warna1' => '#000000',
            'warna2' => '#111111',
            'warna3' => '#222222',
            'warna4' => '#333333',
        ]);
    }

    public function test_reset_form_renders_with_site_chrome_and_styles_not_bare_html(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'test-token',
            'email' => 'recoverable@example.com',
        ]));

        $response->assertOk()
            ->assertViewIs('password-reset')
            // Presentasi: kartu auth bertema, bukan HTML telanjang.
            ->assertSee('auth-reset-page', false)
            ->assertSee('auth-reset-input', false)
            ->assertSee('auth-reset-submit', false)
            // Kontrak fungsional tetap utuh.
            ->assertSee('name="token" value="test-token"', false)
            ->assertSee('minlength="12"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('Simpan Kata Sandi Baru');
    }

    public function test_reset_form_keeps_no_referrer_meta_and_security_headers(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'test-token',
            'email' => 'recoverable@example.com',
        ]));

        $response->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            // Meta anti-bocor token harus tetap ter-render di dalam <head> template.
            ->assertSee('<meta name="referrer" content="no-referrer">', false);
    }

    public function test_invalid_link_state_is_still_rendered_with_site_chrome(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'dummy-token',
            'email' => 'not-an-email',
        ]));

        $response->assertStatus(422)
            ->assertViewIs('password-reset')
            ->assertViewHas('invalidLink', true)
            ->assertSee('auth-reset-page', false)
            ->assertSee('Minta tautan reset baru');
    }
}

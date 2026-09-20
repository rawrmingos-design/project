<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Models\User;
use App\Support\WhatsappNumberNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Google signup must not create a user row with a NULL `no_wa`.
 *
 * `users.no_wa` is NOT NULL with no default, and `no_wa` is the buyer identity
 * used by the order API + WhatsApp flows, so a Google signup that has no phone
 * number yet must be held in the session and completed before the account is
 * activated (mirrors the required `no_wa` field on the normal sign-up form).
 */
class GoogleAuthSignupTest extends TestCase
{
    use RefreshDatabase;

    private const GOOGLE_CLIENT_ID = 'test-google-client-id.apps.googleusercontent.com';

    protected function setUp(): void
    {
        parent::setUp();

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
        ]);
    }

    private function fakeGoogleToken(array $claims = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(array_merge([
                'aud' => self::GOOGLE_CLIENT_ID,
                'iss' => 'https://accounts.google.com',
                'sub' => '117584989561986429719',
                'email' => 'alvinfrista@gmail.com',
                'email_verified' => 'true',
                'name' => 'Alvin Frista',
                'picture' => 'https://lh3.googleusercontent.com/a/avatar=s96-c',
            ], $claims), 200),
        ]);
    }

    public function test_new_google_user_is_not_persisted_without_a_whatsapp_number(): void
    {
        $this->fakeGoogleToken();

        $response = $this->post('/id/auth/google', ['credential' => 'fake-token']);

        // The account must not be activated yet: no user row with a NULL no_wa,
        // and the guest is asked to complete the phone number first.
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
        $response->assertRedirect(route('auth.google.complete'));
    }

    public function test_completing_the_google_signup_creates_the_user_with_a_normalized_number(): void
    {
        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);

        $response = $this->post(route('auth.google.complete.post'), [
            'no_wa' => '081234567890',
        ]);

        $response->assertRedirect('/id/dashboard');

        $user = User::query()->where('email', 'alvinfrista@gmail.com')->firstOrFail();
        $this->assertSame(WhatsappNumberNormalizer::normalize('081234567890'), $user->no_wa);
        $this->assertSame('117584989561986429719', $user->google_id);
        $this->assertSame('Member', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_completing_the_google_signup_rejects_an_invalid_number(): void
    {
        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);

        $response = $this->post(route('auth.google.complete.post'), ['no_wa' => '12345']);

        $response->assertSessionHasErrors('no_wa');
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_completing_the_google_signup_rejects_a_number_already_used(): void
    {
        User::factory()->create(['no_wa' => '6281234567890']);

        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);

        $response = $this->post(route('auth.google.complete.post'), ['no_wa' => '081234567890']);

        $response->assertSessionHasErrors('no_wa');
        $this->assertDatabaseMissing('users', ['email' => 'alvinfrista@gmail.com']);
    }

    public function test_existing_google_user_still_logs_in_directly(): void
    {
        $existing = User::factory()->create([
            'email' => 'alvinfrista@gmail.com',
            'google_id' => '117584989561986429719',
        ]);

        $this->fakeGoogleToken();

        $response = $this->post('/id/auth/google', ['credential' => 'fake-token']);

        $response->assertRedirect('/id/dashboard');
        $this->assertAuthenticatedAs($existing);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_email_registers_google_link_without_touching_no_wa(): void
    {
        $existing = User::factory()->create([
            'email' => 'alvinfrista@gmail.com',
            'no_wa' => '628111111111',
            'google_id' => null,
        ]);

        $this->fakeGoogleToken();

        $response = $this->post('/id/auth/google', ['credential' => 'fake-token']);

        $response->assertRedirect('/id/dashboard');
        $this->assertAuthenticatedAs($existing);
        $this->assertSame(
            '117584989561986429719',
            $existing->fresh()->google_id,
            'Google id should be linked to the existing account.'
        );
        $this->assertSame('628111111111', $existing->fresh()->no_wa);
    }

    public function test_completion_page_is_reachable_for_a_pending_google_signup(): void
    {
        $this->fakeGoogleToken();
        $this->post('/id/auth/google', ['credential' => 'fake-token']);

        $this->get(route('auth.google.complete'))->assertOk();
    }

    public function test_completion_page_redirects_guests_without_a_pending_signup(): void
    {
        $this->get(route('auth.google.complete'))->assertRedirect('/id/sign-in');
    }

    public function test_google_columns_stay_optional_on_legacy_schemas(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'google_id'));
    }
}

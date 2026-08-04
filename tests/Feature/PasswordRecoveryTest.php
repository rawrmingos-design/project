<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\PasswordRecoveryService;
use App\Services\WhatsappNotificationService;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Mockery\MockInterface;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
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

    public function test_known_and_unknown_api_recovery_requests_receive_the_same_response_without_password_change(): void
    {
        $user = User::factory()->create([
            'username' => 'recoverable',
            'email' => 'recoverable@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $originalPassword = $user->password;

        $this->mock(PasswordRecoveryService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('requestRecovery')->twice();
        });

        $known = $this->postJson('/api/auth/forgot-password', ['username' => 'recoverable']);
        $unknown = $this->postJson('/api/auth/forgot-password', ['username' => 'unknown-account']);

        $known->assertStatus(202)->assertExactJson([
            'success' => true,
            'message' => PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE,
        ]);
        $unknown->assertStatus(202)->assertExactJson($known->json());
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_recovery_service_stores_only_a_hashed_broker_token_and_sends_email_first(): void
    {
        Mail::fake();
        $createdUser = User::factory()->create([
            'username' => 'recoverable',
            'email' => 'recoverable@example.com',
            'no_wa' => '628123456789',
        ]);
        /** @var User $user */
        $user = User::query()->findOrFail($createdUser->getKey());
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendMessage');
        });

        app(PasswordRecoveryService::class)->requestRecovery($user->username);

        $record = (array) \Illuminate\Support\Facades\DB::table('password_resets')->where('email', $user->email)->first();
        $plainToken = null;

        Mail::assertSent(\App\Mail\TransactionMail::class, function ($mail) use ($user, &$plainToken): bool {
            if (! $mail->hasTo($user->email)) {
                return false;
            }

            preg_match('#/id/reset-password/([^?"<]+)#', (string) $mail->contentBody, $matches);
            $plainToken = isset($matches[1]) ? rawurldecode($matches[1]) : null;

            return filled($plainToken);
        });

        $this->assertNotEmpty($plainToken);
        $this->assertNotEmpty($record['token']);
        $this->assertNotSame($plainToken, $record['token']);
        $this->assertTrue(Hash::check((string) $plainToken, (string) $record['token']));
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('password_resets')->where('email', $user->email)->count());
    }

    public function test_duplicate_email_is_not_eligible_for_recovery(): void
    {
        Mail::fake();
        $user = User::factory()->create(['username' => 'duplicate-owner', 'email' => 'shared@example.com']);
        User::factory()->create(['username' => 'duplicate-other', 'email' => 'shared@example.com']);
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendMessage');
        });

        app(PasswordRecoveryService::class)->requestRecovery($user->username);

        $this->assertDatabaseMissing('password_resets', ['email' => 'shared@example.com']);
        Mail::assertNothingSent();
    }

    public function test_duplicate_email_cannot_complete_a_reset_even_when_a_broker_token_exists(): void
    {
        $createdUser = User::factory()->create([
            'email' => 'shared@example.com',
            'password' => Hash::make('old-password'),
        ]);
        /** @var User $user */
        $user = User::query()->findOrFail($createdUser->getKey());
        User::factory()->create(['email' => 'shared@example.com']);
        /** @var PasswordBroker $broker */
        $broker = Password::broker('users');
        $token = $broker->createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'shared@example.com',
            'token' => $token,
            'password' => 'A-new-password-123',
            'password_confirmation' => 'A-new-password-123',
        ])->assertStatus(422)->assertJsonPath('message', PasswordRecoveryService::RESET_FAILURE_MESSAGE);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_whatsapp_is_used_only_when_email_delivery_fails(): void
    {
        $createdUser = User::factory()->create([
            'username' => 'whatsapp-fallback',
            'email' => 'fallback@example.com',
            'no_wa' => '628123456789',
        ]);
        /** @var User $user */
        $user = User::query()->findOrFail($createdUser->getKey());

        $this->mock(EmailNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendGenericEmail')->once()->andReturnFalse();
        });
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(fn (string $target, string $message): bool => $target === '628123456789'
                    && str_contains($message, url('/id/reset-password/'))
                    && str_contains($message, 'email=fallback%40example.com'))
                ->andReturn(['success' => true]);
        });

        app(PasswordRecoveryService::class)->requestRecovery($user->username);

        $this->assertDatabaseHas('password_resets', ['email' => 'fallback@example.com']);
    }

    public function test_failed_delivery_on_all_channels_removes_the_broker_token(): void
    {
        $user = User::factory()->create([
            'username' => 'delivery-failure',
            'email' => 'failure@example.com',
            'no_wa' => '628123456789',
        ]);

        $this->mock(EmailNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendGenericEmail')->once()->andReturnFalse();
        });
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once()->andReturn(['success' => false]);
        });

        app(PasswordRecoveryService::class)->requestRecovery($user->username);

        $this->assertDatabaseMissing('password_resets', ['email' => 'failure@example.com']);
    }

    public function test_web_reset_form_is_private_and_does_not_store_the_reset_url_in_referrer_policy(): void
    {
        $response = $this->get('/id/reset-password/test-token?email=recoverable%40example.com');

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('name="token" value="test-token"', false)
            ->assertSee('<meta name="referrer" content="no-referrer">', false);
    }

    public function test_valid_reset_revokes_sanctum_tokens_rotates_remember_token_and_preserves_two_factor_data(): void
    {
        $createdUser = User::factory()->create([
            'email' => 'recoverable@example.com',
            'password' => Hash::make('old-password'),
            'remember_token' => 'old-remember-token',
            'two_factor_secret' => 'TWOFASECRET',
            'two_factor_recovery_codes' => '["one","two"]',
        ]);
        /** @var User $user */
        $user = User::query()->findOrFail($createdUser->getKey());
        /** @var PasswordBroker $broker */
        $broker = Password::broker('users');
        $token = $broker->createToken($user);
        $user->createToken('first');
        $user->createToken('second');

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'A-new-password-123',
            'password_confirmation' => 'A-new-password-123',
        ])->assertOk()->assertJsonPath('success', true);

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('A-new-password-123', $fresh->password));
        $this->assertNotSame('old-remember-token', $fresh->remember_token);
        $this->assertSame('TWOFASECRET', $fresh->two_factor_secret);
        $this->assertSame('["one","two"]', $fresh->two_factor_recovery_codes);
        $this->assertSame(0, $fresh->tokens()->count());
        $this->assertDatabaseMissing('password_resets', ['email' => $user->email]);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'Another-password-123',
            'password_confirmation' => 'Another-password-123',
        ])->assertStatus(422)->assertJsonPath('message', PasswordRecoveryService::RESET_FAILURE_MESSAGE);
    }

    public function test_expired_token_cannot_change_credentials_or_revoke_sessions(): void
    {
        $createdUser = User::factory()->create([
            'email' => 'expired@example.com',
            'password' => Hash::make('old-password'),
            'remember_token' => 'old-remember-token',
        ]);
        /** @var User $user */
        $user = User::query()->findOrFail($createdUser->getKey());
        /** @var PasswordBroker $broker */
        $broker = Password::broker('users');
        $token = $broker->createToken($user);
        $user->createToken('existing-session');
        $expiresIn = (int) config('auth.passwords.users.expire');

        try {
            $this->travel($expiresIn + 1)->minutes();

            $this->postJson('/api/auth/reset-password', [
                'email' => $user->email,
                'token' => $token,
                'password' => 'A-new-password-123',
                'password_confirmation' => 'A-new-password-123',
            ])->assertStatus(422)->assertJsonPath('message', PasswordRecoveryService::RESET_FAILURE_MESSAGE);

            $fresh = $user->fresh();
            $this->assertTrue(Hash::check('old-password', $fresh->password));
            $this->assertSame('old-remember-token', $fresh->remember_token);
            $this->assertSame(1, $fresh->tokens()->count());
        } finally {
            $this->travelBack();
        }
    }

    public function test_email_and_whatsapp_copy_use_the_configured_expiry(): void
    {
        config(['auth.passwords.users.expire' => 17]);
        $emailContent = null;
        $whatsappContent = null;
        User::factory()->create([
            'username' => 'configured-expiry',
            'email' => 'configured-expiry@example.com',
            'no_wa' => '628123456789',
        ]);

        $this->mock(EmailNotificationService::class, function (MockInterface $mock) use (&$emailContent): void {
            $mock->shouldReceive('sendGenericEmail')
                ->once()
                ->withArgs(function (string $email, string $_subject, string $content) use (&$emailContent): bool {
                    $emailContent = $content;

                    return $email === 'configured-expiry@example.com'
                        && $_subject === 'Instruksi Reset Kata Sandi';
                })
                ->andReturnFalse();
        });
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$whatsappContent): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function (string $phone, string $message) use (&$whatsappContent): bool {
                    $whatsappContent = $message;

                    return $phone === '628123456789';
                })
                ->andReturn(['success' => true]);
        });

        app(PasswordRecoveryService::class)->requestRecovery('configured-expiry');

        $this->assertStringContainsString('17 menit', (string) $emailContent);
        $this->assertStringContainsString('17 menit', (string) $whatsappContent);
    }

    public function test_recovery_request_throttle_applies_to_api_and_web_routes(): void
    {
        $this->mock(PasswordRecoveryService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('requestRecovery')->times(4);
        });

        $this->postJson('/api/auth/forgot-password', ['username' => 'same-target'])->assertStatus(202);
        $this->postJson('/api/auth/forgot-password', ['username' => 'same-target'])->assertStatus(202);
        $this->postJson('/api/auth/forgot-password', ['username' => 'same-target'])->assertStatus(202);
        $this->postJson('/api/auth/forgot-password', ['username' => 'same-target'])->assertStatus(429);

        $this->post('/id/forgot-password', ['username' => 'web-target'])->assertRedirect();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\PasswordRecoveryService;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class P04PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_ambiguous_username_rejects_recovery_without_leaking_existence(): void
    {
        User::factory()->create(['username' => 'john']);
        User::factory()->create(['username' => 'john']);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Password recovery request processed.'
                    && $context['reason'] === 'ambiguous_username'
                    && $context['user_id'] === null;
            });

        $this->post(route('post.forgot'), ['username' => 'john'])
            ->assertRedirect()
            ->assertSessionHas('success', PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE);
    }

    public function test_unknown_username_rejects_recovery_without_leaking_non_existence(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Password recovery request processed.'
                    && $context['reason'] === 'unknown_username'
                    && $context['user_id'] === null;
            });

        $this->post(route('post.forgot'), ['username' => 'nonexistent'])
            ->assertRedirect()
            ->assertSessionHas('success', PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE);
    }

    public function test_invalid_email_rejects_recovery_silently(): void
    {
        $user = User::factory()->create([
            'username' => 'validuser',
            'email' => 'not-an-email',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return $message === 'Password recovery request processed.'
                    && $context['reason'] === 'invalid_email'
                    && $context['user_id'] === $user->id;
            });

        $this->post(route('post.forgot'), ['username' => 'validuser'])
            ->assertRedirect()
            ->assertSessionHas('success', PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE);
    }

    public function test_duplicate_email_rejects_recovery_silently(): void
    {
        User::factory()->create([
            'username' => 'user1',
            'email' => 'shared@example.com',
        ]);

        $user2 = User::factory()->create([
            'username' => 'user2',
            'email' => 'shared@example.com',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user2) {
                return $message === 'Password recovery request processed.'
                    && $context['reason'] === 'duplicate_email'
                    && $context['user_id'] === $user2->id;
            });

        $this->post(route('post.forgot'), ['username' => 'user2'])
            ->assertRedirect()
            ->assertSessionHas('success', PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE);
    }

    public function test_valid_request_attempts_email_delivery(): void
    {
        $user = User::factory()->create([
            'username' => 'validuser',
            'email' => 'valid@example.com',
        ]);

        $emailService = $this->mock(EmailNotificationService::class);
        $emailService->shouldReceive('sendGenericEmail')
            ->once()
            ->withArgs(function (string $email, string $subject, string $content, array $metadata) use ($user) {
                return $email === 'valid@example.com'
                    && $subject === 'Instruksi Reset Kata Sandi'
                    && str_contains($content, 'Halo')
                    && str_contains($content, 'Reset kata sandi')
                    && $metadata['notification_type'] === 'password_recovery';
            })
            ->andReturn(true);

        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return $message === 'Password recovery request processed.'
                    && $context['reason'] === 'delivery_attempted'
                    && $context['user_id'] === $user->id
                    && $context['email_delivered'] === true;
            });

        $this->post(route('post.forgot'), ['username' => 'validuser'])
            ->assertRedirect()
            ->assertSessionHas('success', PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE);
    }

    public function test_whatsapp_fallback_when_email_fails(): void
    {
        $user = User::factory()->create([
            'username' => 'validuser',
            'email' => 'valid@example.com',
            'no_wa' => '628123456789',
        ]);

        $emailService = $this->mock(EmailNotificationService::class);
        $emailService->shouldReceive('sendGenericEmail')->once()->andReturn(false);

        $whatsappService = $this->mock(WhatsappNotificationService::class);
        $whatsappService->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function (string $phone, string $message) {
                return $phone === '628123456789'
                    && str_contains($message, 'reset kata sandi')
                    && str_contains($message, 'berlaku 60 menit');
            })
            ->andReturn(['success' => true]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return $message === 'Password recovery request processed.'
                    && $context['reason'] === 'delivery_attempted'
                    && $context['user_id'] === $user->id
                    && $context['email_delivered'] === false
                    && $context['whatsapp_delivered'] === true;
            });

        $this->post(route('post.forgot'), ['username' => 'validuser'])
            ->assertRedirect()
            ->assertSessionHas('success', PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE);
    }

    public function test_token_deleted_when_both_delivery_methods_fail(): void
    {
        $user = User::factory()->create([
            'username' => 'validuser',
            'email' => 'valid@example.com',
            'no_wa' => '628123456789',
        ]);

        $emailService = $this->mock(EmailNotificationService::class);
        $emailService->shouldReceive('sendGenericEmail')->once()->andReturn(false);

        $whatsappService = $this->mock(WhatsappNotificationService::class);
        $whatsappService->shouldReceive('sendMessage')->once()->andReturn(['success' => false]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return $context['reason'] === 'no_delivery_success';
            });

        $this->post(route('post.forgot'), ['username' => 'validuser'])
            ->assertRedirect();

        $this->assertDatabaseMissing('password_resets', [
            'email' => 'valid@example.com',
        ]);
    }

    public function test_reset_form_serves_security_headers(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'dummy-token',
            'email' => 'test@example.com',
        ]));

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'no-store, private');
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_reset_form_rejects_invalid_email_in_query(): void
    {
        $response = $this->get(route('password.reset', [
            'token' => 'dummy-token',
            'email' => 'not-an-email',
        ]));

        $response->assertStatus(422);
        $response->assertViewIs('password-reset');
        $response->assertViewHas('invalidLink', true);
        $response->assertViewHas('token', '');
        $response->assertViewHas('email', '');
    }

    public function test_successful_reset_revokes_all_sanctum_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;

        $this->assertCount(2, $user->tokens);

        $broker = Password::broker('users');
        $resetToken = $broker->createToken($user);

        $this->post(route('password.update'), [
            'token' => $resetToken,
            'email' => 'user@example.com',
            'password' => 'new-secure-password-12345',
            'password_confirmation' => 'new-secure-password-12345',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertCount(0, $user->tokens);
        $this->assertTrue(Hash::check('new-secure-password-12345', $user->password));
    }

    public function test_reset_requires_minimum_12_character_password(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $broker = Password::broker('users');
        $token = $broker->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    public function test_reset_with_invalid_token_returns_error(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'new-secure-password-12345',
            'password_confirmation' => 'new-secure-password-12345',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_reset_with_duplicate_email_fails(): void
    {
        User::factory()->create(['email' => 'shared@example.com']);
        User::factory()->create(['email' => 'shared@example.com']);

        $this->post(route('password.update'), [
            'token' => 'any-token',
            'email' => 'shared@example.com',
            'password' => 'new-secure-password-12345',
            'password_confirmation' => 'new-secure-password-12345',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_production_reset_url_enforces_https(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        $emailService = $this->mock(EmailNotificationService::class);
        $emailService->shouldReceive('sendGenericEmail')
            ->once()
            ->withArgs(function (string $email, string $subject, string $content) {
                return str_contains($content, 'https://');
            })
            ->andReturn(true);

        Log::shouldReceive('info');

        app(PasswordRecoveryService::class)->requestRecovery('testuser');
    }

    public function test_successful_reset_invalidates_session_and_logs_out(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $broker = Password::broker('users');
        $token = $broker->createToken($user);

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-secure-password-12345',
            'password_confirmation' => 'new-secure-password-12345',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Kata sandi berhasil diperbarui. Silakan masuk kembali.');

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_remembers_safe_internal_redirect(): void
    {
        $response = $this->get('/id/sign-in?redirect=' . urlencode('/id/reseller/registry?step=2'));

        $response->assertOk();
        $this->assertSame('/id/reseller/registry?step=2', session('auth.login.redirect'));
    }

    public function test_login_page_discards_external_redirect(): void
    {
        $response = $this->withSession(['auth.login.redirect' => '/id/settings'])
            ->get('/id/sign-in?redirect=' . urlencode('https://attacker.example/'));

        $response->assertOk();
        $this->assertNull(session('auth.login.redirect'));
    }

    public function test_password_login_redirects_to_stored_target_and_consumes_it(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'password' => Hash::make('password'),
        ]);

        $response = $this->withSession(['auth.login.redirect' => '/id/reseller/registry'])
            ->post('/id/sign-in', [
                'username' => $user->username,
                'password' => 'password',
            ]);

        $response->assertRedirect('/id/reseller/registry');
        $this->assertNull(session('auth.login.redirect'));
    }

    public function test_password_login_falls_back_to_dashboard_without_target(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/id/sign-in', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_two_factor_login_redirects_to_stored_target(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = User::factory()->create([
            'role' => 'Member',
            'password' => Hash::make('password'),
            'two_factor_secret' => $secret,
        ]);

        $response = $this->withSession(['auth.login.redirect' => '/id/reseller/registry'])
            ->post('/id/sign-in', [
                'username' => $user->username,
                'password' => 'password',
                'two_factor_code' => (new Google2FA())->getCurrentOtp($secret),
            ]);

        $response->assertRedirect('/id/reseller/registry');
    }

    #[DataProvider('unsafeRedirects')]
    public function test_unsafe_stored_redirect_falls_back_to_dashboard(string $redirect): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'password' => Hash::make('password'),
        ]);

        $response = $this->withSession(['auth.login.redirect' => $redirect])
            ->post('/id/sign-in', [
                'username' => $user->username,
                'password' => 'password',
            ]);

        $response->assertRedirect(route('dashboard'));
    }

    public static function unsafeRedirects(): array
    {
        return [
            ['https://attacker.example/'],
            ['//attacker.example/'],
            ['javascript:alert(1)'],
            ['/\\\\attacker.example'],
            ["/id/registry\nX-Injected: true"],
        ];
    }
}

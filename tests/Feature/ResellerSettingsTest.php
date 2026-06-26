<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResellerIntegration;
use App\Models\ResellerPushSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ResellerSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reseller_settings()
    {
        $response = $this->get('/id/reseller/settings');
        // Usually auth redirects to /login or a specific page.
        $response->assertStatus(302);
    }

    public function test_regular_user_cannot_access_reseller_settings()
    {
        $user = User::factory()->create([
            'role' => 'Member'
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/settings');
        // The middleware `reseller.only` redirects to dashboard.
        $response->assertRedirect('/id/dashboard');
    }

    public function test_reseller_can_access_reseller_settings()
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);
        
        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-001',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/settings');
        
        $response->assertStatus(200);
    }

    public function test_settings_page_exposes_twofactor_status_correctly()
    {
        // Without 2FA
        $userNo2fa = User::factory()->create([
            'role'              => 'Member',
            'two_factor_secret' => null,
        ]);
        ResellerIntegration::create([
            'user_id'          => $userNo2fa->id,
            'integration_code' => 'TEST-2FA-OFF',
            'is_active'        => true,
        ]);

        $this->actingAs($userNo2fa)
            ->get('/id/reseller/settings')
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) =>
                $page->where('settingsPage.twoFactor.enabled', false)
            );

        // With 2FA
        $userWith2fa = User::factory()->create([
            'role'              => 'Member',
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        ]);
        ResellerIntegration::create([
            'user_id'          => $userWith2fa->id,
            'integration_code' => 'TEST-2FA-ON',
            'is_active'        => true,
        ]);

        $this->actingAs($userWith2fa)
            ->get('/id/reseller/settings')
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) =>
                $page->where('settingsPage.twoFactor.enabled', true)
            );
    }

    public function test_settings_page_exposes_push_status_and_vapid_public_key()
    {
        config([
            'services.webpush.vapid.public_key' => 'BEl6VapidPublicKeyExample1234567890',
            'services.webpush.vapid.private_key' => 'private-key',
            'services.webpush.vapid.subject' => 'mailto:test@example.com',
        ]);

        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        ResellerIntegration::create([
            'user_id' => $user->id,
            'integration_code' => 'TEST-PUSH-SETTINGS',
            'is_active' => true,
        ]);

        ResellerPushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://example.com/push/settings',
            'public_key' => 'public-key-value',
            'auth_token' => 'auth-token-value',
            'content_encoding' => 'aes128gcm',
        ]);

        $this->actingAs($user)
            ->get('/id/reseller/settings')
            ->assertInertia(fn (Assert $page) =>
                $page
                    ->where('settingsPage.push.enabled', true)
                    ->where('settingsPage.push.subscriptionCount', 1)
                    ->where('settingsPage.push.vapidPublicKey', 'BEl6VapidPublicKeyExample1234567890')
                    ->where('settingsPage.push.configured', true)
                    ->where('settingsPage.push.settingsUrl', route('reseller.settings'))
            );
    }
}


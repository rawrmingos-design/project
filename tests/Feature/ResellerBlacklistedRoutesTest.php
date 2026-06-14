<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerBlacklistedRoutesTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createResellerUser(): User
    {
        $user = User::factory()->create(['role' => 'Gold']);

        ResellerIntegration::factory()->create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-BL-' . strtoupper(uniqid()),
            'mode'             => 'live',
            'is_active'        => true,
        ]);

        return $user;
    }

    private function createRegularUser(): User
    {
        return User::factory()->create(['role' => 'Member']);
    }

    // ─── Reseller blocked — /deposit/history ─────────────────────────────────

    /**
     * /deposit/history (user biasa) should silently redirect resellers
     * to their dedicated /id/reseller/deposits page (no flash message).
     */
    public function test_reseller_accessing_deposit_history_is_redirected_to_reseller_deposits(): void
    {
        $reseller = $this->createResellerUser();

        $this->actingAs($reseller)
            ->get('/id/deposit/history')
            ->assertRedirect('/id/reseller/deposits');
    }

    public function test_redirect_from_deposit_history_has_no_flash_message(): void
    {
        $reseller = $this->createResellerUser();

        $response = $this->actingAs($reseller)->get('/id/deposit/history');

        $response->assertRedirect('/id/reseller/deposits');
        // No flash 'info' key — this is a seamless/smart redirect
        $this->assertNull(session('info'));
    }

    // ─── Reseller blocked — /dashboard/history ────────────────────────────────

    /**
     * /dashboard/history should redirect resellers to the hub with a flash message.
     */
    public function test_reseller_accessing_dashboard_history_is_redirected_to_reseller_hub(): void
    {
        $reseller = $this->createResellerUser();

        $this->actingAs($reseller)
            ->get('/id/dashboard/history')
            ->assertRedirect('/id/reseller/dashboard');
    }

    public function test_redirect_from_dashboard_history_shows_flash_message(): void
    {
        $reseller = $this->createResellerUser();

        $this->actingAs($reseller)
            ->get('/id/dashboard/history')
            ->assertRedirect('/id/reseller/dashboard')
            ->assertSessionHas('info', 'Halaman ini tidak tersedia untuk akun Reseller Hub.');
    }

    // ─── Reseller blocked — /settings/api-key/regenerate ─────────────────────

    /**
     * The legacy API key regenerate endpoint should redirect resellers to the hub.
     * Resellers use /id/reseller/credentials for key management instead.
     */
    public function test_reseller_cannot_regenerate_legacy_api_key(): void
    {
        $reseller = $this->createResellerUser();

        $this->actingAs($reseller)
            ->post('/id/settings/api-key/regenerate')
            ->assertRedirect('/id/reseller/dashboard');
    }

    public function test_legacy_api_key_redirect_shows_flash_message(): void
    {
        $reseller = $this->createResellerUser();

        $this->actingAs($reseller)
            ->post('/id/settings/api-key/regenerate')
            ->assertRedirect('/id/reseller/dashboard')
            ->assertSessionHas('info', 'Halaman ini tidak tersedia untuk akun Reseller Hub.');
    }

    // ─── Regular users NOT affected ──────────────────────────────────────────

    public function test_regular_user_can_access_deposit_history(): void
    {
        $user = $this->createRegularUser();

        $this->actingAs($user)
            ->get('/id/deposit/history')
            ->assertStatus(200);
    }

    public function test_regular_user_can_access_dashboard_history(): void
    {
        $user = $this->createRegularUser();

        $this->actingAs($user)
            ->get('/id/dashboard/history')
            ->assertStatus(200);
    }

    // ─── Guest redirected to login ────────────────────────────────────────────

    public function test_guest_accessing_blocked_route_is_redirected_to_login(): void
    {
        $blockedRoutes = [
            '/id/deposit/history',
            '/id/dashboard/history',
        ];

        foreach ($blockedRoutes as $route) {
            $this->get($route)->assertStatus(302, "Guest should be redirected from {$route}");
        }
    }

    // ─── Routes reseller STILL can access ────────────────────────────────────

    public function test_reseller_can_still_access_settings(): void
    {
        $reseller = $this->createResellerUser();

        $this->actingAs($reseller)
            ->get('/id/settings')
            ->assertStatus(200);
    }

    public function test_reseller_can_still_access_deposit_page(): void
    {
        $reseller = $this->createResellerUser();

        $this->actingAs($reseller)
            ->get('/id/deposit')
            ->assertStatus(200);
    }

    public function test_reseller_can_still_access_affiliate_page(): void
    {
        $reseller = $this->createResellerUser();

        $this->actingAs($reseller)
            ->get('/id/affiliate')
            ->assertStatus(200);
    }
}

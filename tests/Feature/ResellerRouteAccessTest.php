<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createResellerUser(): User
    {
        $user = User::factory()->create(['role' => 'Member']);

        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-ROUTE-' . strtoupper(uniqid()),
            'mode'             => 'live',
            'is_active'        => true,
        ]);

        return $user;
    }

    // --- Hard redirect: Reseller → /id/reseller ---

    public function test_reseller_is_hard_redirected_from_user_dashboard(): void
    {
        $reseller = $this->createResellerUser();

        $this->actingAs($reseller)
            ->get('/id/dashboard')
            ->assertRedirect('/id/reseller/dashboard');
    }

    public function test_regular_user_can_still_access_user_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($user)->get('/id/dashboard');

        // Regular user should reach the dashboard (200 or inertia response)
        $response->assertStatus(200);
    }

    public function test_guest_is_redirected_to_login_from_user_dashboard(): void
    {
        $this->get('/id/dashboard')->assertStatus(302);
    }

    // --- reseller.only guard ---

    public function test_non_reseller_cannot_access_reseller_hub(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $this->actingAs($user)
            ->get('/id/reseller/dashboard')
            ->assertRedirect('/id/dashboard');
    }

    public function test_reseller_can_access_all_hub_routes(): void
    {
        $reseller = $this->createResellerUser();

        $routes = [
            '/id/reseller/dashboard',
            '/id/reseller/credentials',
            '/id/reseller/orders',
            '/id/reseller/deposits',
            '/id/reseller/callbacks',
            '/id/reseller/sandbox',
            '/id/reseller/settings',
        ];

        foreach ($routes as $route) {
            $this->actingAs($reseller)
                ->get($route)
                ->assertStatus(200, "Expected 200 on route: {$route}");
        }
    }

    public function test_guest_cannot_access_any_reseller_hub_route(): void
    {
        $routes = [
            '/id/reseller/dashboard',
            '/id/reseller/credentials',
            '/id/reseller/deposits',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertStatus(302);
        }
    }
}

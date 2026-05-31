<?php

namespace Tests\Feature\Frontend;

use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_panel(): void
    {
        $response = $this->get('/id/reseller');
        $response->assertRedirect(route('login'));
    }

    public function test_non_reseller_member_is_redirected_away(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        $response = $this->actingAs($user)->get('/id/reseller');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Akses ditolak: Anda tidak memiliki integrasi Reseller.');
    }

    public function test_reseller_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        ResellerIntegration::query()->create([
            'user_id' => $user->id,
            'integration_code' => 'test-integration',
            'mode' => 'sandbox',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/id/reseller');

        $response->assertOk();
        // Since it's Inertia, we can just assert it's a 200 OK. 
        // Inertia testing package assertions could be used here if needed.
    }

    public function test_admin_without_integration_cannot_access_reseller_panel(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this->actingAs($admin)->get('/id/reseller');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Akses ditolak: Anda tidak memiliki integrasi Reseller.');
    }
}

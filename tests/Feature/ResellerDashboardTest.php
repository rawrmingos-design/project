<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ResellerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createResellerUser(string $mode = 'live', bool $isActive = true): User
    {
        $user = User::factory()->create(['role' => 'Member']);

        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-DASH-' . strtoupper($mode),
            'mode'             => $mode,
            'is_active'        => $isActive,
        ]);

        return $user;
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/id/reseller')->assertStatus(302);
    }

    public function test_dashboard_shows_live_active_when_integration_is_active(): void
    {
        $user = $this->createResellerUser('live', true);

        $this->actingAs($user)
            ->get('/id/reseller')
            ->assertInertia(fn (Assert $page) =>
                $page->where('live.is_active', true)
            );
    }

    public function test_dashboard_shows_live_inactive_when_integration_is_inactive(): void
    {
        $user = $this->createResellerUser('live', false);

        $this->actingAs($user)
            ->get('/id/reseller')
            ->assertInertia(fn (Assert $page) =>
                $page->where('live.is_active', false)
            );
    }

    public function test_dashboard_shows_sandbox_active_when_integration_is_active(): void
    {
        $user = $this->createResellerUser('sandbox', true);

        $this->actingAs($user)
            ->get('/id/reseller')
            ->assertInertia(fn (Assert $page) =>
                $page->where('sandbox.is_active', true)
            );
    }

    public function test_dashboard_shows_sandbox_inactive_when_integration_is_inactive(): void
    {
        $user = $this->createResellerUser('sandbox', false);

        $this->actingAs($user)
            ->get('/id/reseller')
            ->assertInertia(fn (Assert $page) =>
                $page->where('sandbox.is_active', false)
            );
    }

    public function test_dashboard_sends_correct_prop_structure(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-DASH-LIVE',
            'mode'             => 'live',
            'is_active'        => true,
        ]);

        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-DASH-SANDBOX',
            'mode'             => 'sandbox',
            'is_active'        => true,
        ]);

        $this->actingAs($user)
            ->get('/id/reseller')
            ->assertInertia(fn (Assert $page) =>
                $page
                    ->has('live', fn (Assert $live) =>
                        $live->has('is_active')->has('allowed_ips')
                    )
                    ->has('sandbox', fn (Assert $sandbox) =>
                        $sandbox->has('is_active')->has('api_key_hint')
                    )
                    ->has('metrics')
                    ->has('recent_orders')
            );
    }

    public function test_dashboard_live_is_null_when_no_live_integration(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        // Only sandbox integration
        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-DASH-SBX-ONLY',
            'mode'             => 'sandbox',
            'is_active'        => true,
        ]);

        $this->actingAs($user)
            ->get('/id/reseller')
            ->assertInertia(fn (Assert $page) =>
                $page->where('live', null)
                     ->where('sandbox.is_active', true)
            );
    }
}

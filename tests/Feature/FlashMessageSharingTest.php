<?php

namespace Tests\Feature;

use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that HandleInertiaRequests correctly shares flash session data
 * into every Inertia page response via the 'flash' shared prop.
 */
class FlashMessageSharingTest extends TestCase
{
    use RefreshDatabase;

    private function makeResellerUser(): User
    {
        $user = User::factory()->create([
            'role'             => 'Member',   // 'Member' is the valid role in this project
            'two_factor_secret' => 'test-2fa-secret',
        ]);

        // EnsureIsReseller middleware checks resellerIntegrations()->exists()
        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'FLASH-TEST-' . $user->id,
            'mode'             => 'live',
            'is_active'        => true,
            'allowed_ips'      => [],
        ]);

        return $user;
    }

    public function test_success_flash_is_shared_in_inertia_props(): void
    {
        $user = $this->makeResellerUser();

        $response = $this->actingAs($user)
            ->withSession(['success' => 'Operasi berhasil dilakukan.'])
            ->get('/id/reseller');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->where('flash.success', 'Operasi berhasil dilakukan.')
        );
    }

    public function test_error_flash_is_shared_in_inertia_props(): void
    {
        $user = $this->makeResellerUser();

        $response = $this->actingAs($user)
            ->withSession(['error' => 'Terjadi kesalahan.'])
            ->get('/id/reseller');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->where('flash.error', 'Terjadi kesalahan.')
        );
    }

    public function test_info_flash_is_shared_in_inertia_props(): void
    {
        $user = $this->makeResellerUser();

        $response = $this->actingAs($user)
            ->withSession(['info' => 'Ada informasi penting.'])
            ->get('/id/reseller');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->where('flash.info', 'Ada informasi penting.')
        );
    }

    public function test_flash_is_null_when_no_session_message_set(): void
    {
        $user = $this->makeResellerUser();

        $response = $this->actingAs($user)->get('/id/reseller');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->where('flash.success', null)
                 ->where('flash.error', null)
                 ->where('flash.info', null)
        );
    }
}

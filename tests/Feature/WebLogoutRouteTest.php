<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebLogoutRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout_via_primary_public_route(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        $response = $this->actingAs($user)->post('/id/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout_via_legacy_compatibility_route(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        $response = $this->actingAs($user)->post('/id/id/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}

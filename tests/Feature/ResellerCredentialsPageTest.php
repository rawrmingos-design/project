<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ResellerCredentialsPageTest extends TestCase
{
    use RefreshDatabase;

    private function createResellerUser(bool $with2fa = false): User
    {
        $user = User::factory()->create([
            'role'              => 'Member',
            'two_factor_secret' => $with2fa ? 'JBSWY3DPEHPK3PXP' : null,
            'api_key'           => Hash::make('live_test_key'),
            'api_key_hint'      => '...hint',
        ]);

        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-CRED-01',
            'is_active'        => true,
            'mode'             => 'live',
        ]);

        return $user;
    }

    public function test_guest_cannot_access_credentials_page(): void
    {
        $response = $this->get('/id/reseller/credentials');

        $response->assertStatus(302);
    }

    public function test_reseller_can_access_credentials_page(): void
    {
        $user = $this->createResellerUser();

        $response = $this->actingAs($user)->get('/id/reseller/credentials');

        $response->assertStatus(200);
    }

    public function test_credentials_page_exposes_twofactor_enabled_when_2fa_active(): void
    {
        $user = $this->createResellerUser(with2fa: true);

        $this->actingAs($user)
            ->get('/id/reseller/credentials')
            ->assertInertia(fn (Assert $page) =>
                $page->where('authUser.twoFactorEnabled', true)
            );
    }

    public function test_credentials_page_exposes_twofactor_disabled_when_no_2fa(): void
    {
        $user = $this->createResellerUser(with2fa: false);

        $this->actingAs($user)
            ->get('/id/reseller/credentials')
            ->assertInertia(fn (Assert $page) =>
                $page->where('authUser.twoFactorEnabled', false)
            );
    }

    public function test_shared_auth_user_contains_expected_fields(): void
    {
        $user = $this->createResellerUser();

        $this->actingAs($user)
            ->get('/id/reseller/credentials')
            ->assertInertia(fn (Assert $page) =>
                $page->has('authUser', fn (Assert $authUser) =>
                    $authUser
                        ->has('id')
                        ->has('name')
                        ->has('email')
                        ->has('twoFactorEnabled')
                )
            );
    }
}

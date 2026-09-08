<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAccessGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_is_redirected_from_sign_in_and_sign_up(): void
    {
        $user = User::factory()->create(['role' => 'Member']);
        $this->actingAs($user);

        $this->get('/id/sign-in')->assertRedirect('/id/dashboard');
        $this->get('/id/sign-up')->assertRedirect('/id/dashboard');
    }

    public function test_guest_is_redirected_to_sign_in_from_dashboard(): void
    {
        $this->get('/id/dashboard')->assertRedirect('/id/sign-in');
    }
}

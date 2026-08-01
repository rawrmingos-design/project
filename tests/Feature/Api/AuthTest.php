<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'nama' => 'Test User',
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'test@example.com',
            'no_wa' => '08123456789',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'token_type'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'no_wa' => '628123456789', // Normalized
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'username' => 'loginuser',
            'password' => Hash::make('password123'),
            'role' => 'Member',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'loginuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'token_type'
                ]
            ]);
    }

    public function test_admin_cannot_login_via_api()
    {
        $admin = User::factory()->create([
            'username' => 'adminuser',
            'password' => Hash::make('password123'),
            'role' => 'Admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'adminuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Username / password mismatch'
            ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);

        $this->assertCount(0, $user->tokens);
    }

    public function test_forgot_password_accepts_a_valid_username_without_replacing_the_password(): void
    {
        $user = User::factory()->create([
            'username' => 'forgotuser',
            'no_wa' => '628123456789',
        ]);
        $originalPassword = $user->password;

        $response = $this->postJson('/api/auth/forgot-password', [
            'username' => 'forgotuser',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'message' => \App\Services\PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE,
            ]);

        $this->assertSame($originalPassword, $user->fresh()->password);
    }
}

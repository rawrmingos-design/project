<?php

namespace Tests\Feature;

use App\Models\ResellerIntegration;
use App\Models\ResellerPushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerPushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckRole::class,
        ]);
    }

    public function test_reseller_can_store_valid_push_subscription(): void
    {
        $user = $this->createResellerUser();
        $this->createResellerIntegration($user);

        $response = $this->actingAs($user)->postJson(route('reseller.push-subscriptions.store'), [
            'subscription' => [
                'endpoint' => 'https://push.example.test/subscriptions/alpha',
                'keys' => [
                    'p256dh' => 'public-key-alpha',
                    'auth' => 'auth-token-alpha',
                ],
                'contentEncoding' => 'aes128gcm',
            ],
        ], [
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Test Device)',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Push subscription berhasil disimpan.');

        $this->assertDatabaseHas('reseller_push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/subscriptions/alpha',
            'public_key' => 'public-key-alpha',
            'auth_token' => 'auth-token-alpha',
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function test_storing_same_endpoint_updates_existing_subscription_instead_of_creating_duplicate(): void
    {
        $user = $this->createResellerUser();
        $this->createResellerIntegration($user);

        $subscription = ResellerPushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/subscriptions/shared',
            'public_key' => 'public-key-old',
            'auth_token' => 'auth-token-old',
            'content_encoding' => 'aesgcm',
            'user_agent' => 'Old Agent',
        ]);

        $response = $this->actingAs($user)->postJson(route('reseller.push-subscriptions.store'), [
            'subscription' => [
                'endpoint' => 'https://push.example.test/subscriptions/shared',
                'keys' => [
                    'p256dh' => 'public-key-new',
                    'auth' => 'auth-token-new',
                ],
                'contentEncoding' => 'aes128gcm',
            ],
        ], [
            'User-Agent' => 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('subscription_id', $subscription->id);

        $this->assertDatabaseCount('reseller_push_subscriptions', 1);
        $this->assertDatabaseHas('reseller_push_subscriptions', [
            'id' => $subscription->id,
            'user_id' => $user->id,
            'endpoint' => 'https://push.example.test/subscriptions/shared',
            'public_key' => 'public-key-new',
            'auth_token' => 'auth-token-new',
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function test_store_requires_valid_endpoint_url(): void
    {
        $user = $this->createResellerUser();
        $this->createResellerIntegration($user);

        $response = $this->actingAs($user)->postJson(route('reseller.push-subscriptions.store'), [
            'subscription' => [
                'endpoint' => 'not-a-valid-url',
                'keys' => [
                    'p256dh' => 'public-key-invalid',
                    'auth' => 'auth-token-invalid',
                ],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subscription.endpoint']);

        $this->assertDatabaseCount('reseller_push_subscriptions', 0);
    }

    public function test_destroy_only_removes_authenticated_users_subscription(): void
    {
        $owner = $this->createResellerUser('owner-reseller', 'owner-reseller@example.com');
        $otherUser = $this->createResellerUser('other-reseller', 'other-reseller@example.com');
        $this->createResellerIntegration($owner);
        $this->createResellerIntegration($otherUser);

        ResellerPushSubscription::create([
            'user_id' => $owner->id,
            'endpoint' => 'https://push.example.test/subscriptions/remove-me',
            'public_key' => 'public-key-owner',
            'auth_token' => 'auth-token-owner',
            'content_encoding' => 'aes128gcm',
        ]);

        ResellerPushSubscription::create([
            'user_id' => $otherUser->id,
            'endpoint' => 'https://push.example.test/subscriptions/keep-me',
            'public_key' => 'public-key-other',
            'auth_token' => 'auth-token-other',
            'content_encoding' => 'aes128gcm',
        ]);

        $response = $this->actingAs($owner)->deleteJson(route('reseller.push-subscriptions.destroy'), [
            'endpoint' => 'https://push.example.test/subscriptions/remove-me',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Push subscription berhasil dihapus.');

        $this->assertDatabaseMissing('reseller_push_subscriptions', [
            'user_id' => $owner->id,
            'endpoint' => 'https://push.example.test/subscriptions/remove-me',
        ]);

        $this->assertDatabaseHas('reseller_push_subscriptions', [
            'user_id' => $otherUser->id,
            'endpoint' => 'https://push.example.test/subscriptions/keep-me',
        ]);
    }

    private function createResellerIntegration(User $user): ResellerIntegration
    {
        return ResellerIntegration::create([
            'user_id' => $user->id,
            'integration_type' => 'provider',
            'integration_code' => 'vip',
            'mode' => 'live',
            'credential_source' => 'global',
            'is_active' => true,
        ]);
    }

    private function createResellerUser(
        string $username = 'reseller-user',
        string $email = 'reseller-user@example.com'
    ): User {
        return User::create([
            'name' => 'Reseller User',
            'username' => $username,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => 'Gold',
            'balance' => 0,
            'point_balance' => 0,
            'email_verified_at' => now(),
        ]);
    }
}

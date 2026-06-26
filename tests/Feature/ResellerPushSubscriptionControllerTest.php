<?php

namespace Tests\Feature;

use App\Models\ResellerIntegration;
use App\Models\ResellerPushSubscription;
use App\Models\User;
use App\Services\ResellerWebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class ResellerPushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createResellerUser(): User
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        ResellerIntegration::create([
            'user_id' => $user->id,
            'integration_code' => 'TEST-PUSH-001',
            'is_active' => true,
            'mode' => 'live',
        ]);

        return $user;
    }

    public function test_reseller_can_store_push_subscription(): void
    {
        config([
            'services.webpush.vapid.public_key' => 'BEl6VapidPublicKeyExample1234567890',
            'services.webpush.vapid.private_key' => 'private-key',
            'services.webpush.vapid.subject' => 'mailto:test@example.com',
        ]);

        $user = $this->createResellerUser();

        $response = $this->actingAs($user)->postJson('/id/reseller/push-subscriptions', [
            'subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-subscription',
                'keys' => [
                    'p256dh' => 'public-key-value',
                    'auth' => 'auth-token-value',
                ],
                'contentEncoding' => 'aes128gcm',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Push subscription berhasil disimpan.');

        $this->assertDatabaseHas('reseller_push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-subscription',
            'public_key' => 'public-key-value',
            'auth_token' => 'auth-token-value',
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function test_push_subscription_validation_errors_are_returned(): void
    {
        $user = $this->createResellerUser();

        $response = $this->actingAs($user)->postJson('/id/reseller/push-subscriptions', [
            'subscription' => [
                'endpoint' => 'invalid-endpoint',
                'keys' => [
                    'p256dh' => '',
                    'auth' => '',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'subscription.endpoint',
                'subscription.keys.p256dh',
                'subscription.keys.auth',
            ]);
    }

    public function test_reseller_can_delete_owned_push_subscription(): void
    {
        $user = $this->createResellerUser();

        ResellerPushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://example.com/push/delete-me',
            'public_key' => 'public-key-value',
            'auth_token' => 'auth-token-value',
            'content_encoding' => 'aes128gcm',
        ]);

        $response = $this->actingAs($user)->deleteJson('/id/reseller/push-subscriptions', [
            'endpoint' => 'https://example.com/push/delete-me',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Push subscription berhasil dihapus.');

        $this->assertDatabaseMissing('reseller_push_subscriptions', [
            'endpoint' => 'https://example.com/push/delete-me',
        ]);
    }

    public function test_non_owner_cannot_delete_other_users_push_subscription(): void
    {
        $owner = $this->createResellerUser();
        $otherUser = $this->createResellerUser();

        ResellerPushSubscription::create([
            'user_id' => $owner->id,
            'endpoint' => 'https://example.com/push/not-owned',
            'public_key' => 'public-key-value',
            'auth_token' => 'auth-token-value',
            'content_encoding' => 'aes128gcm',
        ]);

        $response = $this->actingAs($otherUser)->deleteJson('/id/reseller/push-subscriptions', [
            'endpoint' => 'https://example.com/push/not-owned',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Push subscription tidak ditemukan.');

        $this->assertDatabaseHas('reseller_push_subscriptions', [
            'endpoint' => 'https://example.com/push/not-owned',
            'user_id' => $owner->id,
        ]);
    }

    public function test_send_test_requires_existing_subscription(): void
    {
        config([
            'services.webpush.vapid.public_key' => 'BEl6VapidPublicKeyExample1234567890',
            'services.webpush.vapid.private_key' => 'private-key',
            'services.webpush.vapid.subject' => 'mailto:test@example.com',
        ]);

        $user = $this->createResellerUser();

        $response = $this->actingAs($user)->postJson('/id/reseller/push-subscriptions/test');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Belum ada device yang subscribe push notification.');
    }

    public function test_send_test_uses_current_reseller_subscriptions_only(): void
    {
        config([
            'services.webpush.vapid.public_key' => 'BEl6VapidPublicKeyExample1234567890',
            'services.webpush.vapid.private_key' => 'private-key',
            'services.webpush.vapid.subject' => 'mailto:test@example.com',
        ]);

        $user = $this->createResellerUser();
        $otherUser = $this->createResellerUser();

        $owned = ResellerPushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://example.com/push/owned',
            'public_key' => 'public-key-owned',
            'auth_token' => 'auth-token-owned',
            'content_encoding' => 'aes128gcm',
        ]);

        ResellerPushSubscription::create([
            'user_id' => $otherUser->id,
            'endpoint' => 'https://example.com/push/other',
            'public_key' => 'public-key-other',
            'auth_token' => 'auth-token-other',
            'content_encoding' => 'aes128gcm',
        ]);

        $mock = Mockery::mock(ResellerWebPushService::class);
        $mock->shouldReceive('isConfigured')->once()->andReturn(true);
        $mock->shouldReceive('sendTestNotification')
            ->once()
            ->with(Mockery::on(fn ($subscription) => $subscription->is($owned)))
            ->andReturn([
                'success' => true,
                'message' => 'Test push berhasil dikirim.',
                'remove_subscription' => false,
            ]);
        $this->app->instance(ResellerWebPushService::class, $mock);

        $response = $this->actingAs($user)->postJson('/id/reseller/push-subscriptions/test');

        $response->assertOk()
            ->assertJsonPath('message', 'Test push berhasil dikirim ke 1 device.')
            ->assertJsonPath('success_count', 1);
    }

    public function test_non_reseller_cannot_access_push_subscription_endpoints(): void
    {
        $nonReseller = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($nonReseller)->postJson('/id/reseller/push-subscriptions', [
            'subscription' => [
                'endpoint' => 'https://example.com/push/blocked',
                'keys' => [
                    'p256dh' => 'public-key-value',
                    'auth' => 'auth-token-value',
                ],
            ],
        ]);

        $response->assertRedirect(route('dashboard'));
    }
}

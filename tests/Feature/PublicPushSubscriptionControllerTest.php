<?php

namespace Tests\Feature;

use App\Models\PublicPushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_store_public_push_subscription(): void
    {
        $response = $this->postJson('/id/push-subscriptions', [
            'subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/public-subscription',
                'keys' => [
                    'p256dh' => 'public-key-value',
                    'auth' => 'auth-token-value',
                ],
                'contentEncoding' => 'aes128gcm',
            ],
            'device_label' => 'Android Chrome',
            'locale' => 'id-ID',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Push subscription berhasil disimpan.');

        $this->assertDatabaseHas('public_push_subscriptions', [
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/public-subscription'),
            'public_key' => 'public-key-value',
            'auth_token' => 'auth-token-value',
            'content_encoding' => 'aes128gcm',
            'device_label' => 'Android Chrome',
            'locale' => 'id-ID',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_is_attached_to_public_push_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/id/push-subscriptions', [
            'subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/member-subscription',
                'keys' => [
                    'p256dh' => 'member-public-key',
                    'auth' => 'member-auth-token',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('public_push_subscriptions', [
            'user_id' => $user->id,
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/member-subscription'),
        ]);
    }

    public function test_public_push_subscription_validation_errors_are_returned(): void
    {
        $response = $this->postJson('/id/push-subscriptions', [
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

    public function test_public_push_subscription_can_be_deactivated(): void
    {
        $subscription = PublicPushSubscription::create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/remove-subscription',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/remove-subscription'),
            'public_key' => 'public-key-value',
            'auth_token' => 'auth-token-value',
            'content_encoding' => 'aes128gcm',
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->deleteJson('/id/push-subscriptions', [
            'endpoint' => $subscription->endpoint,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Push subscription berhasil dihapus.');

        $subscription->refresh();

        $this->assertFalse($subscription->is_active);
        $this->assertNotNull($subscription->unsubscribed_at);
    }
}

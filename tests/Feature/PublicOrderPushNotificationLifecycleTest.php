<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\PublicPushNotificationDelivery;
use App\Models\PublicPushSubscription;
use App\Models\User;
use App\Services\PublicOrderPushNotificationService;
use App\Services\PublicWebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PublicOrderPushNotificationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_created_push_targets_only_order_user_subscription_and_dedupes(): void
    {
        $buyer = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Pembelian::factory()->create([
            'username' => $buyer->username,
            'order_id' => 'INV-PUSH-001',
        ]);

        $buyerSubscription = PublicPushSubscription::create([
            'user_id' => $buyer->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/buyer-subscription',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/buyer-subscription'),
            'public_key' => 'buyer-public-key',
            'auth_token' => 'buyer-auth-token',
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        PublicPushSubscription::create([
            'user_id' => $otherUser->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/other-subscription',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/other-subscription'),
            'public_key' => 'other-public-key',
            'auth_token' => 'other-auth-token',
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        $mock = Mockery::mock(PublicWebPushService::class);
        $mock->shouldReceive('sendToSubscription')
            ->once()
            ->with(Mockery::on(fn (PublicPushSubscription $subscription): bool => $subscription->is($buyerSubscription)), Mockery::on(function (array $payload): bool {
                return $payload['title'] === 'Pesanan berhasil dibuat'
                    && str_contains($payload['body'], 'INV-PUSH-001')
                    && str_contains($payload['url'], '/id/invoices/INV-PUSH-001');
            }))
            ->andReturn([
                'success' => true,
                'message' => 'Push berhasil dikirim.',
                'remove_subscription' => false,
            ]);

        $service = new PublicOrderPushNotificationService($mock);

        $first = $service->notifyOrderCreated($order);
        $second = $service->notifyOrderCreated($order);

        $this->assertSame(1, $first['sent']);
        $this->assertSame(0, $first['failed']);
        $this->assertSame(0, $first['skipped']);
        $this->assertSame(0, $second['sent']);
        $this->assertSame(1, $second['skipped']);

        $this->assertDatabaseHas('public_push_notification_deliveries', [
            'public_push_subscription_id' => $buyerSubscription->id,
            'order_id' => 'INV-PUSH-001',
            'event' => PublicOrderPushNotificationService::EVENT_ORDER_CREATED,
            'status' => 'sent',
        ]);

        $this->assertSame(1, PublicPushNotificationDelivery::query()->count());
    }

    public function test_guest_order_push_can_target_session_subscription(): void
    {
        $order = Pembelian::factory()->create([
            'username' => 'guest_user_without_account',
            'order_id' => 'INV-GUEST-PUSH-001',
        ]);
        $sessionId = 'guest-session-id';

        $subscription = PublicPushSubscription::create([
            'session_id_hash' => hash('sha256', $sessionId),
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/guest-subscription',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/guest-subscription'),
            'public_key' => 'guest-public-key',
            'auth_token' => 'guest-auth-token',
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        $mock = Mockery::mock(PublicWebPushService::class);
        $mock->shouldReceive('sendToSubscription')
            ->once()
            ->with(Mockery::on(fn (PublicPushSubscription $target): bool => $target->is($subscription)), Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'message' => 'Push berhasil dikirim.',
                'remove_subscription' => false,
            ]);

        $service = new PublicOrderPushNotificationService($mock);
        $result = $service->notifyOrderCreated($order, $sessionId);

        $this->assertSame(1, $result['sent']);
        $this->assertDatabaseHas('public_push_notification_deliveries', [
            'public_push_subscription_id' => $subscription->id,
            'order_id' => 'INV-GUEST-PUSH-001',
            'event' => PublicOrderPushNotificationService::EVENT_ORDER_CREATED,
            'status' => 'sent',
        ]);
    }

    public function test_guest_payment_success_push_reuses_order_created_delivery_target_and_dedupes(): void
    {
        $order = Pembelian::factory()->create([
            'username' => 'guest_user_without_account',
            'order_id' => 'INV-GUEST-PAID-001',
        ]);

        $subscription = PublicPushSubscription::create([
            'session_id_hash' => hash('sha256', 'original-order-session'),
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/guest-paid-subscription',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/guest-paid-subscription'),
            'public_key' => 'guest-paid-public-key',
            'auth_token' => 'guest-paid-auth-token',
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        PublicPushNotificationDelivery::create([
            'public_push_subscription_id' => $subscription->id,
            'order_id' => 'INV-GUEST-PAID-001',
            'event' => PublicOrderPushNotificationService::EVENT_ORDER_CREATED,
            'endpoint_hash' => $subscription->endpoint_hash,
            'status' => 'sent',
            'payload' => ['title' => 'Pesanan berhasil dibuat'],
        ]);

        $mock = Mockery::mock(PublicWebPushService::class);
        $mock->shouldReceive('sendToSubscription')
            ->once()
            ->with(Mockery::on(fn (PublicPushSubscription $target): bool => $target->is($subscription)), Mockery::on(function (array $payload): bool {
                return $payload['title'] === 'Pembayaran berhasil'
                    && str_contains($payload['body'], 'INV-GUEST-PAID-001')
                    && str_contains($payload['url'], '/id/invoices/INV-GUEST-PAID-001');
            }))
            ->andReturn([
                'success' => true,
                'message' => 'Push berhasil dikirim.',
                'remove_subscription' => false,
            ]);

        $service = new PublicOrderPushNotificationService($mock);

        $first = $service->notifyPaymentSuccess($order);
        $second = $service->notifyPaymentSuccess($order);

        $this->assertSame(1, $first['sent']);
        $this->assertSame(0, $second['sent']);
        $this->assertSame(1, $second['skipped']);

        $this->assertDatabaseHas('public_push_notification_deliveries', [
            'public_push_subscription_id' => $subscription->id,
            'order_id' => 'INV-GUEST-PAID-001',
            'event' => PublicOrderPushNotificationService::EVENT_PAYMENT_SUCCESS,
            'status' => 'sent',
        ]);
    }

    public function test_order_success_push_is_sent_when_order_status_transitions_to_success(): void
    {
        $buyer = User::factory()->create();
        $order = Pembelian::factory()->create([
            'username' => $buyer->username,
            'order_id' => 'INV-SUCCESS-PUSH-001',
            'status' => 'Proses',
        ]);

        $subscription = PublicPushSubscription::create([
            'user_id' => $buyer->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/success-subscription',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/success-subscription'),
            'public_key' => 'success-public-key',
            'auth_token' => 'success-auth-token',
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        $mock = Mockery::mock(PublicWebPushService::class);
        $mock->shouldReceive('sendToSubscription')
            ->once()
            ->with(Mockery::on(fn (PublicPushSubscription $target): bool => $target->is($subscription)), Mockery::on(function (array $payload): bool {
                return $payload['title'] === 'Pesanan berhasil'
                    && str_contains($payload['body'], 'INV-SUCCESS-PUSH-001')
                    && str_contains($payload['url'], '/id/invoices/INV-SUCCESS-PUSH-001');
            }))
            ->andReturn([
                'success' => true,
                'message' => 'Push berhasil dikirim.',
                'remove_subscription' => false,
            ]);
        $this->app->instance(PublicWebPushService::class, $mock);

        $order->update(['status' => 'Sukses']);
        $order->update(['status' => 'Sukses']);

        $this->assertDatabaseHas('public_push_notification_deliveries', [
            'public_push_subscription_id' => $subscription->id,
            'order_id' => 'INV-SUCCESS-PUSH-001',
            'event' => PublicOrderPushNotificationService::EVENT_ORDER_SUCCESS,
            'status' => 'sent',
        ]);

        $this->assertSame(1, PublicPushNotificationDelivery::query()
            ->where('order_id', 'INV-SUCCESS-PUSH-001')
            ->where('event', PublicOrderPushNotificationService::EVENT_ORDER_SUCCESS)
            ->count());
    }
}



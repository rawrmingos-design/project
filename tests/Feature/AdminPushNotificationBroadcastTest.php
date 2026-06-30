<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\PushNotificationBroadcast;
use App\Models\PublicPushSubscription;
use App\Models\User;
use App\Services\PublicWebPushService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AdminPushNotificationBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_trigger_push_broadcast_from_filament_page(): void
    {
        config([
            'services.webpush.vapid.public_key' => 'BEl6VapidPublicKeyExample1234567890',
            'services.webpush.vapid.private_key' => 'private-key',
            'services.webpush.vapid.subject' => 'mailto:test@example.com',
        ]);

        $admin = User::factory()->create(['role' => 'Admin']);
        PublicPushSubscription::create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/public-admin-subscription',
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/public-admin-subscription'),
            'public_key' => 'public-key-value',
            'auth_token' => 'auth-token-value',
            'subscribed_at' => now(),
            'is_active' => true,
        ]);

        $mock = Mockery::mock(PublicWebPushService::class);
        $mock->shouldReceive('isConfigured')->once()->andReturn(true);
        $mock->shouldReceive('broadcastToActiveSubscriptions')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                return $payload['title'] === 'Promo Baru'
                    && $payload['body'] === 'Diskon top up malam ini.'
                    && $payload['url'] === 'https://example.com/id';
            }))
            ->andReturn([
                'success_count' => 1,
                'failed_count' => 0,
                'failed_messages' => [],
                'total' => 1,
            ]);
        $this->app->instance(PublicWebPushService::class, $mock);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(PushNotificationBroadcast::class)
            ->fillForm([
                'title' => 'Promo Baru',
                'body' => 'Diskon top up malam ini.',
                'target_url' => 'https://example.com/id',
                'icon_url' => 'https://example.com/icon.png',
            ])
            ->call('send')
            ->assertHasNoFormErrors();
    }
}

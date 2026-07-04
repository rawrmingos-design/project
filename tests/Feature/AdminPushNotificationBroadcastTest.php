<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\PushNotificationBroadcast;
use App\Jobs\SendPublicPushBroadcastJob;
use App\Models\PublicPushBroadcast;
use App\Models\PublicPushSubscription;
use App\Models\User;
use App\Services\PublicWebPushService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AdminPushNotificationBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_queue_push_broadcast_from_filament_page(): void
    {
        config([
            'services.webpush.vapid.public_key' => 'BEl6VapidPublicKeyExample1234567890',
            'services.webpush.vapid.private_key' => 'private-key',
            'services.webpush.vapid.subject' => 'mailto:test@example.com',
        ]);

        Queue::fake();

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
        $mock->shouldNotReceive('broadcastToActiveSubscriptions');
        $this->app->instance(PublicWebPushService::class, $mock);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(PushNotificationBroadcast::class)
            ->fillForm([
                'title' => 'Promo Baru',
                'body' => 'Diskon top up malam ini.',
                'target_url' => 'https://example.com/id',
                'send_mode' => 'now',
                'scheduled_at' => null,
            ])
            ->call('send')
            ->assertHasNoFormErrors();

        $broadcast = PublicPushBroadcast::query()->first();

        $this->assertNotNull($broadcast);
        $this->assertSame('queued', $broadcast->status);
        $this->assertSame('now', $broadcast->send_mode);
        $this->assertSame('Promo Baru', $broadcast->title);
        $this->assertSame('https://example.com/id', $broadcast->target_url);

        Queue::assertPushed(SendPublicPushBroadcastJob::class, fn (SendPublicPushBroadcastJob $job): bool => $job->broadcastId === $broadcast->id);
    }
}

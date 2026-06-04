<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ResellerIntegration;
use App\Notifications\ResellerSecurityNotification;
use Illuminate\Support\Facades\Event;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'null']);
    }

    private function createReseller()
    {
        $user = User::factory()->create(['role' => 'Member']);
        ResellerIntegration::create([
            'user_id' => $user->id,
            'integration_code' => 'TEST-INT-02',
            'is_active' => true,
            'mode' => 'live',
        ]);
        return $user;
    }

    public function test_reseller_can_fetch_notifications()
    {
        $user = $this->createReseller();
        $user->notify(new ResellerSecurityNotification('Test Title', 'Test Message'));

        $response = $this->actingAs($user)->getJson('/id/reseller/notifications');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'type', 'data', 'read_at', 'created_at']
                     ]
                 ]);
                 
        $this->assertCount(1, $response->json('data'));
    }

    public function test_reseller_can_mark_notification_as_read()
    {
        $user = $this->createReseller();
        $user->notify(new ResellerSecurityNotification('Test Title', 'Test Message'));

        $notification = $user->unreadNotifications->first();
        
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)->postJson('/id/reseller/notifications/' . $notification->id . '/read');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Notification marked as read.']);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_reseller_can_mark_all_notifications_as_read()
    {
        $user = $this->createReseller();
        $user->notify(new ResellerSecurityNotification('Test 1', 'Message 1'));
        $user->notify(new ResellerSecurityNotification('Test 2', 'Message 2'));

        $this->assertEquals(2, $user->unreadNotifications()->count());

        $response = $this->actingAs($user)->postJson('/id/reseller/notifications/read-all');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'All notifications marked as read.']);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_reseller_can_get_unread_count()
    {
        $user = $this->createReseller();
        $user->notify(new ResellerSecurityNotification('Test 1', 'Message 1'));
        
        $response = $this->actingAs($user)->getJson('/id/reseller/notifications/unread-count');
        
        $response->assertStatus(200)
                 ->assertJson(['count' => 1]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PublicWhatsappLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_whatsapp_link_status_or_create_a_challenge(): void
    {
        $this->getJson('/id/settings/whatsapp/status')->assertUnauthorized();
        $this->postJson('/id/settings/whatsapp/link', ['no_wa' => '081234567890'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_challenge_and_notification_uses_canonical_number(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['no_wa' => null]);
        $message = null;
        $target = null;

        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$message, &$target): void {
            $mock->shouldReceive('sendMessage')->once()->andReturnUsing(function (string $phone, string $content) use (&$message, &$target): array {
                $target = $phone;
                $message = $content;

                return ['success' => true];
            });
        });

        $response = $this->actingAs($user)->postJson('/id/settings/whatsapp/link', [
            'no_wa' => '+62 812-3456-7890',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.number', '6281234567890')
            ->assertJsonPath('data.instruction', 'Kirim LINK <kode> dari nomor WhatsApp ini.')
            ->assertJsonMissingPath('data.challenge.code_hash');

        $this->assertSame('6281234567890', $target);
        $this->assertStringContainsString('LINK ', (string) $message);
        $this->assertDatabaseHas('whatsapp_link_challenges', [
            'user_id' => $user->id,
            'whatsapp_number' => '6281234567890',
        ]);
    }

    public function test_duplicate_verified_number_returns_safe_error(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);
        /** @var User $user */
        $user = User::factory()->create(['no_wa' => null]);

        $this->actingAs($user)
            ->postJson('/id/settings/whatsapp/link', ['no_wa' => '081234567890'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'WHATSAPP_NUMBER_UNAVAILABLE')
            ->assertJsonMissing(['email' => '']);
    }

    public function test_authenticated_user_can_revoke_pending_challenge_and_unlink_with_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);
        $challenge = app(\App\Services\Whatsapp\WhatsappLinkService::class)
            ->createChallenge($user, '081234567890')['challenge'];

        $this->actingAs($user)
            ->postJson('/id/settings/whatsapp/revoke')
            ->assertOk();

        $this->assertNotNull($challenge->fresh()->revoked_at);

        $this->actingAs($user)
            ->postJson('/id/settings/whatsapp/unlink', ['current_password' => 'secret-password'])
            ->assertOk();

        $this->assertNull($user->fresh()->whatsapp_verified_at);
    }

    public function test_unlink_rejects_wrong_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/id/settings/whatsapp/unlink', ['current_password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'INVALID_PASSWORD');
    }
}

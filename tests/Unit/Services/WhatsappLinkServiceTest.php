<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Whatsapp\WhatsappLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WhatsappLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenge_is_created_with_hashed_code_and_normalized_number(): void
    {
        $user = User::factory()->create(['no_wa' => null]);

        $result = app(WhatsappLinkService::class)->createChallenge($user, '+62 812-3456-7890');
        $challenge = $result['challenge'];

        $this->assertSame('created', $result['status']);
        $this->assertSame('6281234567890', $challenge->whatsapp_number);
        $this->assertNotSame($result['code'], $challenge->code_hash);
        $this->assertTrue(Hash::check($result['code'], $challenge->code_hash));
        $this->assertTrue($challenge->expires_at->isFuture());
    }

    public function test_new_challenge_revokes_previous_active_challenge(): void
    {
        $user = User::factory()->create(['no_wa' => null]);
        $service = app(WhatsappLinkService::class);

        $first = $service->createChallenge($user, '081234567890');
        $second = $service->createChallenge($user, '081234567890');

        $this->assertNotNull($first['challenge']->fresh()->revoked_at);
        $this->assertNull($second['challenge']->fresh()->revoked_at);
    }

    public function test_correct_code_verifies_user_once(): void
    {
        $user = User::factory()->create(['no_wa' => null]);
        $service = app(WhatsappLinkService::class);
        $created = $service->createChallenge($user, '081234567890');

        $result = $service->verifyChallenge('6281234567890', $created['code']);

        $this->assertSame('verified', $result['status']);
        $this->assertSame('6281234567890', $user->fresh()->no_wa);
        $this->assertNotNull($user->fresh()->whatsapp_verified_at);
        $this->assertNotNull($created['challenge']->fresh()->consumed_at);
        $this->assertSame('not_found', $service->verifyChallenge('081234567890', $created['code'])['reason']);
    }

    public function test_wrong_code_increments_attempts_and_max_attempts_revokes_challenge(): void
    {
        config(['services.fonnte.link_challenge_max_attempts' => 2]);
        $user = User::factory()->create(['no_wa' => null]);
        $service = app(WhatsappLinkService::class);
        $created = $service->createChallenge($user, '081234567890');

        $this->assertSame('invalid_code', $service->verifyChallenge('081234567890', '000000')['reason']);
        $this->assertSame('max_attempts', $service->verifyChallenge('081234567890', '000000')['reason']);
        $this->assertNotNull($created['challenge']->fresh()->revoked_at);
    }

    public function test_expired_challenge_is_revoked_and_rejected(): void
    {
        $user = User::factory()->create(['no_wa' => null]);
        $service = app(WhatsappLinkService::class);
        $created = $service->createChallenge($user, '081234567890');
        $created['challenge']->forceFill(['expires_at' => now()->subMinute()])->save();

        $result = $service->verifyChallenge('081234567890', $created['code']);

        $this->assertSame('expired', $result['reason']);
        $this->assertNotNull($created['challenge']->fresh()->revoked_at);
    }

    public function test_number_already_verified_for_another_user_is_rejected(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);
        $other = User::factory()->create(['no_wa' => null]);

        $this->expectException(ValidationException::class);
        app(WhatsappLinkService::class)->createChallenge($other, '081234567890');
    }

    public function test_unlink_clears_verification_and_revokes_challenges(): void
    {
        $user = User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);
        $service = app(WhatsappLinkService::class);
        $created = $service->createChallenge($user, '081234567890');

        $service->unlink($user);

        $this->assertNull($user->fresh()->whatsapp_verified_at);
        $this->assertNotNull($created['challenge']->fresh()->revoked_at);
    }
}

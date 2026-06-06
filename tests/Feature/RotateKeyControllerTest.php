<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ResellerIntegration;
use App\Notifications\ResellerSecurityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class RotateKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createResellerUserWith2FA($with2fa = true)
    {
        $google2fa = new Google2FA();
        $secret = $with2fa ? $google2fa->generateSecretKey() : null;

        $user = User::factory()->create([
            'role' => 'Member',
            'two_factor_secret' => $secret,
        ]);
        
        ResellerIntegration::factory()->create([
            'user_id' => $user->id,
            'integration_code' => 'TEST-INT-02',
            'is_active' => true,
            'mode' => 'live',
            'api_key_hash' => hash('sha256', 'old_live_key'),
            'api_key_hint' => 'old_...hint',
            'api_key_prefix' => 'old_live',
        ]);

        ResellerIntegration::factory()->create([
            'user_id' => $user->id,
            'integration_code' => 'TEST-INT-03',
            'is_active' => true,
            'mode' => 'sandbox',
            'api_key_hash' => hash('sha256', 'old_sandbox_key'),
            'api_key_hint' => 'old_...sbx',
            'api_key_prefix' => 'old_sbx',
        ]);

        return $user;
    }

    private function getValidTotpCode($secret)
    {
        $google2fa = new Google2FA();
        return $google2fa->getCurrentOtp($secret);
    }

    public function test_reseller_cannot_rotate_without_2fa_enabled()
    {
        $user = $this->createResellerUserWith2FA(false);
        
        $response = $this->actingAs($user)->postJson('/id/reseller/credentials/rotate-live', [
            'totp_code' => '123456'
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Anda harus mengaktifkan 2FA terlebih dahulu di Pengaturan.');
    }

    public function test_reseller_cannot_rotate_with_invalid_totp()
    {
        $user = $this->createResellerUserWith2FA();

        $response = $this->actingAs($user)->postJson('/id/reseller/credentials/rotate-live', [
            'totp_code' => '000000' // Invalid
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Kode 2FA tidak valid.');
    }

    public function test_reseller_can_rotate_live_api_key_and_receive_plain_text_once()
    {
        Notification::fake();

        $user = $this->createResellerUserWith2FA();
        $validCode = $this->getValidTotpCode($user->two_factor_secret);

        $response = $this->actingAs($user)->postJson('/id/reseller/credentials/rotate-live', [
            'totp_code' => $validCode
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Live API Key berhasil dirotasi.')
                 ->assertJsonStructure(['raw_key', 'hint']);

        $rawKey = $response->json('raw_key');
        $hint   = $response->json('hint');

        // raw_key must be prefixed with 'live_'
        $this->assertStringStartsWith('egylive_', $rawKey);
        // hint must be derived from raw key (last 6 chars), not from hash
        $this->assertStringStartsWith('...', $hint);
        $this->assertStringEndsWith(substr($rawKey, -6), $hint);

        // DB: hint matches, key is hashed, prefix set from raw key
        $integration = $user->resellerIntegrations()->where('mode', 'live')->first();
        $this->assertEquals($hint, $integration->api_key_hint);
        $this->assertEquals(substr($rawKey, 0, 8), $integration->api_key_prefix);
        $this->assertEquals(hash('sha256', $rawKey), $integration->api_key_hash);

        // raw key NOT stored in plain text
        $this->assertNotEquals($rawKey, $integration->api_key_hash);

        // Security notification was sent
        Notification::assertSentTo($user, ResellerSecurityNotification::class);
    }

    public function test_reseller_can_rotate_sandbox_api_key_and_receive_plain_text_once()
    {
        Notification::fake();

        $user = $this->createResellerUserWith2FA();
        $validCode = $this->getValidTotpCode($user->two_factor_secret);

        $response = $this->actingAs($user)->postJson('/id/reseller/credentials/rotate-sandbox', [
            'totp_code' => $validCode
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('message', 'Sandbox API Key berhasil dirotasi.')
                 ->assertJsonStructure(['raw_key', 'hint']);

        $rawKey = $response->json('raw_key');
        $hint   = $response->json('hint');

        $this->assertStringStartsWith('egysbx_', $rawKey);
        $this->assertStringStartsWith('...', $hint);

        $integration = $user->resellerIntegrations()->where('mode', 'sandbox')->first();
        $this->assertEquals($hint, $integration->api_key_hint);
        $this->assertEquals(substr($rawKey, 0, 8), $integration->api_key_prefix);
        $this->assertEquals(hash('sha256', $rawKey), $integration->api_key_hash);

        Notification::assertSentTo($user, ResellerSecurityNotification::class);
    }

    public function test_unauthenticated_user_cannot_rotate_live_key()
    {
        $response = $this->postJson('/id/reseller/credentials/rotate-live', [
            'totp_code' => '123456'
        ]);

        // Unauthenticated → redirected or 401
        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_rotate_sandbox_key()
    {
        $response = $this->postJson('/id/reseller/credentials/rotate-sandbox', [
            'totp_code' => '123456'
        ]);

        $response->assertStatus(401);
    }

    public function test_sandbox_cannot_rotate_without_2fa_enabled()
    {
        $user = $this->createResellerUserWith2FA(false);

        $response = $this->actingAs($user)->postJson('/id/reseller/credentials/rotate-sandbox', [
            'totp_code' => '123456'
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Anda harus mengaktifkan 2FA terlebih dahulu di Pengaturan.');
    }

    public function test_sandbox_cannot_rotate_with_invalid_totp()
    {
        $user = $this->createResellerUserWith2FA();

        $response = $this->actingAs($user)->postJson('/id/reseller/credentials/rotate-sandbox', [
            'totp_code' => '000000'
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('message', 'Kode 2FA tidak valid.');
    }
}

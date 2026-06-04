<?php

namespace Tests\Feature\Reseller;

use App\Models\Pembelian;
use App\Models\ResellerCallbackDelivery;
use App\Models\ResellerCallbackProfile;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests for POST /id/reseller/callbacks/{delivery}/resend
 *
 * Verifies:
 *   - Auth isolation (cannot resend another user's delivery)
 *   - Already-delivered guard (409-equivalent redirect with info flash)
 *   - Max-attempt guard (attempt_count >= 4 → error flash)
 *   - Successful resend → delivery status updated to 'delivered'
 *   - Failed resend (HTTP 500 from endpoint) → delivery stays 'failed'
 *   - Unauthenticated access → redirect to login
 */
class CallbackResendTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeIntegration(User $user): ResellerIntegration
    {
        return ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-RESEND-' . $user->id,
            'mode'             => 'live',
            'is_active'        => true,
            'allowed_ips'      => [],
        ]);
    }

    private function makeProfile(ResellerIntegration $integration): ResellerCallbackProfile
    {
        $profile = ResellerCallbackProfile::create([
            'reseller_integration_id' => $integration->id,
            'callback_url'            => 'https://example.com/webhook',
            'webhook_secret'          => 'test-secret-for-resend',
            'signature_header'        => 'X-Callback-Signature',
            'signing_algorithm'       => 'sha256',
            'version'                 => 1,
            'is_enabled'              => true,
            'retry_enabled'           => false,
            'timeout_ms'              => 5000,
        ]);

        return $profile->fresh();
    }

    private function makeDelivery(
        User $user,
        ResellerIntegration $integration,
        ResellerCallbackProfile $profile,
        string $status = 'failed',
        int $attemptCount = 1
    ): ResellerCallbackDelivery {
        return ResellerCallbackDelivery::create([
            'user_id'                    => $user->id,
            'reseller_integration_id'    => $integration->id,
            'reseller_callback_profile_id' => $profile->id,
            'pembelian_id'               => null,
            'environment'                => 'live',
            'event_name'                 => 'h2h.order.updated',
            'order_id'                   => 'INV-RESEND-TEST-001',
            'reference_number'           => 'REF-001',
            'callback_url'               => 'https://example.com/webhook',
            'signature_algorithm'        => 'sha256',
            'payload'                    => ['event' => 'h2h.order.updated', 'invoiceNumber' => 'INV-RESEND-TEST-001'],
            'attempt_count'              => $attemptCount,
            'status'                     => $status,
            'last_attempted_at'          => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->post('/id/reseller/callbacks/999/resend');

        $response->assertRedirectContains('sign-in');
    }

    public function test_resend_another_users_delivery_returns_not_found_flash(): void
    {
        $owner   = $this->makeUser();
        $attacker = $this->makeUser();

        $integration = $this->makeIntegration($owner);
        $profile     = $this->makeProfile($integration);
        $delivery    = $this->makeDelivery($owner, $integration, $profile, 'failed', 1);

        $response = $this->actingAs($attacker)
            ->post("/id/reseller/callbacks/{$delivery->id}/resend");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Delivery should not have been touched
        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertSame(1, $delivery->fresh()->attempt_count);
    }

    public function test_resend_already_delivered_returns_info_flash(): void
    {
        $user        = $this->makeUser();
        $integration = $this->makeIntegration($user);
        $profile     = $this->makeProfile($integration);
        $delivery    = $this->makeDelivery($user, $integration, $profile, 'delivered', 1);

        $response = $this->actingAs($user)
            ->post("/id/reseller/callbacks/{$delivery->id}/resend");

        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_resend_maxed_out_delivery_returns_error_flash(): void
    {
        $user        = $this->makeUser();
        $integration = $this->makeIntegration($user);
        $profile     = $this->makeProfile($integration);
        // attempt_count = 4 means max reached
        $delivery = $this->makeDelivery($user, $integration, $profile, 'failed', 4);

        $response = $this->actingAs($user)
            ->post("/id/reseller/callbacks/{$delivery->id}/resend");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Count should not have been incremented
        $this->assertSame(4, $delivery->fresh()->attempt_count);
    }

    public function test_successful_resend_updates_delivery_to_delivered(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['ok' => true], 200),
        ]);

        $user        = $this->makeUser();
        $integration = $this->makeIntegration($user);
        $profile     = $this->makeProfile($integration);

        // Create a Pembelian so buildPayload() has something to work with
        $pembelian = Pembelian::create([
            'order_id'            => 'INV-RESEND-TEST-001',
            'username'            => $user->username,
            'user_id'             => '100',
            'zone'                => '200',
            'nickname'            => 'Tester',
            'layanan'             => 'Test Service',
            'harga'               => 10000,
            'profit'              => 500,
            'status'              => 'Sukses',
            'tipe_transaksi'      => 'game',
            'reseller_integration_id' => $integration->id,
        ]);

        $delivery = ResellerCallbackDelivery::create([
            'user_id'                      => $user->id,
            'reseller_integration_id'      => $integration->id,
            'reseller_callback_profile_id' => $profile->id,
            'pembelian_id'                 => $pembelian->id,
            'environment'                  => 'live',
            'event_name'                   => 'h2h.order.updated',
            'order_id'                     => 'INV-RESEND-TEST-001',
            'reference_number'             => '',
            'callback_url'                 => 'https://example.com/webhook',
            'signature_algorithm'          => 'sha256',
            'payload'                      => ['event' => 'h2h.order.updated'],
            'attempt_count'                => 1,
            'status'                       => 'failed',
            'last_attempted_at'            => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)
            ->post("/id/reseller/callbacks/{$delivery->id}/resend");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $delivery->fresh();
        $this->assertSame('delivered', $fresh->status);
        $this->assertSame(2, $fresh->attempt_count);
        $this->assertNotNull($fresh->delivered_at);

        Http::assertSent(fn ($req) => str_contains($req->url(), 'example.com/webhook'));
    }

    public function test_failed_resend_http_error_stays_failed(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('Internal Server Error', 500),
        ]);

        $user        = $this->makeUser();
        $integration = $this->makeIntegration($user);
        $profile     = $this->makeProfile($integration);

        $pembelian = Pembelian::create([
            'order_id'            => 'INV-FAIL-RESEND-001',
            'username'            => $user->username,
            'user_id'             => '100',
            'zone'                => '200',
            'nickname'            => 'Tester',
            'layanan'             => 'Test Service',
            'harga'               => 10000,
            'profit'              => 500,
            'status'              => 'Gagal',
            'tipe_transaksi'      => 'game',
            'reseller_integration_id' => $integration->id,
        ]);

        $delivery = ResellerCallbackDelivery::create([
            'user_id'                      => $user->id,
            'reseller_integration_id'      => $integration->id,
            'reseller_callback_profile_id' => $profile->id,
            'pembelian_id'                 => $pembelian->id,
            'environment'                  => 'live',
            'event_name'                   => 'h2h.order.updated',
            'order_id'                     => 'INV-FAIL-RESEND-001',
            'reference_number'             => '',
            'callback_url'                 => 'https://example.com/webhook',
            'signature_algorithm'          => 'sha256',
            'payload'                      => ['event' => 'h2h.order.updated'],
            'attempt_count'                => 2,
            'status'                       => 'failed',
            'last_attempted_at'            => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)
            ->post("/id/reseller/callbacks/{$delivery->id}/resend");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $fresh = $delivery->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertSame(3, $fresh->attempt_count); // incremented before attempt
        $this->assertSame(500, $fresh->last_response_status);
    }

    public function test_attempt_count_is_incremented_on_each_resend(): void
    {
        Http::fake(['https://example.com/webhook' => Http::response('ok', 200)]);

        $user        = $this->makeUser();
        $integration = $this->makeIntegration($user);
        $profile     = $this->makeProfile($integration);

        $pembelian = Pembelian::create([
            'order_id'            => 'INV-COUNT-TEST',
            'username'            => $user->username,
            'user_id'             => '100',
            'zone'                => '',
            'nickname'            => 'Tester',
            'layanan'             => 'Test',
            'harga'               => 5000,
            'profit'              => 100,
            'status'              => 'Sukses',
            'tipe_transaksi'      => 'game',
            'reseller_integration_id' => $integration->id,
        ]);

        $delivery = ResellerCallbackDelivery::create([
            'user_id'                      => $user->id,
            'reseller_integration_id'      => $integration->id,
            'reseller_callback_profile_id' => $profile->id,
            'pembelian_id'                 => $pembelian->id,
            'environment'                  => 'live',
            'event_name'                   => 'h2h.order.updated',
            'order_id'                     => 'INV-COUNT-TEST',
            'reference_number'             => '',
            'callback_url'                 => 'https://example.com/webhook',
            'signature_algorithm'          => 'sha256',
            'payload'                      => [],
            'attempt_count'                => 1,
            'status'                       => 'failed',
            'last_attempted_at'            => now(),
        ]);

        $this->actingAs($user)->post("/id/reseller/callbacks/{$delivery->id}/resend");

        $this->assertSame(2, $delivery->fresh()->attempt_count);
    }
}

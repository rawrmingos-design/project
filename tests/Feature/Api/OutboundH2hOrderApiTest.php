<?php

namespace Tests\Feature\Api;

use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\ResellerCallbackDelivery;
use App\Models\ResellerCallbackProfile;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OutboundH2hOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_requires_reseller_integration_header(): void
    {
        $user = User::factory()->create([
            'api_key' => 'token-live-no-header',
            'balance' => 50_000,
            'no_wa' => '081234567890',
        ]);

        $this->createManualLayanan();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->api_key,
        ])->postJson('/api/v1/order', [
            'code' => 'MANUAL-MVP-001',
            'referenceNumber' => 'REF-NO-HEADER-001',
            'data' => '12345678|2001',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'X-Reseller-Integration-Code header is required')
            ->assertJsonPath('error_code', 'INTEGRATION_CODE_REQUIRED');
    }

    public function test_order_rejects_foreign_or_inactive_integration_code(): void
    {
        $user = User::factory()->create([
            'api_key' => 'token-live-owner-a',
            'balance' => 50_000,
            'no_wa' => '081234567890',
        ]);
        $otherUser = User::factory()->create([
            'api_key' => 'token-live-owner-b',
            'balance' => 50_000,
            'no_wa' => '081234567891',
        ]);

        $integration = ResellerIntegration::query()->create([
            'user_id' => $otherUser->getKey(),
            'integration_code' => 'other-live-01',
            'mode' => 'live',
            'is_active' => true,
        ]);

        $this->createManualLayanan();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->api_key,
            'X-Reseller-Integration-Code' => $integration->integration_code,
        ])->postJson('/api/v1/order', [
            'code' => 'MANUAL-MVP-001',
            'referenceNumber' => 'REF-FOREIGN-001',
            'data' => '12345678|2001',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Invalid or inactive reseller integration code')
            ->assertJsonPath('error_code', 'INVALID_INTEGRATION_CODE');
    }

    public function test_valid_live_order_stores_integration_and_sends_signed_callback(): void
    {
        Http::fake([
            'https://client.example/callback' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'api_key' => 'token-live-valid',
            'balance' => 50_000,
            'no_wa' => '081234567890',
        ]);
        $integration = $this->createIntegrationWithProfile($user);
        $this->createManualLayanan();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $user->api_key,
            'X-Reseller-Integration-Code' => $integration->integration_code,
        ])->postJson('/api/v1/order', [
            'code' => 'MANUAL-MVP-001',
            'referenceNumber' => 'EXT-REF-VALID-001',
            'user_id' => '12345678',
            'zone_id' => '2001',
        ]);

        $response->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.status', 'Success');

        $invoiceNumber = $response->json('data.invoiceNumber');
        $pembelian = Pembelian::query()->where('order_id', $invoiceNumber)->firstOrFail();
        $delivery = ResellerCallbackDelivery::query()->where('pembelian_id', $pembelian->getKey())->firstOrFail();

        $this->assertSame($integration->getKey(), $pembelian->reseller_integration_id);
        $this->assertSame('reseller_h2h', $pembelian->traffic_source);
        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(200, $delivery->last_response_status);

        Http::assertSentCount(1);

        $recorded = Http::recorded();
        $request = $recorded->first()[0];
        $payload = json_decode($request->body(), true);
        $expectedSignature = hash_hmac('sha256', $request->body(), 'live-secret-001');

        $this->assertSame('https://client.example/callback', $request->url());
        $this->assertTrue($request->hasHeader('X-Callback-Event', 'h2h.order.updated'));
        $this->assertTrue($request->hasHeader('X-Callback-Version', '1'));
        $this->assertTrue($request->hasHeader('X-Callback-Signature', $expectedSignature));
        $this->assertSame($invoiceNumber, $payload['invoiceNumber'] ?? null);
        $this->assertSame('EXT-REF-VALID-001', $payload['referenceNumber'] ?? null);
        $this->assertSame('Manual MVP Service', $payload['productName'] ?? null);
        $this->assertSame('12345678', $payload['userId'] ?? null);
        $this->assertSame('2001', $payload['zoneId'] ?? null);
        $this->assertArrayNotHasKey('userData', $payload);
        $this->assertSame('Success', $payload['statusCode'] ?? null);
        $this->assertFalse($payload['sandbox'] ?? true);
        $this->assertSame('live', $payload['environment'] ?? null);
    }

    public function test_final_status_transition_creates_second_callback_and_unchanged_status_does_not(): void
    {
        Http::fake([
            'https://client.example/callback' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'api_key' => 'token-live-transition',
            'balance' => 50_000,
            'no_wa' => '081234567890',
        ]);
        $integration = $this->createIntegrationWithProfile($user);
        $layanan = $this->createManualLayanan();

        $pembelian = Pembelian::query()->create([
            'username' => $user->username,
            'reseller_integration_id' => $integration->getKey(),
            'order_id' => 'INV-H2H-MVP-TRANSITION-001',
            'user_id' => '12345678',
            'zone' => '2001',
            'layanan' => $layanan->layanan,
            'harga' => 10_000,
            'profit' => 1_000,
            'status' => 'Pending',
            'active_layanan_id' => $layanan->getKey(),
            'active_provider_code' => 'manual',
            'active_provider_sku' => $layanan->provider_id,
            'traffic_source' => 'reseller_h2h',
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::query()->create([
            'order_id' => $pembelian->order_id,
            'harga' => 10_000,
            'no_pembayaran' => 'SALDO',
            'no_pembeli' => $user->no_wa,
            'status' => 'Lunas',
            'metode' => 'SALDO',
            'reference' => 'EXT-REF-TRANSITION-001',
        ]);

        $this->assertSame(1, ResellerCallbackDelivery::query()->count());

        $pembelian->update([
            'status' => 'Sukses',
        ]);

        $this->assertSame(2, ResellerCallbackDelivery::query()->count());

        $latest = ResellerCallbackDelivery::query()->latest('id')->firstOrFail();
        $this->assertSame('Success', $latest->payload['statusCode']);

        $pembelian->status = 'Sukses';
        $pembelian->save();

        $this->assertSame(2, ResellerCallbackDelivery::query()->count());
    }

    public function test_failed_callback_response_is_logged(): void
    {
        Http::fake([
            'https://client.example/callback' => Http::response(['error' => 'upstream-fail'], 500),
        ]);

        $user = User::factory()->create([
            'api_key' => 'token-live-failed-callback',
            'balance' => 50_000,
            'no_wa' => '081234567890',
        ]);
        $integration = $this->createIntegrationWithProfile($user);
        $layanan = $this->createManualLayanan();

        try {
            $pembelian = Pembelian::query()->create([
                'username' => $user->username,
                'reseller_integration_id' => $integration->getKey(),
                'order_id' => 'INV-H2H-MVP-FAILED-001',
                'user_id' => '12345678',
                'zone' => '2001',
                'layanan' => $layanan->layanan,
                'harga' => 10_000,
                'profit' => 1_000,
                'status' => 'Pending',
                'active_layanan_id' => $layanan->getKey(),
                'active_provider_code' => 'manual',
                'active_provider_sku' => $layanan->provider_id,
                'traffic_source' => 'reseller_h2h',
                'tipe_transaksi' => 'game',
            ]);

            Pembayaran::query()->create([
                'order_id' => $pembelian->order_id,
                'harga' => 10_000,
                'no_pembayaran' => 'SALDO',
                'no_pembeli' => $user->no_wa,
                'status' => 'Lunas',
                'metode' => 'SALDO',
                'reference' => 'EXT-REF-FAILED-001',
            ]);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Webhook delivery failed', $e->getMessage());
        }

        $delivery = ResellerCallbackDelivery::query()->firstOrFail();

        $this->assertSame('failed', $delivery->status);
        $this->assertSame(500, $delivery->last_response_status);
        $this->assertStringContainsString('HTTP 500 response', (string) $delivery->last_error);
        $this->assertStringContainsString('upstream-fail', (string) $delivery->last_response_body);
    }

    public function test_invalid_callback_url_is_blocked_and_logged_as_failed_delivery(): void
    {
        Http::preventStrayRequests();

        $user = User::factory()->create([
            'api_key' => 'token-live-invalid-url',
            'balance' => 50_000,
            'no_wa' => '081234567890',
        ]);

        $integration = ResellerIntegration::query()->create([
            'user_id'          => $user->getKey(),
            'integration_code' => 'live-invalid-url-01',
            'mode'             => 'live',
            'is_active'        => true,
            'metadata'         => ['allowed_ips' => ['127.0.0.1']],
        ]);

        ResellerCallbackProfile::query()->create([
            'reseller_integration_id' => $integration->getKey(),
            'is_enabled' => true,
            'callback_url' => 'https://localhost/callback',
            'webhook_secret' => 'live-secret-001',
            'signing_algorithm' => 'sha256',
            'signature_header' => 'X-Callback-Signature',
            'version' => 1,
        ]);

        $layanan = $this->createManualLayanan();

        try {
            $pembelian = Pembelian::query()->create([
                'username' => $user->username,
                'reseller_integration_id' => $integration->getKey(),
                'order_id' => 'INV-H2H-MVP-INVALID-URL-001',
                'user_id' => '12345678',
                'zone' => '2001',
                'layanan' => $layanan->layanan,
                'harga' => 10_000,
                'profit' => 1_000,
                'status' => 'Pending',
                'active_layanan_id' => $layanan->getKey(),
                'active_provider_code' => 'manual',
                'active_provider_sku' => $layanan->provider_id,
                'traffic_source' => 'reseller_h2h',
                'tipe_transaksi' => 'game',
            ]);

            Pembayaran::query()->create([
                'order_id' => $pembelian->order_id,
                'harga' => 10_000,
                'no_pembayaran' => 'SALDO',
                'no_pembeli' => $user->no_wa,
                'status' => 'Lunas',
                'metode' => 'SALDO',
                'reference' => 'EXT-REF-INVALID-URL-001',
            ]);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Webhook delivery failed', $e->getMessage());
        }

        $delivery = ResellerCallbackDelivery::query()->firstOrFail();

        $this->assertSame('failed', $delivery->status);
        $this->assertStringContainsString('host publik', (string) $delivery->last_error);
    }

    private function createManualLayanan(): Layanan
    {
        return Layanan::query()->create([
            'kategori_id' => '1',
            'layanan' => 'Manual MVP Service',
            'provider_id' => 'MANUAL-MVP-001',
            'harga' => 10_000,
            'harga_member' => 10_000,
            'harga_platinum' => 10_000,
            'harga_gold' => 10_000,
            'harga_flash_sale' => 0,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
            'is_flash_sale' => 0,
            'stock_flash_sale' => 0,
            'catatan' => '-',
            'status' => 'available',
            'provider' => 'manual',
        ]);
    }

    private function createIntegrationWithProfile(User $user): ResellerIntegration
    {
        $integration = ResellerIntegration::query()->create([
            'user_id'          => $user->getKey(),
            'integration_code' => 'live-integration-001-' . $user->getKey(),
            'mode'             => 'live',
            'is_active'        => true,
            // 127.0.0.1 is the loopback used by Laravel's test HTTP client
            'metadata'         => ['allowed_ips' => ['127.0.0.1']],
        ]);

        ResellerCallbackProfile::query()->create([
            'reseller_integration_id' => $integration->getKey(),
            'is_enabled'              => true,
            'callback_url'            => 'https://client.example/callback',
            'webhook_secret'          => 'live-secret-001',
            'signing_algorithm'       => 'sha256',
            'signature_header'        => 'X-Callback-Signature',
            'version'                 => 1,
        ]);

        return $integration;
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\AffiliateHistory;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\PointHistory;
use App\Models\ResellerCallbackDelivery;
use App\Models\ResellerCallbackProfile;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SandboxH2hApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sandbox_key_and_order_schema_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('reseller_integrations', 'api_key_hash'));
        $this->assertTrue(Schema::hasColumn('reseller_integrations', 'api_key_hint'));
        $this->assertTrue(Schema::hasColumn('reseller_integrations', 'api_key_rotated_at'));
        $this->assertTrue(Schema::hasColumn('reseller_integrations', 'api_key_last_used_at'));
        $this->assertTrue(Schema::hasColumn('pembelians', 'environment'));
        $this->assertTrue(Schema::hasColumn('pembelians', 'is_sandbox'));
    }

    public function test_sandbox_auth_accepts_valid_key_and_updates_last_used_at(): void
    {
        $user = User::factory()->create([
            'name' => 'Sandbox Reseller',
            'balance' => 123456,
        ]);
        
        $integration = ResellerIntegration::factory()->sandbox()->create(['user_id' => $user->id]);
        $rawKey = 'testing_sbx_key';

        $this->withHeader('Authorization', 'Bearer ' . $rawKey)
            ->postJson('/api/v1/sandbox/balance')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.name', 'Sandbox Reseller')
            ->assertJsonPath('data.balance', 123456);

        $this->assertNotNull($integration->fresh()->api_key_last_used_at);
    }

    public function test_sandbox_auth_rejects_missing_and_invalid_key(): void
    {
        $this->postJson('/api/v1/sandbox/balance')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'ACCESS_TOKEN_REQUIRED');

        $this->withHeader('Authorization', 'Bearer invalid-sandbox-key')
            ->postJson('/api/v1/sandbox/balance')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'INVALID_TOKEN');
    }

    public function test_sandbox_catalog_endpoints_match_live_shape(): void
    {
        $user = User::factory()->create();
        ResellerIntegration::factory()->sandbox()->create(['user_id' => $user->id]);
        $rawKey = 'testing_sbx_key';
        
        $kategori = Kategori::factory()->create([
            'kode' => 'mlbb',
            'nama' => 'Mobile Legends',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Mobile Legends 59 Diamond',
            'provider_id' => 'SANDBOX-ML59',
            'provider' => 'manual',
            'status' => 'available',
            'harga_member' => 10000,
            'harga_gold' => 9000,
            'harga_platinum' => 8000,
        ]);

        $headers = ['Authorization' => 'Bearer ' . $rawKey];

        $this->withHeaders($headers)
            ->postJson('/api/v1/sandbox/category')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.0.code', 'mlbb');

        $this->withHeaders($headers)
            ->postJson('/api/v1/sandbox/variant', ['code' => 'mlbb'])
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.0.code', 'SANDBOX-ML59');
    }

    public function test_sandbox_order_rejects_live_integration_key(): void
    {
        $user = User::factory()->create(['balance' => 50000]);
        $liveIntegration = ResellerIntegration::factory()->create(['user_id' => $user->id]);
        $rawKey = 'testing_live_key';
        $this->createManualLayanan();

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $rawKey,
        ])->postJson('/api/v1/sandbox/order', [
            'code' => 'MANUAL-SBX-001',
            'referenceNumber' => 'SBX-REJECT-LIVE',
            'data' => '12345678|2001',
        ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'INVALID_TOKEN');
    }

    public function test_valid_sandbox_order_does_not_cut_balance_and_sends_sandbox_callback(): void
    {
        Http::fake([
            'http://localhost/callback' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'balance' => 50000,
            'no_wa' => '081234567890',
        ]);
        $integration = $this->createSandboxIntegrationWithProfile($user);
        $rawKey = 'testing_sbx_key';
        
        $this->createManualLayanan();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $rawKey,
        ])->postJson('/api/v1/sandbox/order', [
            'code'            => 'MANUAL-SBX-001',
            'referenceNumber' => 'SBX-ORDER-001',
            'user_id'         => '12345678',
            'zone_id'         => '2001',
        ]);

        $response->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.status', 'Pending');

        $invoiceNumber = $response->json('data.invoiceNumber');
        $pembelian = Pembelian::query()->where('order_id', $invoiceNumber)->firstOrFail();
        $delivery = ResellerCallbackDelivery::query()->where('pembelian_id', $pembelian->getKey())->firstOrFail();

        $this->assertSame(50000, $user->fresh()->balance);
        $this->assertTrue($pembelian->isSandboxOrder());
        $this->assertSame('sandbox', $pembelian->environment);
        $this->assertSame($integration->getKey(), $pembelian->reseller_integration_id);
        $this->assertSame('sandbox', $delivery->environment);
        $this->assertSame('h2h.sandbox.order.updated', $delivery->event_name);
        $this->assertSame('delivered', $delivery->status);

        Http::assertSentCount(1);

        $request = Http::recorded()->first()[0];
        $payload = json_decode($request->body(), true);

        $this->assertTrue($request->hasHeader('X-Callback-Event', 'h2h.sandbox.order.updated'));
        $this->assertSame('h2h.sandbox.order.updated', $payload['event'] ?? null);
        $this->assertTrue($payload['sandbox'] ?? false);
        $this->assertSame('sandbox', $payload['environment'] ?? null);
        $this->assertSame('Pending', $payload['statusCode'] ?? null);
    }

    public function test_sandbox_status_and_simulate_status_are_owner_scoped_and_dispatch_final_callback_once(): void
    {
        Http::fake([
            'http://localhost/callback' => Http::response(['ok' => true], 200),
        ]);

        $owner = User::factory()->create(['balance' => 50000]);
        $outsider = User::factory()->create(['balance' => 50000]);
        
        $integration = $this->createSandboxIntegrationWithProfile($owner);
        $ownerKey = 'testing_sbx_key';
        
        $outsiderIntegration = ResellerIntegration::factory()->sandbox()->create(['user_id' => $outsider->id]);
        $outsiderKey = 'testing_sbx_key'; // They both use testing_sbx_key but wait! 
        // If they both use the SAME rawKey, then the hash is the SAME in the database!
        // That's a collision! Let's update the rawKey for outsider
        
        $outsiderKey = 'testing_sbx_key_OUTSIDER';
        $outsiderIntegration->api_key = $outsiderKey;
        $outsiderIntegration->save();
        
        $this->createManualLayanan();

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $ownerKey,
        ])->postJson('/api/v1/sandbox/order', [
            'code'            => 'MANUAL-SBX-001',
            'referenceNumber' => 'SBX-SIM-001',
            'user_id'         => '12345678',
            'zone_id'         => '2001',
        ])->assertOk();

        $invoiceNumber = $createResponse->json('data.invoiceNumber');

        $this->withHeader('Authorization', 'Bearer ' . $outsiderKey)
            ->postJson('/api/v1/sandbox/status-order/' . $invoiceNumber)
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'INVOICE_NOT_FOUND');

        $this->withHeader('Authorization', 'Bearer ' . $ownerKey)
            ->postJson('/api/v1/sandbox/simulate-status/' . $invoiceNumber, [
                'status' => 'Success',
            ])
            ->assertOk()
            ->assertJsonPath('data.statusCode', 'Success');

        $this->assertSame(2, ResellerCallbackDelivery::query()->count());
        $this->assertSame('Success', ResellerCallbackDelivery::query()->latest('id')->firstOrFail()->payload['statusCode']);

        $this->withHeader('Authorization', 'Bearer ' . $ownerKey)
            ->postJson('/api/v1/sandbox/simulate-status/' . $invoiceNumber, [
                'status' => 'Success',
            ])
            ->assertOk();

        $this->assertSame(2, ResellerCallbackDelivery::query()->count());
    }

    public function test_sandbox_success_does_not_trigger_affiliate_commission_or_points(): void
    {
        Http::fake([
            'http://localhost/callback' => Http::response(['ok' => true], 200),
        ]);

        $affiliate = User::factory()->create([
            'username' => 'affiliate.owner',
            'affiliate_status' => 'active',
            'balance' => 0,
        ]);
        $downline = User::factory()->create([
            'username' => 'sandbox.downline',
            'uplink' => $affiliate->username,
            'balance' => 50000,
        ]);
        
        $integration = $this->createSandboxIntegrationWithProfile($downline);
        $rawKey = 'testing_sbx_key';
        
        $this->createManualLayanan();

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $rawKey,
        ])->postJson('/api/v1/sandbox/order', [
            'code'            => 'MANUAL-SBX-001',
            'referenceNumber' => 'SBX-SIDE-EFFECT-001',
            'user_id'         => '12345678',
            'zone_id'         => '2001',
        ])->assertOk();

        $this->withHeader('Authorization', 'Bearer ' . $rawKey)
            ->postJson('/api/v1/sandbox/simulate-status/' . $createResponse->json('data.invoiceNumber'), [
                'status' => 'Success',
            ])
            ->assertOk();

        $this->assertSame(0, AffiliateHistory::query()->count());
        $this->assertSame(0, PointHistory::query()->count());
        $this->assertSame(0, $affiliate->fresh()->balance);
    }

    private function createManualLayanan(): Layanan
    {
        return Layanan::query()->create([
            'kategori_id' => '1',
            'layanan' => 'Sandbox Manual Service',
            'provider_id' => 'MANUAL-SBX-001',
            'harga' => 10000,
            'harga_member' => 10000,
            'harga_platinum' => 10000,
            'harga_gold' => 10000,
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

    private function createSandboxIntegrationWithProfile(User $user): ResellerIntegration
    {
        $integration = ResellerIntegration::factory()->sandbox()->create([
            'user_id' => $user->id,
        ]);

        ResellerCallbackProfile::query()->create([
            'reseller_integration_id' => $integration->getKey(),
            'is_enabled' => true,
            'callback_url' => 'http://localhost/callback',
            'webhook_secret' => 'sandbox-secret-001',
            'signing_algorithm' => 'sha256',
            'signature_header' => 'X-Callback-Signature',
            'version' => 1,
        ]);

        return $integration;
    }
}

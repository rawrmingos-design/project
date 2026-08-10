<?php

namespace Tests\Feature\Api;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\ProviderPath;
use App\Models\ResellerCallbackDelivery;
use App\Models\ResellerCallbackProfile;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResellerApiRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockSuccessfulAccountValidation();
    }

    public function test_balance_returns_authenticated_reseller_profile(): void
    {
        $integration = ResellerIntegration::factory()->create([
            'mode'      => 'live',
            'is_active' => true,
            'allowed_ips' => ['127.0.0.1'],
        ]);

        $integration->user->update([
            'name'    => 'Reseller Demo',
            'role'    => 'Gold',
            'balance' => 123456,
        ]);

        $this->withHeader('Authorization', 'Bearer testing_live_key')
            ->postJson('/api/v1/balance')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.name', 'Reseller Demo')
            ->assertJsonPath('data.membership', 'Gold')
            ->assertJsonPath('data.balance', 123456);
    }

    public function test_balance_rejects_invalid_token_with_http_403(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/v1/balance')
            ->assertStatus(403)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Invalid Token')
            ->assertJsonPath('error_code', 'INVALID_TOKEN');
    }

    public function test_category_lists_categories_for_authenticated_reseller(): void
    {
        ResellerIntegration::factory()->create([
            'mode'      => 'live',
            'is_active' => true,
        ]);

        Kategori::factory()->create([
            'kode' => 'mlbb',
            'nama' => 'Mobile Legends',
            'status' => 'active',
        ]);

        Kategori::factory()->create([
            'kode' => 'valorant',
            'nama' => 'Valorant',
            'status' => 'inactive',
        ]);

        $this->withHeader('Authorization', 'Bearer testing_live_key')
            ->postJson('/api/v1/category')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'mlbb')
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.1.code', 'valorant')
            ->assertJsonPath('data.1.is_active', false);
    }

    public function test_variant_requires_code_field(): void
    {
        ResellerIntegration::factory()->create([
            'mode'      => 'live',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer testing_live_key')
            ->postJson('/api/v1/variant', [])
            ->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonPath('error_code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'details' => ['code'],
            ]);
    }

    public function test_variant_rejects_malformed_json_payload(): void
    {
        ResellerIntegration::factory()->create([
            'mode'      => 'live',
            'is_active' => true,
        ]);

        $this->call('POST', '/api/v1/variant', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer testing_live_key',
        ], '{"code":')
            ->assertStatus(400)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Invalid JSON payload')
            ->assertJsonPath('error_code', 'INVALID_JSON_PAYLOAD');
    }

    public function test_variant_returns_best_provider_path_and_role_price(): void
    {
        $integration = ResellerIntegration::factory()->create([
            'mode'      => 'live',
            'is_active' => true,
        ]);
        
        $integration->user->update([
            'role' => 'Gold',
        ]);

        $kategori = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'nama' => 'Mobile Legends',
            'status' => 'active',
        ]);

        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Mobile Legends 86 DM',
            'provider' => 'digiflazz',
            'provider_id' => 'DG-ML86',
            'harga' => 10000,
            'harga_member' => 12000,
            'harga_gold' => 13500,
            'harga_platinum' => 11000,
            'status' => 'available',
        ]);

        ProviderPath::query()->create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'apigames',
            'provider_sku' => 'AG-ML86',
            'modal_price' => 9200,
            'priority' => 1,
            'status' => 'available',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer testing_live_key')
            ->postJson('/api/v1/variant', [
                'code' => $kategori->kode,
            ])
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.0.code', 'AG-ML86')
            ->assertJsonPath('data.0.price', 13500);

        // provider field is intentionally removed from /variant response (API contract v2)
        $this->assertArrayNotHasKey('provider', $response->json('data.0'));
        $this->assertIsBool($response->json('data.0.is_active'));
    }

    public function test_status_order_is_scoped_to_authenticated_reseller(): void
    {
        $ownerIntegration = ResellerIntegration::factory()->create([
            'mode'      => 'live',
            'is_active' => true,
            'allowed_ips' => ['127.0.0.1'],
        ]);

        // Create outsider without setting testing_live_key as we will use invalid token
        $outsider = User::factory()->create([
            'username' => 'outsider.reseller',
        ]);

        ResellerIntegration::factory()->create([
            'user_id' => $outsider->getKey(),
            'mode'      => 'live',
            'is_active' => true,
            'allowed_ips' => ['127.0.0.1'],
            'api_key_hash' => hash('sha256', 'outsider_token'),
        ]);

        Pembelian::factory()->create([
            'order_id' => 'INV-OWNER-ONLY-001',
            'username' => $ownerIntegration->user->username,
            'user_id'  => '998877',
            'zone'     => '3344',
            'status'   => 'Sukses',
        ]);

        // Owner can read their own order
        $this->withHeaders([
            'Authorization'               => 'Bearer testing_live_key',
        ])->postJson('/api/v1/status-order/INV-OWNER-ONLY-001')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.invoiceNumber', 'INV-OWNER-ONLY-001');

        // Outsider cannot read owner's order — 404 not found
        $this->withHeaders([
            'Authorization'               => 'Bearer outsider_token',
        ])->postJson('/api/v1/status-order/INV-OWNER-ONLY-001')
            ->assertStatus(404)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Invoice Not Found')
            ->assertJsonPath('error_code', 'INVOICE_NOT_FOUND');
    }

    public function test_order_requires_reference_number_with_validation_details(): void
    {
        $integration = $this->createIntegrationWithProfile();
        $this->createManualLayanan();

        $this->withHeaders([
            'Authorization' => 'Bearer testing_live_key',
        ])->postJson('/api/v1/order', [
            'code' => 'MANUAL-MVP-001',
            'data' => '12345678|2001',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonPath('error_code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'details' => ['referenceNumber'],
            ]);
    }

    public function test_variant_unknown_product_code_returns_stable_error_code(): void
    {
        ResellerIntegration::factory()->create([
            'mode'      => 'live',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer testing_live_key')
            ->postJson('/api/v1/variant', [
                'code' => 'missing-category',
            ])
            ->assertStatus(404)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Code Not Found')
            ->assertJsonPath('error_code', 'CODE_NOT_FOUND');
    }

    public function test_order_unknown_service_code_returns_stable_error_code(): void
    {
        $integration = $this->createIntegrationWithProfile();

        $this->withHeaders([
            'Authorization' => 'Bearer testing_live_key',
        ])->postJson('/api/v1/order', [
            'code' => 'MISSING-SERVICE-001',
            'referenceNumber' => 'REF-CODE-NOT-FOUND-001',
            'user_id' => '12345678',
            'zone_id' => '2001',
        ])
            ->assertStatus(404)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Code Not Found')
            ->assertJsonPath('error_code', 'CODE_NOT_FOUND');
    }

    public function test_order_rejects_insufficient_balance_with_stable_error_code(): void
    {
        $integration = $this->createIntegrationWithProfile();
        $integration->user->update(['balance' => 100]);
        $this->createManualLayanan();

        $this->withHeaders([
            'Authorization' => 'Bearer testing_live_key',
        ])->postJson('/api/v1/order', [
            'code' => 'MANUAL-MVP-001',
            'referenceNumber' => 'REF-LOW-BALANCE-001',
            'user_id' => '12345678',
            'zone_id' => '2001',
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Your Balance is Insufficient')
            ->assertJsonPath('error_code', 'INSUFFICIENT_BALANCE');
    }

    public function test_provider_failure_returns_safe_order_failed_error(): void
    {
        $integration = $this->createIntegrationWithProfile();
        $kategori = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'nama' => 'Mobile Legends',
            'status' => 'active',
            'tipe' => 'game',
            'server_id' => true,
            'require_user_id' => true,
        ]);

        Layanan::query()->create([
            'kategori_id' => (string) $kategori->getKey(),
            'layanan' => 'VIP Failure Service',
            'provider_id' => 'VIP-FAIL-MVP-001',
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
            'provider' => 'vip',
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer testing_live_key',
        ])->postJson('/api/v1/order', [
            'code' => 'VIP-FAIL-MVP-001',
            'referenceNumber' => 'REF-PROVIDER-FAILED-001',
            'user_id' => '12345678',
            'zone_id' => '2001',
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Order Failed')
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.invoiceNumber', null)
            ->assertJsonMissingPath('error_code')
            ->assertJsonMissingPath('details');
    }

    public function test_order_duplicate_reference_number_returns_existing_invoice_without_duplicate_side_effects(): void
    {
        Http::fake([
            'https://client.example/callback' => Http::response(['ok' => true], 200),
        ]);

        $integration = $this->createIntegrationWithProfile();
        $this->createManualLayanan();

        $payload = [
            'code' => 'MANUAL-MVP-001',
            'referenceNumber' => 'REF-IDEMPOTENT-001',
            'user_id' => '12345678',
            'zone_id' => '2001',
        ];

        $headers = [
            'Authorization' => 'Bearer testing_live_key',
        ];

        $firstResponse = $this->withHeaders($headers)->postJson('/api/v1/order', $payload);
        $firstResponse->assertOk()->assertJsonPath('error', false);

        $invoiceNumber = $firstResponse->json('data.invoiceNumber');

        $secondResponse = $this->withHeaders($headers)->postJson('/api/v1/order', $payload);
        $secondResponse->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.invoiceNumber', $invoiceNumber)
            ->assertJsonPath('data.isDuplicate', true);

        $this->assertSame(1, Pembelian::query()->count());
        $this->assertSame(1, Pembayaran::query()->count());
        $this->assertSame(1, ResellerCallbackDelivery::query()->count());
        $this->assertSame(40000, $integration->user->fresh()->balance);

        Http::assertSentCount(1);
    }

    public function test_live_status_order_cannot_read_sandbox_order(): void
    {
        $integration = ResellerIntegration::factory()->create([
            'mode'      => 'live',
            'is_active' => true,
            'allowed_ips' => ['127.0.0.1'],
        ]);

        // Create a sandbox order owned by the same user
        Pembelian::factory()->create([
            'order_id'    => 'SBX-SCOPE-001',
            'username'    => $integration->user->username,
            'is_sandbox'  => true,
            'environment' => 'sandbox',
        ]);

        $this->withHeaders([
            'Authorization'               => 'Bearer testing_live_key',
        ])->postJson('/api/v1/status-order/SBX-SCOPE-001')
            ->assertStatus(404)
            ->assertJsonPath('error', true)
            ->assertJsonPath('error_code', 'INVOICE_NOT_FOUND');
    }

    public function test_live_status_order_reads_legacy_order_with_null_environment(): void
    {
        $integration = ResellerIntegration::factory()->create([
            'mode'      => 'live',
            'is_active' => true,
            'allowed_ips' => ['127.0.0.1'],
        ]);

        // Legacy orders created before the environment column existed have
        // is_sandbox=false and environment=NULL. The live endpoint must still return them.
        Pembelian::factory()->create([
            'order_id'    => 'LEGACY-NULL-001',
            'username'    => $integration->user->username,
            'status'      => 'Sukses',
            'is_sandbox'  => false,
            'environment' => null,
        ]);

        $this->withHeaders([
            'Authorization'               => 'Bearer testing_live_key',
        ])->postJson('/api/v1/status-order/LEGACY-NULL-001')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.invoiceNumber', 'LEGACY-NULL-001');
    }

    private function createManualLayanan(): Layanan
    {
        $kategori = Kategori::query()->create([
            'nama' => 'Mobile Legends',
            'sub_nama' => 'Mobile Legends',
            'kode' => 'mobile-legends',
            'tipe' => 'game',
            'status' => 'active',
            'server_id' => true,
            'require_user_id' => true,
        ]);

        return Layanan::query()->create([
            'kategori_id' => (string) $kategori->getKey(),
            'layanan' => 'Manual MVP Service',
            'provider_id' => 'MANUAL-MVP-001',
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

    private function createIntegrationWithProfile(): ResellerIntegration
    {
        $integration = ResellerIntegration::factory()->create([
            'mode'             => 'live',
            'is_active'        => true,
            // 127.0.0.1 is the loopback used by Laravel's test HTTP client
            'allowed_ips'         => ['127.0.0.1'],
        ]);

        $integration->user->update(['balance' => 50000]);

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

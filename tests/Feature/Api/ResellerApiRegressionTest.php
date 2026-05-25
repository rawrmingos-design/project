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

    public function test_balance_returns_authenticated_reseller_profile(): void
    {
        $user = User::factory()->create([
            'api_key' => 'token-balance-valid',
            'name' => 'Reseller Demo',
            'role' => 'Gold',
            'balance' => 123456,
            'no_wa' => '081234567890',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $user->api_key)
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
            ->assertJsonPath('message', 'Invalid Token');
    }

    public function test_product_lists_categories_for_authenticated_reseller(): void
    {
        User::factory()->create([
            'api_key' => 'token-product-valid',
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

        $this->withHeader('Authorization', 'Bearer token-product-valid')
            ->postJson('/api/v1/product')
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
        User::factory()->create([
            'api_key' => 'token-variant-validation',
        ]);

        $this->withHeader('Authorization', 'Bearer token-variant-validation')
            ->postJson('/api/v1/variant', [])
            ->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonStructure([
                'errors' => ['code'],
            ]);
    }

    public function test_variant_returns_best_provider_path_and_role_price(): void
    {
        $user = User::factory()->create([
            'api_key' => 'token-variant-success',
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

        $this->withHeader('Authorization', 'Bearer ' . $user->api_key)
            ->postJson('/api/v1/variant', [
                'code' => $kategori->kode,
            ])
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.0.code', 'AG-ML86')
            ->assertJsonPath('data.0.provider', 'apigames')
            ->assertJsonPath('data.0.price', 13500);
    }

    public function test_status_order_is_scoped_to_authenticated_reseller(): void
    {
        $owner = User::factory()->create([
            'api_key' => 'token-status-owner',
            'username' => 'owner.reseller',
        ]);
        $outsider = User::factory()->create([
            'api_key' => 'token-status-outsider',
            'username' => 'outsider.reseller',
        ]);

        Pembelian::factory()->create([
            'order_id' => 'INV-OWNER-ONLY-001',
            'username' => $owner->username,
            'user_id' => '998877',
            'zone' => '3344',
            'status' => 'Sukses',
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $owner->api_key)
            ->postJson('/api/v1/status-order/INV-OWNER-ONLY-001')
            ->assertOk()
            ->assertJsonPath('error', false)
            ->assertJsonPath('data.invoiceNumber', 'INV-OWNER-ONLY-001');

        $this->withHeader('Authorization', 'Bearer ' . $outsider->api_key)
            ->postJson('/api/v1/status-order/INV-OWNER-ONLY-001')
            ->assertStatus(404)
            ->assertJsonPath('error', true)
            ->assertJsonPath('message', 'Invoice Not Found');
    }

    public function test_order_duplicate_reference_number_returns_existing_invoice_without_duplicate_side_effects(): void
    {
        Http::fake([
            'https://client.example/callback' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'api_key' => 'token-order-idempotent',
            'balance' => 50000,
            'no_wa' => '081234567890',
        ]);

        $integration = $this->createIntegrationWithProfile($user);
        $this->createManualLayanan();

        $payload = [
            'code' => 'MANUAL-MVP-001',
            'referenceNumber' => 'REF-IDEMPOTENT-001',
            'data' => '12345678|2001',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $user->api_key,
            'X-Reseller-Integration-Code' => $integration->integration_code,
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
        $this->assertSame(40000, $user->fresh()->balance);

        Http::assertSentCount(1);
    }

    private function createManualLayanan(): Layanan
    {
        return Layanan::query()->create([
            'kategori_id' => '1',
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

    private function createIntegrationWithProfile(User $user): ResellerIntegration
    {
        $integration = ResellerIntegration::query()->create([
            'user_id' => $user->getKey(),
            'integration_code' => 'live-regression-' . $user->getKey(),
            'mode' => 'live',
            'is_active' => true,
        ]);

        ResellerCallbackProfile::query()->create([
            'reseller_integration_id' => $integration->getKey(),
            'is_enabled' => true,
            'callback_url' => 'https://client.example/callback',
            'webhook_secret' => 'live-secret-001',
            'signing_algorithm' => 'sha256',
            'signature_header' => 'X-Callback-Signature',
            'version' => 1,
        ]);

        return $integration;
    }
}

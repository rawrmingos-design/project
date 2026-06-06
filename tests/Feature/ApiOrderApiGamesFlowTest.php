<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\OrderApiController;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembelian;
use App\Models\ProviderPath;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiOrderApiGamesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_variant_exposes_best_provider_path_sku(): void
    {
        $token = 'token-apigames-list';
        $integration = \App\Models\ResellerIntegration::factory()->create([
            'api_key_hash' => hash('sha256', $token),
            'mode' => 'live',
            'is_active' => true,
        ]);
        $user = $integration->user;
        $kategori = Kategori::factory()->create(['kode' => 'mobile-legends']);
        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Mobile Legends 86 DM',
            'provider' => 'digiflazz',
            'provider_id' => 'DG-ML86',
            'status' => 'available',
        ]);

        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'apigames',
            'provider_sku' => 'AG-ML86',
            'modal_price' => 9500,
            'priority' => 1,
            'status' => 'available',
            'metadata' => ['source' => 'apigames_catalog'],
        ]);

        $request = Request::create('/api/list-variant', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], json_encode([
            'code' => $kategori->kode,
        ]));
        // Phase 3: controller reads api_user from request attributes (no DB fallback)
        $request->attributes->set('api_user', $user);

        $response = app(OrderApiController::class)->listVariant($request);
        $payload = $response->getData(true);

        $this->assertFalse($payload['error']);
        $this->assertSame('AG-ML86', $payload['data'][0]['code']);
        $this->assertSame('apigames', $payload['data'][0]['provider']);
    }

    public function test_api_order_uses_apigames_provider_path_and_persists_pending_status(): void
    {
        $this->seedSettings();

        $token = 'token-apigames-order';
        $integration = \App\Models\ResellerIntegration::factory()->create([
            'api_key_hash' => hash('sha256', $token),
            'mode' => 'live',
            'is_active' => true,
        ]);
        $user = $integration->user;
        $user->update([
            'balance' => 50000,
            'no_wa' => '08123456789',
        ]);

        $kategori = Kategori::factory()->create(['kode' => 'mobile-legends']);
        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Mobile Legends 86 DM',
            'provider' => 'digiflazz',
            'provider_id' => 'DG-ML86',
            'harga' => 10000,
            'harga_member' => 12000,
            'status' => 'available',
        ]);

        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'apigames',
            'provider_sku' => 'AG-ML86',
            'modal_price' => 9100,
            'priority' => 1,
            'status' => 'available',
            'metadata' => [
                'source' => 'apigames_catalog',
                'catalog_product_id' => '123',
            ],
        ]);

        Http::fake([
            'https://v1.apigames.id/v2/transaksi' => Http::response([
                'status' => 1,
                'message' => 'Transaksi diterima',
                'data' => [
                    'trx_id' => 'TRX-APIGAMES-001',
                    'ref_id' => 'WEJIZY-RAPI-REF',
                    'status' => 'Pending',
                    'message' => 'Menunggu proses provider',
                ],
            ], 200),
        ]);

        $request = Request::create('/api/order', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'code'            => 'AG-ML86',
            'referenceNumber' => 'EXT-REF-001',
            'user_id'         => '12345678',
            'zone_id'         => '9012',
        ]));
        // Phase 3: controller reads api_user from request attributes (no DB fallback)
        $request->attributes->set('api_user', $user);

        $response = app(OrderApiController::class)->order($request);
        $payload = $response->getData(true);
        $pembelian = Pembelian::query()->firstOrFail();

        $this->assertFalse($payload['error']);
        $this->assertSame('Pending', $payload['data']['status']);
        $this->assertSame('Pending', $pembelian->status);
        $this->assertSame('apigames', $pembelian->active_provider_code);
        $this->assertSame('AG-ML86', $pembelian->active_provider_sku);
        $this->assertSame('TRX-APIGAMES-001', $pembelian->provider_order_id);
        $this->assertSame(2900, $pembelian->profit);
        $this->assertSame(38000, $user->fresh()->balance);
    }

    public function test_api_order_prefers_bangjeff_provider_path_over_legacy_provider_fields(): void
    {
        $this->seedSettings([
            'apikey_bangjeff' => 'bangjeff-key',
        ]);

        $token = 'token-bangjeff-order';
        $integration = \App\Models\ResellerIntegration::factory()->create([
            'api_key_hash' => hash('sha256', $token),
            'mode' => 'live',
            'is_active' => true,
        ]);
        $user = $integration->user;
        $user->update([
            'balance' => 50000,
            'no_wa' => '08123456789',
        ]);

        $kategori = Kategori::factory()->create(['kode' => 'mobile-legends']);
        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Mobile Legends Weekly Pass',
            'provider' => 'digiflazz',
            'provider_id' => 'DG-WP',
            'harga' => 10000,
            'harga_member' => 12000,
            'status' => 'available',
        ]);

        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'bangjeff',
            'provider_sku' => 'BJ-WP',
            'modal_price' => 9200,
            'priority' => 1,
            'status' => 'available',
            'metadata' => ['source' => 'bangjeff_variant'],
        ]);

        Http::fake([
            'https://sandbox-api.bangjeff.com/api/v4/checkout' => Http::response([
                'rc' => '00',
                'message' => 'Success',
                'data' => [
                    'invoiceNumber' => 'BJ-INV-001',
                    'statusCode' => 'PROCESSING',
                    'statusDesc' => 'Order diproses',
                ],
            ], 200),
        ]);

        config()->set('providers.bangjeff.use_sandbox_on_local', true);

        $request = Request::create('/api/order', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'code'            => 'BJ-WP',
            'referenceNumber' => 'EXT-REF-BJ-001',
            'user_id'         => '12345678',
            'zone_id'         => '9012',
        ]));
        // Phase 3: controller reads api_user from request attributes (no DB fallback)
        $request->attributes->set('api_user', $user);

        $response = app(OrderApiController::class)->order($request);
        $payload = $response->getData(true);
        $pembelian = Pembelian::query()->firstOrFail();

        $this->assertFalse($payload['error']);
        $this->assertSame('Pending', $payload['data']['status']);
        $this->assertSame('Pending', $pembelian->status);
        $this->assertSame('bangjeff', $pembelian->active_provider_code);
        $this->assertSame('BJ-WP', $pembelian->active_provider_sku);
        $this->assertSame('BJ-INV-001', $pembelian->provider_order_id);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'https://sandbox-api.bangjeff.com/api/v4/checkout'
                && ($payload['variantCode'] ?? null) === 'BJ-WP';
        });
    }

    private function seedSettings(array $overrides = []): void
    {
        SettingWeb::query()->create(array_merge([
            'judul_web' => 'Test',
            'deskripsi_web' => 'Test',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/1',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => '-',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => '-',
            'order_prefik' => 'INV',
            'username_digi' => 'digi-user',
            'api_key_digi' => 'digi-key',
            'apigames_secret' => 'secret-123',
            'apigames_merchant' => 'merchant-123',
        ], $overrides));
    }
}

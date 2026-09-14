<?php

namespace Tests\Feature;

use App\Models\CustomInput;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Services\CheckId\CheckIdResolver;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\PublicOrderPageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderControllerCheckAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        Http::preventStrayRequests();
    }

    public function test_resolver_skips_non_game_categories_for_all_entrypoints(): void
    {
        foreach (['voucher', 'joki', 'jokigendong', 'vilogml'] as $type) {
            $kategori = Kategori::factory()->create([
                'kode' => $type . '-category',
                'tipe' => $type,
                'require_user_id' => true,
            ]);

            $result = app(CheckIdResolver::class)->resolveForCategory($kategori, 'CUSTOM_UID', null);

            $this->assertSame(204, $result['status']['code']);
            $this->assertTrue($result['skip_check']);
        }

        Http::assertNothingSent();
    }

    public function test_check_account_skips_voucher_and_complex_categories(): void
    {
        foreach (['voucher', 'joki', 'jokigendong', 'vilogml'] as $type) {
            $category = Kategori::factory()->create([
                'kode' => $type . '-endpoint',
                'tipe' => $type,
                'require_user_id' => true,
            ]);

            $this->postJson('/ajax/check-account', [
                'uid' => 'CUSTOM_UID',
                'kategori_kode' => $category->kode,
            ])
                ->assertOk()
                ->assertJsonPath('status.code', 204)
                ->assertJsonPath('skip_check', true);
        }

        Http::assertNothingSent();
    }

    public function test_check_account_keeps_game_and_populer_as_validation_candidates(): void
    {
        foreach (['game', 'populer'] as $type) {
            $category = Kategori::factory()->create([
                'kode' => $type . '-endpoint',
                'tipe' => $type,
                'require_user_id' => true,
            ]);

            $this->postJson('/ajax/check-account', [
                'uid' => 'CUSTOM_UID',
                'kategori_kode' => $category->kode,
            ])
                ->assertStatus(200)
                ->assertJsonMissingPath('skip_check');
        }

        Http::assertNothingSent();
    }

    public function test_check_account_skips_game_category_when_user_id_is_not_required(): void
    {
        $category = Kategori::factory()->create([
            'kode' => 'optional-user-id',
            'tipe' => 'populer',
            'require_user_id' => false,
        ]);

        $this->postJson('/ajax/check-account', [
            'uid' => 'CUSTOM_UID',
            'kategori_kode' => $category->kode,
        ])
            ->assertOk()
            ->assertJsonPath('status.code', 204)
            ->assertJsonPath('skip_check', true);

        Http::assertNothingSent();
    }

    public function test_check_account_rejects_unknown_category(): void
    {
        $this->postJson('/ajax/check-account', [
            'uid' => 'CUSTOM_UID',
            'kategori_kode' => 'missing-category',
        ])->assertStatus(404);

        Http::assertNothingSent();
    }

    public function test_check_account_rejects_layanan_from_different_category(): void
    {
        $requestedKategori = Kategori::factory()->create([
            'kode' => 'custom-game',
            'tipe' => 'game',
        ]);
        $otherKategori = Kategori::factory()->create([
            'kode' => 'other-game',
            'tipe' => 'game',
        ]);

        $layanan = Layanan::factory()->create([
            'kategori_id' => $otherKategori->id,
        ]);

        $response = $this->postJson('/ajax/check-account', [
            'uid' => 'CUSTOM_UID',
            'kategori_kode' => $requestedKategori->kode,
            'service' => $layanan->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status.code', 422);

        Http::assertNothingSent();
    }

    public function test_catalog_zone_requirement_overrides_local_metadata_across_storefront_and_gateway_contracts(): void
    {
        $category = Kategori::factory()->create([
            'kode' => 'pubg-mobile',
            'tipe' => 'populer',
            'require_user_id' => true,
            'server_id' => false,
        ]);
        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'harga_member' => 10000,
        ]);
        Method::query()->create([
            'name' => 'Test Manual',
            'code' => 'TEST_MANUAL',
            'payment' => 'manual',
            'tipe' => 'manual',
            'images' => 'manual.png',
            'keterangan' => 'Test manual payment',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => true,
        ]);
        CustomInput::query()
            ->where('kategori_id', (string) $category->id)
            ->update([
                'field_1' => 'User ID,Masukkan User ID,number',
                'field_2' => null,
                'field_select_title' => null,
                'field_select' => null,
            ]);

        Cache::put('checkid_catalog_v1', [
            'pubg-mobile-dg' => [
                'slug' => 'pubg-mobile-dg',
                'hasZoneId' => true,
            ],
        ]);

        $resolver = app(CheckIdResolver::class);
        $this->assertTrue($resolver->requiresZoneId('pubg-mobile', false));

        $page = app(PublicOrderPageDataService::class)->getData($category);
        $this->assertTrue($page['category']['serverId']);
        $this->assertSame('Server / Zone', $page['category']['customInputs']['zone']['label']);

        $gatewayIndexCategory = collect(app(GatewayCatalogService::class)->categories()['data'])
            ->firstWhere('code', 'pubg-mobile');
        $this->assertTrue($gatewayIndexCategory['requires_zone_id']);

        $gatewayCategory = app(GatewayCatalogService::class)->services('pubg-mobile');
        $this->assertTrue($gatewayCategory['data']['category']['requires_zone_id']);
        $this->assertSame('Server / Zone', $gatewayCategory['data']['category']['custom_inputs']['zone']['label']);

        $quote = app(GatewayPricingService::class)->quote([
            'service_id' => $service->id,
            'payment_method' => 'TEST_MANUAL',
        ]);
        $this->assertTrue($quote['data']['requires_zone_id']);
        $this->assertSame('Server / Zone', $quote['data']['custom_inputs']['zone']['label']);
    }

    public function test_check_account_uses_catalog_alias_and_preserves_required_zone(): void
    {
        $category = Kategori::factory()->create([
            'kode' => 'pubg-mobile',
            'tipe' => 'populer',
            'require_user_id' => true,
            'server_id' => false,
        ]);
        $service = Layanan::factory()->create(['kategori_id' => $category->id]);

        Cache::put('checkid_catalog_v1', [
            'pubg-mobile-dg' => [
                'slug' => 'pubg-mobile-dg',
                'hasZoneId' => true,
            ],
        ]);

        config([
            'providers.check_id.selfhosted.enabled' => true,
            'providers.check_id.selfhosted.base_url' => 'https://cekid.jasakoding.web.id',
            'providers.check_id.selfhosted.api_key' => 'test-check-id-key',
        ]);

        Http::fake([
            'https://cekid.jasakoding.web.id/api/check*' => Http::response([
                'status' => true,
                'data' => ['username' => 'PUBG Nick'],
            ]),
        ]);

        $this->postJson('/ajax/check-account', [
            'uid' => '512345678',
            'kategori_kode' => 'pubg-mobile',
            'service' => $service->id,
            'zone' => '1234',
        ])->assertOk()->assertJsonPath('status.code', 200);

        Http::assertSent(function ($request): bool {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['slug'] ?? '') === 'pubg-mobile-dg'
                && ($query['zone'] ?? '') === '1234';
        });
    }

    public function test_catalog_zoneless_metadata_hides_stale_local_zone_input(): void
    {
        $category = Kategori::factory()->create([
            'kode' => 'free-fire',
            'tipe' => 'populer',
            'require_user_id' => true,
            'server_id' => true,
        ]);

        CustomInput::query()
            ->where('kategori_id', (string) $category->id)
            ->update([
                'field_1' => 'User ID,Masukkan User ID,number',
                'field_2' => 'Server ID,Masukkan Server ID,number',
                'field_select_title' => null,
                'field_select' => null,
            ]);

        Cache::put('checkid_catalog_v1', [
            'free-fire' => [
                'slug' => 'free-fire',
                'hasZoneId' => false,
            ],
        ]);

        $page = app(PublicOrderPageDataService::class)->getData($category);
        $this->assertFalse($page['category']['serverId']);
        $this->assertNull($page['category']['customInputs']['zone']);

        $gatewayCategory = app(GatewayCatalogService::class)->services('free-fire');
        $this->assertFalse($gatewayCategory['data']['category']['requires_zone_id']);
        $this->assertNull($gatewayCategory['data']['category']['custom_inputs']['zone']);
    }
}

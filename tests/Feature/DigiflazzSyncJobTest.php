<?php

namespace Tests\Feature;

use App\Jobs\DigiflazzSyncJob;
use App\Models\Layanan;
use App\Models\ProviderPath;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DigiflazzSyncJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_skips_associative_error_response_without_type_error(): void
    {
        $this->createSettings();
        Log::spy();
        Http::fake([
            'https://api.digiflazz.com/v1/price-list' => Http::response([
                'data' => [
                    'status' => 'Gagal',
                    'message' => 'Invalid signature',
                ],
            ]),
        ]);

        (new DigiflazzSyncJob())->handle();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'DigiflazzSyncJob: pricelist response did not contain a product list.'
                    && ($context['status'] ?? null) === 'Gagal'
                    && ($context['message'] ?? null) === 'Invalid signature'
                    && ($context['data_type'] ?? null) === 'array';
            });
    }

    public function test_it_skips_string_rows_and_updates_valid_provider_paths(): void
    {
        $this->createSettings();
        $layanan = $this->createLayanan();
        $path = ProviderPath::query()->create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'digiflazz',
            'provider_sku' => 'DF-ML10',
            'modal_price' => 1000,
            'priority' => 1,
            'status' => 'maintenance',
        ]);

        Log::spy();
        Http::fake([
            'https://api.digiflazz.com/v1/price-list' => Http::response([
                'data' => [
                    'invalid-row-from-api',
                    [
                        'buyer_sku_code' => 'DF-ML10',
                        'price' => 1234,
                        'buyer_product_status' => true,
                        'seller_product_status' => true,
                        'stock' => 0,
                        'unlimited_stock' => true,
                    ],
                ],
            ]),
        ]);

        (new DigiflazzSyncJob())->handle();

        $path->refresh();

        $this->assertSame(1234.0, (float) $path->modal_price);
        $this->assertSame('available', $path->status);
        $this->assertNotNull($path->last_sync_at);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'DigiflazzSyncJob: skipped invalid pricelist entries.'
                    && ($context['skipped_invalid'] ?? null) === 1
                    && ($context['updated'] ?? null) === 1;
            });
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Test store description',
            'keywords' => 'test',
            'logo_header' => 'assets/logo/header.png',
            'logo_footer' => 'assets/logo/footer.png',
            'logo_favicon' => 'assets/logo/favicon.ico',
            'url_wa' => 'https://wa.me/620000000000',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'topupindo_api' => 'test-topupindo-key',
            'paydisini_apikey' => 'test-paydisini-key',
            'username_digi' => 'test-digi-user',
            'api_key_digi' => 'test-digi-key',
            'order_prefik' => 'TS',
            'profit_member' => 10,
            'profit_platinum' => 10,
            'profit_gold' => 10,
        ], $overrides));
    }

    private function createLayanan(): Layanan
    {
        return Layanan::query()->create([
            'kategori_id' => '1',
            'layanan' => 'Mobile Legends 10 Diamonds',
            'provider_id' => 'DF-ML10',
            'harga' => 1000,
            'harga_member' => 1100,
            'harga_platinum' => 1050,
            'harga_gold' => 1080,
            'harga_flash_sale' => 0,
            'profit_member' => 10,
            'profit_platinum' => 5,
            'profit_gold' => 8,
            'is_flash_sale' => false,
            'stock_flash_sale' => 0,
            'catatan' => '',
            'status' => 'active',
            'provider' => 'digiflazz',
        ]);
    }
}

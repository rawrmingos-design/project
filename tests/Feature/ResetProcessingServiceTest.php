<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembelian;
use App\Models\ProviderPath;
use App\Models\SettingWeb;
use App\Services\OrderProcessingService;
use App\Services\ProviderRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_prefers_active_layanan_and_versioned_attempt_reference_for_reset_transactions(): void
    {
        $this->seedSettingWeb();
        $kategori = $this->createKategori();

        Layanan::create([
            'kategori_id' => (string) $kategori->id,
            'layanan' => 'Weekly Pass',
            'provider_id' => 'ML-WP',
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'available',
            'provider' => 'digiflazz',
            'catatan' => 'Legacy current provider',
            'is_flash_sale' => 0,
        ]);

        $activeLayanan = Layanan::create([
            'kategori_id' => (string) $kategori->id,
            'layanan' => 'Weekly Pass',
            'provider_id' => 'ML-WP',
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'available',
            'provider' => 'bangjeff',
            'catatan' => 'Reset target provider',
            'is_flash_sale' => 0,
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-RESET-PROCESS-001',
            'username' => 'reset-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Reset User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'invoice_version' => 1,
            'display_order_id' => 'INV-RESET-PROCESS-001_001',
            'active_attempt_reference' => 'INV-RESET-PROCESS-001_001',
            'active_layanan_id' => $activeLayanan->id,
            'active_provider_code' => 'bangjeff',
            'active_provider_sku' => 'ML-WP',
            'reset_status' => 'requested',
            'reset_count' => 1,
        ]);

        $service = new RecordingOrderProcessingService(app(ProviderRoutingService::class));

        $result = $service->process($pembelian);

        $this->assertTrue($result['success']);
        $this->assertSame('bangjeff', $service->dispatches[0]['provider_code']);
        $this->assertSame('ML-WP', $service->dispatches[0]['sku']);
        $this->assertSame('INV-RESET-PROCESS-001_001', $service->dispatches[0]['provider_reference']);
        $this->assertSame('INV-RESET-PROCESS-001', $pembelian->order_id);
    }

    public function test_process_keeps_reset_transaction_on_explicit_active_provider_even_when_best_route_prefers_another_provider(): void
    {
        $this->seedSettingWeb();
        $kategori = $this->createKategori();

        $activeLayanan = Layanan::create([
            'kategori_id' => (string) $kategori->id,
            'layanan' => 'Weekly Pass',
            'provider_id' => 'ML-WP',
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'available',
            'provider' => 'bangjeff',
            'catatan' => 'Reset target provider',
            'is_flash_sale' => 0,
        ]);

        ProviderPath::create([
            'layanan_id' => $activeLayanan->id,
            'provider_code' => 'digiflazz',
            'provider_sku' => 'ML-WP-DIGI',
            'modal_price' => 10000,
            'priority' => 1,
            'status' => 'available',
        ]);

        ProviderPath::create([
            'layanan_id' => $activeLayanan->id,
            'provider_code' => 'bangjeff',
            'provider_sku' => 'ML-WP',
            'modal_price' => 11000,
            'priority' => 2,
            'status' => 'available',
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-RESET-ROUTE-LOCK-001',
            'username' => 'reset-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Reset User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'invoice_version' => 1,
            'display_order_id' => 'INV-RESET-ROUTE-LOCK-001_001',
            'active_attempt_reference' => 'INV-RESET-ROUTE-LOCK-001_001',
            'active_layanan_id' => $activeLayanan->id,
            'active_provider_code' => 'bangjeff',
            'active_provider_sku' => 'ML-WP',
            'reset_status' => 'requested',
            'reset_count' => 1,
        ]);

        $service = new RecordingOrderProcessingService(app(ProviderRoutingService::class));

        $result = $service->process($pembelian);

        $this->assertTrue($result['success']);
        $this->assertSame('bangjeff', $service->dispatches[0]['provider_code']);
        $this->assertSame('ML-WP', $service->dispatches[0]['sku']);
        $this->assertNotSame('digiflazz', $service->dispatches[0]['provider_code']);
        $this->assertNotSame('ML-WP-DIGI', $service->dispatches[0]['sku']);
    }

    public function test_process_falls_back_to_legacy_layanan_for_untouched_records(): void
    {
        $this->seedSettingWeb();
        $kategori = $this->createKategori();

        Layanan::create([
            'kategori_id' => (string) $kategori->id,
            'layanan' => 'Diamond 86',
            'provider_id' => 'ML-86',
            'harga' => 20000,
            'harga_member' => 19500,
            'harga_platinum' => 19000,
            'harga_gold' => 18500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'available',
            'provider' => 'digiflazz',
            'catatan' => 'Legacy provider',
            'is_flash_sale' => 0,
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-LEGACY-PROCESS-001',
            'username' => 'legacy-user',
            'user_id' => '20002',
            'zone' => '3002',
            'nickname' => 'Legacy User',
            'layanan' => 'Diamond 86',
            'harga' => 20000,
            'profit' => 1000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
        ]);

        $service = new RecordingOrderProcessingService(app(ProviderRoutingService::class));

        $result = $service->process($pembelian);

        $this->assertTrue($result['success']);
        $this->assertSame('digiflazz', $service->dispatches[0]['provider_code']);
        $this->assertSame('INV-LEGACY-PROCESS-001', $service->dispatches[0]['provider_reference']);
    }

    public function test_process_does_not_fall_back_to_text_layanan_when_active_layanan_id_is_invalid(): void
    {
        $this->seedSettingWeb();
        $kategori = $this->createKategori();

        Layanan::create([
            'kategori_id' => (string) $kategori->id,
            'layanan' => 'Weekly Pass',
            'provider_id' => 'ML-WP',
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'available',
            'provider' => 'digiflazz',
            'catatan' => 'Legacy provider',
            'is_flash_sale' => 0,
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-RESET-MISSING-LAYANAN-001',
            'username' => 'broken-reset-user',
            'user_id' => '30003',
            'zone' => '4003',
            'nickname' => 'Broken Reset User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'active_layanan_id' => 999999,
            'display_order_id' => 'INV-RESET-MISSING-LAYANAN-001_001',
            'active_attempt_reference' => 'INV-RESET-MISSING-LAYANAN-001_001',
            'invoice_version' => 1,
            'reset_status' => 'requested',
            'reset_count' => 1,
        ]);

        $service = new RecordingOrderProcessingService(app(ProviderRoutingService::class));

        $result = $service->process($pembelian);

        $this->assertFalse($result['success']);
        $this->assertSame([], $service->dispatches);
        $this->assertSame('Active layanan not found in database: 999999', $result['message']);
    }

    private function createKategori(): Kategori
    {
        return Kategori::create([
            'nama' => 'Mobile Legends',
            'sub_nama' => 'Mobile Legends',
            'kode' => 'mobile-legends',
            'status' => 'active',
            'thumbnail' => 'assets/thumbnail/mobile-legends.png',
            'banner' => 'assets/banner/mobile-legends.png',
            'tipe' => 'game',
            'server_id' => true,
            'require_user_id' => true,
        ]);
    }

    private function seedSettingWeb(): void
    {
        SettingWeb::create([
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'url_wa' => 'wa.me/test',
            'url_ig' => 'instagram.com/test',
            'url_tiktok' => 'tiktok.com/test',
            'url_youtube' => 'youtube.com/test',
            'url_fb' => 'facebook.com/test',
            'topupindo_api' => 'test_api',
            'warna1' => '#222222',
            'warna2' => '#d06800',
            'warna3' => '#ffa54a',
            'warna4' => '#ff8040',
            'paydisini_apikey' => 'test_paydisini',
            'tripay_api' => 'test_api_key',
            'tripay_merchant_code' => 'test_merchant',
            'tripay_private_key' => 'test_private',
            'username_digi' => 'test_digi',
            'api_key_digi' => 'test_digi_key',
            'apigames_secret' => 'test_apigames_secret',
            'apigames_merchant' => 'test_apigames_merchant',
            'vip_apiid' => 'test_vip_id',
            'vip_apikey' => 'test_vip_key',
            'apikey_bangjeff' => 'test_bangjeff_key',
            'order_prefik' => 'INV',
        ]);
    }
}

class RecordingOrderProcessingService extends OrderProcessingService
{
    public array $dispatches = [];

    protected function dispatchToProvider(
        string $providerCode,
        array $credentials,
        mixed $uid,
        mixed $zone,
        mixed $sku,
        string $providerReference
    ): array {
        $this->dispatches[] = [
            'provider_code' => $providerCode,
            'credentials' => $credentials,
            'uid' => $uid,
            'zone' => $zone,
            'sku' => $sku,
            'provider_reference' => $providerReference,
        ];

        return [
            'success' => true,
            'order_status' => 'Pending',
            'transaction_id' => 'TX-' . $providerReference,
            'message' => 'Provider accepted order',
            'sn' => null,
            'provider' => $providerCode,
        ];
    }
}

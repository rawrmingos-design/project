<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Services\OrderProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiGamesRetryStatusModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_mode_uses_apigames_status_endpoint_with_active_reference(): void
    {
        $this->seedSettingWeb();
        $layanan = $this->createLayanan();

        Http::fake(function ($request) {
            $this->assertSame('https://v1.apigames.id/v2/transaksi/status', $request->url());
            $payload = $request->data();
            $this->assertSame('INV-APIGAMES-001_001', $payload['ref_id'] ?? null);

            return Http::response([
                'status' => 1,
                'data' => [
                    'trx_id' => 'TRX-APIGAMES-001',
                    'ref_id' => 'INV-APIGAMES-001_001',
                    'status' => 'Proses',
                    'sn' => 'TRACE-001',
                    'message' => 'Masih proses',
                ],
            ]);
        });

        $pembelian = Pembelian::create([
            'order_id' => 'INV-APIGAMES-001',
            'username' => 'apigames-user',
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'API Games User',
            'layanan' => 'ML 86',
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'apigames',
            'active_provider_sku' => 'ML86',
            'provider_order_id' => 'TRX-APIGAMES-OLD',
            'status' => 'Processing',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
            'invoice_version' => 1,
            'display_order_id' => 'INV-APIGAMES-001_001',
            'active_attempt_reference' => 'INV-APIGAMES-001_001',
            'reset_status' => 'processing',
        ]);

        $result = app(OrderProcessingService::class)->process($pembelian, 'retry_status');

        $this->assertTrue($result['success']);
        $this->assertSame('Processing', $result['order_status']);
        $this->assertSame('TRX-APIGAMES-001', $result['transaction_id']);
        $this->assertSame('TRACE-001', $result['sn']);
    }

    private function createLayanan(): Layanan
    {
        $kategori = Kategori::create([
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

        return Layanan::create([
            'kategori_id' => (string) $kategori->id,
            'layanan' => 'ML 86',
            'provider_id' => 'ML86',
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'available',
            'provider' => 'apigames',
            'catatan' => 'API Games service',
            'is_flash_sale' => 0,
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
            'apigames_secret' => 'secret-123',
            'apigames_merchant' => 'merchant-123',
            'vip_apiid' => 'test_vip_id',
            'vip_apikey' => 'test_vip_key',
            'apikey_bangjeff' => 'test_bangjeff_key',
            'order_prefik' => 'INV',
        ]);
    }
}

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

class VipRetryStatusModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_mode_uses_vip_status_endpoint_instead_of_creating_new_order(): void
    {
        $this->seedSettingWeb();
        $layanan = $this->createVipLayanan();

        Http::fake(function ($request) {
            parse_str($request->body(), $payload);

            $this->assertSame('status', $payload['type'] ?? null);
            $this->assertSame('VP123', $payload['trxid'] ?? null);
            $this->assertArrayNotHasKey('service', $payload);

            return Http::response([
                'result' => true,
                'message' => 'Status fetched',
                'data' => [
                    'trxid' => 'VP123',
                    'status' => 'waiting',
                    'note' => 'Masih diproses',
                ],
            ]);
        });

        $pembelian = Pembelian::create([
            'order_id' => 'INV-VIP-RETRY-001',
            'username' => 'vip-user',
            'user_id' => '123456',
            'zone' => '2001',
            'nickname' => 'VIP User',
            'layanan' => $layanan->layanan,
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'vip',
            'active_provider_sku' => 'VIP-WP',
            'provider_order_id' => 'VP123',
            'status' => 'Processing',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
        ]);

        $result = app(OrderProcessingService::class)->process($pembelian, 'retry_status');

        $this->assertTrue($result['success']);
        $this->assertSame('Pending', $result['order_status']);
        $this->assertSame('VP123', $result['transaction_id']);
        $this->assertSame('Masih diproses', $result['sn']);
    }

    public function test_auto_mode_keeps_using_order_for_first_reset_dispatch_even_if_provider_order_id_exists(): void
    {
        $this->seedSettingWeb();
        $layanan = $this->createVipLayanan();

        Http::fake(function ($request) {
            parse_str($request->body(), $payload);

            $this->assertSame('order', $payload['type'] ?? null);
            $this->assertSame('VIP-WP', $payload['service'] ?? null);
            $this->assertSame('123456', $payload['data_no'] ?? null);
            $this->assertSame('2001', $payload['data_zone'] ?? null);
            $this->assertArrayNotHasKey('trxid', $payload);

            return Http::response([
                'result' => true,
                'message' => 'Order queued',
                'data' => [
                    'trxid' => 'VP999',
                    'status' => 'waiting',
                    'note' => 'Order baru',
                ],
            ]);
        });

        $pembelian = Pembelian::create([
            'order_id' => 'INV-VIP-RESET-001',
            'username' => 'vip-user',
            'user_id' => '123456',
            'zone' => '2001',
            'nickname' => 'VIP User',
            'layanan' => $layanan->layanan,
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'vip',
            'active_provider_sku' => 'VIP-WP',
            'provider_order_id' => 'OLD-DIGI-REF',
            'status' => 'Gagal',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
            'invoice_version' => 1,
            'display_order_id' => 'INV-VIP-RESET-001_001',
            'active_attempt_reference' => 'INV-VIP-RESET-001_001',
            'reset_status' => 'processing',
        ]);

        $result = app(OrderProcessingService::class)->process($pembelian);

        $this->assertTrue($result['success']);
        $this->assertSame('Pending', $result['order_status']);
        $this->assertSame('VP999', $result['transaction_id']);
    }

    private function createVipLayanan(): Layanan
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
            'layanan' => 'Weekly Pass',
            'provider_id' => 'VIP-WP',
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'available',
            'provider' => 'vip',
            'catatan' => 'VIP service',
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
            'apigames_secret' => 'test_apigames_secret',
            'apigames_merchant' => 'test_apigames_merchant',
            'vip_apiid' => 'test_vip_id',
            'vip_apikey' => 'test_vip_key',
            'apikey_bangjeff' => 'test_bangjeff_key',
            'order_prefik' => 'INV',
        ]);
    }
}


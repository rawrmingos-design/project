<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiGamesCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_apigames_callback_updates_order_using_active_attempt_reference(): void
    {
        Http::fake();

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

        $user = User::factory()->create([
            'username' => 'apigames-user',
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-API-RESET-001',
            'username' => $user->username,
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'API Games User',
            'layanan' => 'ML 86',
            'harga' => 15000,
            'profit' => 1000,
            'provider_order_id' => 'TRX-OLD',
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'invoice_version' => 1,
            'display_order_id' => 'INV-API-RESET-001_001',
            'active_attempt_reference' => 'INV-API-RESET-001_001',
            'active_provider_code' => 'apigames',
            'active_provider_sku' => 'ML86',
            'reset_status' => 'processing',
            'reset_count' => 1,
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-' . $pembelian->order_id,
        ]);

        $payload = [
            'merchant_id' => 'merchant-123',
            'trx_id' => 'TRX-NEW',
            'ref_id' => 'INV-API-RESET-001_001',
            'destination' => '12345678',
            'product_code' => 'ML86',
            'message' => 'status SUKSES',
            'status' => 'Sukses',
            'sn' => 'SN-APIGAMES',
        ];

        $signature = md5('merchant-123:secret-123:INV-API-RESET-001_001');

        $this->withHeaders([
            'X-Apigames-Authorization' => $signature,
        ])->postJson('/wejizy/apigames/callback', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $pembelian->refresh();

        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('TRX-NEW', $pembelian->provider_order_id);
        $this->assertSame('SN-APIGAMES', $pembelian->keterangan_sn);
    }
}

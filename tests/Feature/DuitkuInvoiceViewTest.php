<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuitkuInvoiceViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_duitku_invoice_hides_qr_and_keeps_payment_link(): void
    {
        $settings = SettingWeb::create([
            'id' => 1,
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

        view()->share('config', (object) array_merge([
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
        ], $settings->getAttributes()));

        Pembelian::create([
            'order_id' => 'INV-DUITKU-001',
            'username' => 'duitku-user',
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'Duitku User',
            'layanan' => 'Membership Mingguan',
            'harga' => 15000,
            'profit' => 1000,
            'provider_order_id' => '',
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::create([
            'order_id' => 'INV-DUITKU-001',
            'harga' => 15000,
            'no_pembayaran' => 'https://sandbox.duitku.com/payment/link-test',
            'no_pembeli' => '08123456789',
            'status' => 'Belum Lunas',
            'metode' => 'DUITKU',
            'reference' => 'DUITKU-INV-DUITKU-001',
        ]);

        $response = $this->get('/id/invoices/INV-DUITKU-001');

        $response->assertOk();
        $response->assertSee('Buka Link Pembayaran');
        $response->assertSee('https://sandbox.duitku.com/payment/link-test');
        $response->assertDontSee('id="qrisPaymentImage"', false);
        $response->assertDontSee('Unduh Kode QR / Screenshoot');
    }
}

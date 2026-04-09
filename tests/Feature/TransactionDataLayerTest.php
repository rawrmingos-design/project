<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionDataLayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
            'google_tag_manager_id' => 'GTM-TEST123',
        ]);

        view()->share('config', (object) array_merge([
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'google_tag_manager_id' => 'GTM-TEST123',
        ], $settings->getAttributes()));
    }

    public function test_order_page_renders_view_item_data_layer_payload(): void
    {
        $kategori = Kategori::create([
            'nama' => 'Mobile Legends',
            'sub_nama' => 'Top Up Mobile Legends',
            'kode' => 'mobile-legends',
            'tipe' => 'game',
            'server_id' => 1,
            'require_user_id' => 1,
            'thumbnail' => 'assets/thumbnail/mlbb.png',
            'banner' => 'assets/banner_game/mlbb-banner.png',
            'status' => 'active',
            'deskripsi_game' => '<p>Top up cepat.</p>',
            'deskripsi_field' => 'Isi UID dan zone.',
        ]);

        Layanan::create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Weekly Diamond Pass',
            'provider_id' => 'WDP',
            'provider' => 'digiflazz',
            'harga' => 32000,
            'harga_member' => 32000,
            'harga_platinum' => 32000,
            'harga_gold' => 32000,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
            'catatan' => 'Aman',
            'status' => 'available',
            'is_flash_sale' => 0,
        ]);

        Method::create([
            'name' => 'QRIS',
            'code' => 'QRIS',
            'payment' => 'tripay',
            'tipe' => 'e-wallet',
            'images' => 'qris.png',
            'keterangan' => 'QRIS',
            'fee_percent' => 0.7,
            'fix_fee' => 100,
            'statuspayment' => 1,
        ]);

        $response = $this->get('/id/mobile-legends');

        $response->assertOk();
        $response->assertSee('window.gtmOrderTracking', false);
        $response->assertSee('view_item', false);
        $response->assertSee('"currency":"IDR"', false);
        $response->assertSee('"item_name":"Weekly Diamond Pass"', false);
        $response->assertSee('"item_category":"Mobile Legends"', false);
    }

    public function test_pending_invoice_renders_add_payment_info_without_purchase(): void
    {
        $response = $this->getInvoiceResponse(
            paymentStatus: 'Belum Lunas',
            paymentMethodCode: 'QRIS',
            paymentMethodName: 'QRIS Tripay',
            amount: 27204,
        );

        $response->assertOk();
        $response->assertSee('invoice_viewed', false);
        $response->assertSee('add_payment_info', false);
        $response->assertSee('payment_pending', false);
        $response->assertDontSee('purchase', false);
        $response->assertSee('"transaction_id":"INV-GTM-001"', false);
        $response->assertSee('"payment_type":"QRIS Tripay"', false);
        $response->assertSee('"value":27204', false);
        $response->assertSee('"item_name":"Membership Mingguan"', false);
        $response->assertSee('"item_category":"Free Fire"', false);
        $response->assertDontSee('"email":"', false);
        $response->assertDontSee('"email_pembeli":', false);
        $response->assertDontSee('"uid":', false);
    }

    public function test_paid_invoice_renders_purchase_event_with_final_payment_value(): void
    {
        $response = $this->getInvoiceResponse(
            paymentStatus: 'Lunas',
            paymentMethodCode: 'QRIS',
            paymentMethodName: 'QRIS Tripay',
            amount: 27500,
        );

        $response->assertOk();
        $response->assertSee('purchase', false);
        $response->assertSee('"transaction_id":"INV-GTM-001"', false);
        $response->assertSee('"value":27500', false);
        $response->assertSee('"payment_type":"QRIS Tripay"', false);
        $response->assertDontSee('add_payment_info', false);
    }

    public function test_expired_invoice_renders_expired_event_without_purchase(): void
    {
        $response = $this->getInvoiceResponse(
            paymentStatus: 'Expired',
            paymentMethodCode: 'QRIS',
            paymentMethodName: 'QRIS Tripay',
            amount: 27500,
        );

        $response->assertOk();
        $response->assertSee('payment_expired', false);
        $response->assertDontSee('purchase', false);
    }

    public function test_pending_duitku_invoice_does_not_render_purchase_event(): void
    {
        $response = $this->getInvoiceResponse(
            paymentStatus: 'Belum Lunas',
            paymentMethodCode: 'DUITKU',
            paymentMethodName: 'Duitku Payment Link',
            amount: 15000,
            paymentReference: 'DUITKU-INV-GTM-001',
            paymentNumber: 'https://sandbox.duitku.com/payment/link-test',
        );

        $response->assertOk();
        $response->assertSee('add_payment_info', false);
        $response->assertDontSee('purchase', false);
    }

    private function getInvoiceResponse(
        string $paymentStatus,
        string $paymentMethodCode,
        string $paymentMethodName,
        int $amount,
        string $paymentReference = 'REF-INV-GTM-001',
        string $paymentNumber = '1234567890',
    ) {
        $kategori = Kategori::create([
            'nama' => 'Free Fire',
            'sub_nama' => 'Top Up Free Fire',
            'kode' => 'free-fire',
            'tipe' => 'game',
            'server_id' => 1,
            'require_user_id' => 1,
            'thumbnail' => 'assets/thumbnail/ff.png',
            'banner' => 'assets/banner_game/ff-banner.png',
            'status' => 'active',
        ]);

        Layanan::create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Membership Mingguan',
            'provider_id' => 'FFMM',
            'provider' => 'digiflazz',
            'harga' => $amount,
            'harga_member' => $amount,
            'harga_platinum' => $amount,
            'harga_gold' => $amount,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
            'catatan' => 'Aman',
            'status' => 'available',
            'is_flash_sale' => 0,
        ]);

        Method::create([
            'name' => $paymentMethodName,
            'code' => $paymentMethodCode,
            'payment' => strtolower($paymentMethodCode) === 'duitku' ? 'duitku' : 'tripay',
            'tipe' => 'e-wallet',
            'images' => strtolower($paymentMethodCode) . '.png',
            'keterangan' => $paymentMethodName,
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);

        Pembelian::create([
            'order_id' => 'INV-GTM-001',
            'username' => 'gtm-user',
            'user_id' => '1840180550',
            'zone' => '1001',
            'nickname' => 'GTM User',
            'layanan' => 'Membership Mingguan',
            'harga' => $amount,
            'profit' => 1000,
            'provider_order_id' => '',
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'email_pembeli' => 'gtm@example.test',
        ]);

        Pembayaran::create([
            'order_id' => 'INV-GTM-001',
            'harga' => $amount,
            'no_pembayaran' => $paymentNumber,
            'no_pembeli' => '08123456789',
            'status' => $paymentStatus,
            'metode' => $paymentMethodCode,
            'reference' => $paymentReference,
        ]);

        return $this->get('/id/invoices/INV-GTM-001');
    }
}

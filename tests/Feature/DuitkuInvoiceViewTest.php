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
        $settings = SettingWeb::updateOrCreate(['id' => 1], [
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'url_wa' => '081234567890',
            'url_ig' => 'test',
            'url_tiktok' => 'test',
            'url_youtube' => 'test',
            'url_fb' => 'test',
            'topupindo_api' => 'test',
            'warna1' => '#000000',
            'warna2' => '#000000',
            'warna3' => '#000000',
            'warna4' => '#000000',
            'order_prefik' => 'TRX',
            'paydisini_apikey' => 'test',
            'tripay_api' => 'test',
            'tripay_merchant_code' => 'test',
            'tripay_private_key' => 'test',
            'vip_apiid' => 'test',
            'vip_apikey' => 'test',
            'duitku_mode' => 'sandbox',
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
        $response->assertDontSee('qrImageUrl');
    }

    public function test_unpaid_duitku_qris_shows_inline_qr(): void
    {
        $settings = SettingWeb::updateOrCreate(['id' => 2], [
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'url_wa' => '081234567890',
            'url_ig' => 'test',
            'url_tiktok' => 'test',
            'url_youtube' => 'test',
            'url_fb' => 'test',
            'topupindo_api' => 'test',
            'warna1' => '#000000',
            'warna2' => '#000000',
            'warna3' => '#000000',
            'warna4' => '#000000',
            'order_prefik' => 'TRX',
            'paydisini_apikey' => 'test',
            'tripay_api' => 'test',
            'tripay_merchant_code' => 'test',
            'tripay_private_key' => 'test',
            'vip_apiid' => 'test',
            'vip_apikey' => 'test',
            'duitku_mode' => 'sandbox',
        ]);

        view()->share('config', (object) array_merge([
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
        ], $settings->getAttributes()));

        Pembelian::create([
            'order_id' => 'INV-DUITKU-QR-001',
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
            'order_id' => 'INV-DUITKU-QR-001',
            'harga' => 15000,
            'no_pembayaran' => '00020101021126560012ID.CO.DANA.WWW011893600915306634704021422709210967000303UMI51440014ID.CO.QRIS.WWW0215ID10230231534060303UMI5204581453033605405150005802ID5910KOTA TANG6009TANGERANG61071514321623801121020211116051515306634704071300908819037463045E67',
            'no_pembeli' => '08123456789',
            'status' => 'Belum Lunas',
            'metode' => 'DUITKU',
            'reference' => 'DUITKU-INV-DUITKU-QR-001',
        ]);

        $response = $this->get('/id/invoices/INV-DUITKU-QR-001');

        $response->assertOk();
        $response->assertDontSee('Buka Link Pembayaran');
        $response->assertSee('id="qrisPaymentImage"', false);
        $response->assertSee('Unduh Kode QR / Screenshoot');
        $response->assertSee('00020101021126560012ID.CO.DANA');
    }

    public function test_unpaid_duitku_va_shows_inline_number(): void
    {
        $settings = SettingWeb::updateOrCreate(['id' => 3], [
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'url_wa' => '081234567890',
            'url_ig' => 'test',
            'url_tiktok' => 'test',
            'url_youtube' => 'test',
            'url_fb' => 'test',
            'topupindo_api' => 'test',
            'warna1' => '#000000',
            'warna2' => '#000000',
            'warna3' => '#000000',
            'warna4' => '#000000',
            'order_prefik' => 'TRX',
            'paydisini_apikey' => 'test',
            'tripay_api' => 'test',
            'tripay_merchant_code' => 'test',
            'tripay_private_key' => 'test',
            'vip_apiid' => 'test',
            'vip_apikey' => 'test',
            'duitku_mode' => 'sandbox',
        ]);

        view()->share('config', (object) array_merge([
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
        ], $settings->getAttributes()));

        Pembelian::create([
            'order_id' => 'INV-DUITKU-VA-001',
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
            'order_id' => 'INV-DUITKU-VA-001',
            'harga' => 15000,
            'no_pembayaran' => '8888000012345678',
            'no_pembeli' => '08123456789',
            'status' => 'Belum Lunas',
            'metode' => 'DUITKU',
            'reference' => 'DUITKU-INV-DUITKU-VA-001',
        ]);

        $response = $this->get('/id/invoices/INV-DUITKU-VA-001');

        $response->assertOk();
        $response->assertDontSee('Buka Link Pembayaran');
        $response->assertDontSee('id="qrisPaymentImage"', false);
        $response->assertSee('No Pembayaran');
        $response->assertSee('8888000012345678');
    }

    public function test_paid_invoice_hides_payment_number(): void
    {
        $this->createInvoiceWithPaymentStatus('INV-DUITKU-PAID-001', 'Lunas', 'PAYMENT-SECRET-PAID-001');

        $response = $this->get('/id/invoices/INV-DUITKU-PAID-001');

        $response->assertOk();
        $response->assertSee('Paid');
        $response->assertDontSee('No Pembayaran');
        $response->assertDontSee('PAYMENT-SECRET-PAID-001');
    }

    public function test_expired_invoice_hides_payment_number(): void
    {
        $this->createInvoiceWithPaymentStatus('INV-DUITKU-EXPIRED-001', 'Expired', 'PAYMENT-SECRET-EXPIRED-001');

        $response = $this->get('/id/invoices/INV-DUITKU-EXPIRED-001');

        $response->assertOk();
        $response->assertSee('Expired');
        $response->assertDontSee('No Pembayaran');
        $response->assertDontSee('PAYMENT-SECRET-EXPIRED-001');
    }

    private function createInvoiceWithPaymentStatus(string $orderId, string $paymentStatus, string $paymentNumber): void
    {
        $settings = SettingWeb::updateOrCreate(['id' => 4], [
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'url_wa' => '081234567890',
            'url_ig' => 'test',
            'url_tiktok' => 'test',
            'url_youtube' => 'test',
            'url_fb' => 'test',
            'topupindo_api' => 'test',
            'warna1' => '#000000',
            'warna2' => '#000000',
            'warna3' => '#000000',
            'warna4' => '#000000',
            'order_prefik' => 'TRX',
            'paydisini_apikey' => 'test',
            'tripay_api' => 'test',
            'tripay_merchant_code' => 'test',
            'tripay_private_key' => 'test',
            'vip_apiid' => 'test',
            'vip_apikey' => 'test',
            'duitku_mode' => 'sandbox',
        ]);

        view()->share('config', (object) array_merge([
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
        ], $settings->getAttributes()));

        Pembelian::create([
            'order_id' => $orderId,
            'username' => 'duitku-user',
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'Duitku User',
            'layanan' => 'Membership Mingguan',
            'harga' => 15000,
            'profit' => 1000,
            'provider_order_id' => '',
            'status' => $paymentStatus === 'Lunas' ? 'Success' : 'Pending',
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::create([
            'order_id' => $orderId,
            'harga' => 15000,
            'no_pembayaran' => $paymentNumber,
            'no_pembeli' => '08123456789',
            'status' => $paymentStatus,
            'metode' => 'DUITKU',
            'reference' => "DUITKU-{$orderId}",
        ]);
    }
}

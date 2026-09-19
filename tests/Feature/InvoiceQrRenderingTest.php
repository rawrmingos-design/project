<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceQrRenderingTest extends TestCase
{
    use RefreshDatabase;

    private const TRIPAY_QR_URL = 'https://tripay.co.id/qr/DEV-E2ETEST0001';

    #[Test]
    public function tripay_qris_invoice_exposes_same_origin_qr_proxy_url(): void
    {
        $orderId = 'INV-QR-TRIPAY-001';
        $this->seedInvoiceOrder($orderId, 'Belum Lunas', self::TRIPAY_QR_URL);

        $this->get(route('pembelian', ['order' => $orderId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Invoice')
                ->where('invoice.payment.showQrImage', true)
                ->where('invoice.payment.showPayButton', false)
                ->where('invoice.payment.qrImageUrl', fn ($value) => is_string($value)
                    && str_contains($value, '/id/invoices/' . $orderId . '/payment-qr'))
            );
    }

    #[Test]
    public function raw_qris_payload_is_rendered_locally_as_png_data_uri(): void
    {
        $orderId = 'INV-QR-RAW-001';
        $this->seedInvoiceOrder(
            $orderId,
            'Belum Lunas',
            '00020101021226610014COM.GO-JEK.WWW01189360091432506004040215ID102226550003UMI0000000000005204581253033605802ID5910TOKO TEST6007JAKARTA61051234062070703A016304ABCD',
        );

        $this->get(route('pembelian', ['order' => $orderId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Invoice')
                ->where('invoice.payment.showQrImage', true)
                ->where('invoice.payment.qrImageUrl', fn ($value) => is_string($value)
                    && str_starts_with($value, 'data:image/png;base64,')
                    && ! str_contains($value, 'qrserver'))
            );
    }

    #[Test]
    public function non_qr_checkout_url_keeps_pay_button_flow(): void
    {
        $orderId = 'INV-QR-CHECKOUT-001';
        $this->seedInvoiceOrder($orderId, 'Belum Lunas', 'https://app.duitku.com/payment/INV-001');

        $this->get(route('pembelian', ['order' => $orderId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Invoice')
                ->where('invoice.payment.showQrImage', false)
                ->where('invoice.payment.showPayButton', true)
            );
    }

    #[Test]
    public function qr_proxy_streams_allowlisted_remote_image(): void
    {
        $orderId = 'INV-QR-PROXY-001';
        $this->seedInvoiceOrder($orderId, 'Belum Lunas', self::TRIPAY_QR_URL);

        $png = $this->tinyPng();
        Http::fake([
            'tripay.co.id/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        $response = $this->get(route('pembelian.qr', ['order' => $orderId]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertSame($png, $response->baseResponse->getContent());

        Http::assertSent(fn ($request) => str_contains($request->url(), 'tripay.co.id/qr/DEV-E2ETEST0001'));
        Http::assertSentCount(1);
    }

    #[Test]
    public function qr_proxy_rejects_hosts_outside_the_allowlist(): void
    {
        $orderId = 'INV-QR-EVIL-001';
        $this->seedInvoiceOrder($orderId, 'Belum Lunas', 'https://evil.example.com/qr/x.png');

        Http::fake();

        $this->get(route('pembelian.qr', ['order' => $orderId]))->assertNotFound();
        Http::assertNothingSent();
    }

    #[Test]
    public function qr_proxy_rejects_settled_payments(): void
    {
        $orderId = 'INV-QR-PAID-001';
        $this->seedInvoiceOrder($orderId, 'Paid', self::TRIPAY_QR_URL);

        Http::fake();

        $this->get(route('pembelian.qr', ['order' => $orderId]))->assertNotFound();
        Http::assertNothingSent();
    }

    private function tinyPng(): string
    {
        return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    }

    private function seedInvoiceOrder(string $orderId, string $paymentStatus, string $paymentValue): void
    {
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->withoutVite();

        SettingWeb::create([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Demo storefront',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/testweb',
            'url_tiktok' => 'https://tiktok.com/@testweb',
            'url_youtube' => 'https://youtube.com/@testweb',
            'url_fb' => 'https://facebook.com/testweb',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'DMO',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
            'public_theme' => 'istanatopup',
        ]);

        $category = Kategori::factory()->create([
            'nama' => 'Mobile Legends',
            'tipe' => 'game',
            'thumbnail' => 'assets/category/mobile-legends.webp',
        ]);

        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Mobile Legends 86 Diamond',
            'harga' => 50000,
        ]);

        $paymentCategory = PaymentDisplayCategory::create([
            'code' => 'qris',
            'label' => 'QRIS',
            'display_style' => 'flat',
            'sort_order' => 1,
            'is_visible' => true,
            'icon' => 'fa-solid fa-qrcode',
        ]);

        Method::create([
            'code' => 'QRIS',
            'name' => 'QRIS Test',
            'payment' => 'tripay',
            'keterangan' => 'QRIS test method',
            'tipe' => 'qris',
            'payment_display_category_id' => $paymentCategory->id,
            'images' => 'qris.png',
            'statuspayment' => 1,
        ]);

        Pembelian::factory()->create([
            'order_id' => $orderId,
            'user_id' => '12345678',
            'zone' => '1234',
            'nickname' => 'TestPlayer',
            'email_pembeli' => 'test@example.com',
            'layanan' => 'Mobile Legends 86 Diamond',
            'harga' => 50000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'voucher' => null,
            'keterangan_sn' => null,
        ]);

        Pembayaran::create([
            'order_id' => $orderId,
            'harga' => '50000',
            'no_pembayaran' => $paymentValue,
            'no_pembeli' => '08123456789',
            'status' => $paymentStatus,
            'metode' => 'QRIS',
            'reference' => 'REF-' . $orderId,
        ]);
    }
}

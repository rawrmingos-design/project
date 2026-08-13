<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Support\InvoiceRealtimeStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicePageControllerRealtimePropsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function inertia_invoice_page_includes_realtime_channel_and_event_props(): void
    {
        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
        $this->withoutVite();

        $orderId = 'INV-INERTIA-REALTIME-001';
        $this->seedBangjeffInvoiceData($orderId);

        $this->get(route('pembelian', ['order' => $orderId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Invoice')
                ->where('invoice.orderId', $orderId)
                ->where('invoice.fulfillment.serviceNote', null)
                ->where('invoice.fulfillment.transactionType', 'game')
                ->where('invoice.fulfillment.joki', null)
                ->where('invoice.rating.eligible', false)
                ->where('invoice.rating.submitted', false)
                ->where('invoice.rating.categoryName', 'Mobile Legends')
                ->where('invoice.payment.methodCategory.label', 'QRIS')
                ->where('invoice.payment.methodCategory.icon', 'fa-solid fa-qrcode')
                ->where('invoice.payment.methodCategory.code', 'qris')
                ->where('invoice.payment.hint', 'Gunakan e-wallet atau aplikasi mobile banking untuk melakukan scan QR pembayaran.')
                ->where('invoice.realtime.channel', InvoiceRealtimeStatus::channelName($orderId))
                ->where('invoice.realtime.event', '.InvoiceStatusUpdated')
                ->has('invoice.gtmEvents', 3)
                ->where('invoice.gtmEvents.0.name', 'invoice_viewed')
                ->where('invoice.gtmEvents.1.name', 'add_payment_info')
                ->where('invoice.gtmEvents.2.name', 'payment_pending')
                ->where('invoice.gtmEvents.0.dedupe_key', "invoice_viewed:{$orderId}")
                ->missing('invoice.gtmEvents.0.payload.email_pembeli')
                ->missing('invoice.gtmEvents.0.payload.no_pembeli')
                ->missing('invoice.gtmEvents.0.payload.uid')
                ->where('invoice.gtmEvents.0.payload.customer_email_sha256', fn (string $hash) => $hash === hash('sha256', 'test@example.com'))
                ->where('invoice.gtmEvents.0.payload.customer_phone_sha256', fn (string $hash) => $hash === hash('sha256', '628123456789'))
                ->where('invoice.gtmEvents.0.payload.game_user_id', '12345678')
                ->where('invoice.gtmEvents.0.payload.game_zone', '1234')
                ->where('invoice.gtmEvents.0.payload.game_nickname', 'TestPlayer')
                ->where('invoice.gtmEvents.0.payload.enhanced_conversion_data.email', fn (string $hash) => $hash === hash('sha256', 'test@example.com'))
                ->where('invoice.gtmEvents.0.payload.enhanced_conversion_data.phone_number', fn (string $hash) => $hash === hash('sha256', '628123456789'))
                ->where('invoice.gtmEvents.0.payload.items.0.item_name', 'Mobile Legends 86 Diamond')
                ->where('invoice.gtmEvents.0.payload.currency', 'IDR')
                ->missing('invoice.gtmEvents.0.payload.gtm_custom_head_script')
                ->missing('invoice.gtmEvents.0.payload.gtm_custom_body_noscript')
            );
    }

    private function seedBangjeffInvoiceData(string $orderId): void
    {
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
            'public_theme' => 'bangjeff',
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
            'no_pembayaran' => 'QRIS-' . $orderId,
            'no_pembeli' => '08123456789',
            'status' => 'Belum Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-' . $orderId,
        ]);
    }
}

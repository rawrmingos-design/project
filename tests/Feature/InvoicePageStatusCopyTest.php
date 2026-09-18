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
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicePageStatusCopyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function fresh_unpaid_invoice_keeps_pending_hero_and_intro(): void
    {
        $orderId = 'INV-COPY-PENDING-001';
        $this->seedInvoiceOrder($orderId, 'Pending', 'Belum Lunas');

        $this->get(route('pembelian', ['order' => $orderId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Invoice')
                ->where('invoice.hero.title', 'Harap lengkapi pembayaran.')
                ->where('invoice.intro.state', 'pending')
            );
    }

    #[Test]
    public function unpaid_invoice_past_fallback_deadline_renders_expired_hero_and_intro(): void
    {
        $orderId = 'INV-COPY-LAPSED-001';
        $this->seedInvoiceOrder($orderId, 'Pending', 'Belum Lunas', now()->subHours(6));

        $this->get(route('pembelian', ['order' => $orderId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Invoice')
                ->where('invoice.hero.title', 'Invoice sudah kedaluwarsa.')
                ->where('invoice.hero.description', fn (string $value) => str_contains($value, 'telah habis'))
                ->where('invoice.intro.state', 'expired')
                ->where('invoice.intro.badgeText', 'Pembayaran Kedaluwarsa')
            );
    }

    #[Test]
    public function paid_failed_invoice_renders_failure_copy_and_failed_intro(): void
    {
        $orderId = 'INV-COPY-PAID-FAILED-001';
        $this->seedInvoiceOrder($orderId, 'Gagal', 'Paid');

        $this->get(route('pembelian', ['order' => $orderId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Invoice')
                ->where('invoice.hero.title', 'Pembayaran diterima, namun transaksi gagal.')
                ->where('invoice.hero.description', fn (string $value) => str_contains($value, 'customer service'))
                ->where('invoice.intro.state', 'failed')
                ->where('invoice.intro.badgeText', 'Transaksi Gagal')
            );
    }

    #[Test]
    public function paid_processing_invoice_keeps_processing_copy(): void
    {
        $orderId = 'INV-COPY-PAID-PROSES-001';
        $this->seedInvoiceOrder($orderId, 'Proses', 'Paid');

        $this->get(route('pembelian', ['order' => $orderId]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Invoice')
                ->where('invoice.hero.title', 'Pembayaran sudah diterima.')
                ->where('invoice.hero.description', fn (string $value) => str_contains($value, 'sedang diproses'))
                ->where('invoice.intro.state', 'paid')
            );
    }

    private function seedInvoiceOrder(
        string $orderId,
        string $orderStatus,
        string $paymentStatus,
        ?\DateTimeInterface $pembelianCreatedAt = null,
    ): void {
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

        $pembelian = [
            'order_id' => $orderId,
            'user_id' => '12345678',
            'zone' => '1234',
            'nickname' => 'TestPlayer',
            'email_pembeli' => 'test@example.com',
            'layanan' => 'Mobile Legends 86 Diamond',
            'harga' => 50000,
            'status' => $orderStatus,
            'tipe_transaksi' => 'game',
            'voucher' => null,
            'keterangan_sn' => null,
        ];

        if ($pembelianCreatedAt !== null) {
            $pembelian['created_at'] = $pembelianCreatedAt;
        }

        Pembelian::factory()->create($pembelian);

        Pembayaran::create([
            'order_id' => $orderId,
            'harga' => '50000',
            'no_pembayaran' => 'QRIS-' . $orderId,
            'no_pembeli' => '08123456789',
            'status' => $paymentStatus,
            'metode' => 'QRIS',
            'reference' => 'REF-' . $orderId,
        ]);
    }
}

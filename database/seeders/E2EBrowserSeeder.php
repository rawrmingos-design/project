<?php

namespace Database\Seeders;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class E2EBrowserSeeder extends Seeder
{
    public const POPUP_ID = 900001;

    public function run(): void
    {
        DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'P06 Browser Test',
                'deskripsi_web' => 'Deterministic browser test storefront.',
                'keywords' => 'browser,test',
                'logo_header' => 'assets/logo/favicon.webp',
                'logo_footer' => 'assets/logo/favicon.webp',
                'logo_favicon' => 'assets/logo/favicon.webp',
                'url_wa' => 'https://wa.me/6200000000000',
                'url_ig' => 'https://instagram.com/example',
                'url_tiktok' => 'https://tiktok.com/@example',
                'url_youtube' => 'https://youtube.com/@example',
                'url_fb' => 'https://facebook.com/example',
                'topupindo_api' => '',
                'warna1' => '#111827',
                'warna2' => '#1f2937',
                'warna3' => '#f97316',
                'warna4' => '#fb923c',
                'paydisini_apikey' => '',
                'order_prefik' => 'E2E',
                'public_theme' => env('E2E_PUBLIC_THEME', 'bangjeff'),
                'home_popup_enabled' => true,
                'live_sales_enabled' => filter_var(env('E2E_LIVE_SALES_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
                'google_analytics_id' => null,
                'facebook_pixel_id' => null,
                'google_tag_manager_id' => null,
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-01 00:00:00',
            ],
        );

        DB::table('beritas')->updateOrInsert(
            ['id' => self::POPUP_ID],
            [
                'path' => null,
                'tipe' => 'popup',
                'urutan' => 0,
                'deskripsi' => '<p>Pengumuman browser test P06.</p>',
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-01 00:00:00',
            ],
        );

        $this->seedUsers();
        $this->seedStorefront();
        $this->seedInvoice();
    }

    private function seedUsers(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'e2e-member'],
            [
                'name' => 'E2E Member',
                'email' => 'e2e-member@example.test',
                'password' => Hash::make('e2e-password'),
                'role' => 'Member',
                'balance' => 100000,
                'point_balance' => 500,
                'no_wa' => '6281200000001',
                'affiliate_status' => 'inactive',
            ],
        );

        User::query()->updateOrCreate(
            ['username' => 'e2e-admin'],
            [
                'name' => 'E2E Admin',
                'email' => 'e2e-admin@example.test',
                'password' => Hash::make('e2e-password'),
                'role' => 'Admin',
                'balance' => 0,
                'point_balance' => 0,
                'no_wa' => '6281200000002',
                'affiliate_status' => 'inactive',
            ],
        );
    }

    private function seedInvoice(): void
    {
        $orderId = 'E2E-INVOICE-INTERNAL-001';
        $displayOrderId = 'E2E-INVOICE-INTERNAL-001_001';
        $category = Kategori::query()->updateOrCreate(
            ['kode' => 'e2e-invoice-game'],
            [
                'nama' => 'E2E Invoice Game',
                'sub_nama' => 'Deterministic invoice test game',
                'status' => 'active',
                'thumbnail' => 'assets/thumbnail/e2e-invoice-missing.webp',
                'tipe' => 'game',
                'server_id' => false,
                'require_user_id' => true,
            ],
        );
        Layanan::query()->updateOrCreate(
            ['kategori_id' => $category->id, 'provider_id' => 'e2e-invoice-product'],
            [
                'layanan' => 'E2E Invoice Product',
                'provider' => 'manual',
                'harga' => 10000,
                'harga_member' => 10000,
                'harga_platinum' => 10000,
                'harga_gold' => 10000,
                'profit_member' => 0,
                'profit_platinum' => 0,
                'profit_gold' => 0,
                'status' => 'available',
            ],
        );

        Pembelian::query()->updateOrCreate(
            ['order_id' => $orderId],
            [
                'base_order_id' => $orderId,
                'invoice_version' => 1,
                'display_order_id' => $displayOrderId,
                'active_attempt_reference' => $displayOrderId,
                'username' => 'Anonim',
                'user_id' => '12345678',
                'zone' => '1234',
                'nickname' => 'E2E Player',
                'email_pembeli' => 'e2e-invoice@example.test',
                'layanan' => 'E2E Invoice Product',
                'harga' => 10000,
                'profit' => 0,
                'status' => 'Pending',
                'tipe_transaksi' => 'game',
                'voucher' => null,
                'keterangan_sn' => null,
            ],
        );

        Pembayaran::query()->updateOrCreate(
            ['order_id' => $orderId],
            [
                'harga' => '10000',
                'no_pembayaran' => 'E2E-PAYMENT-001',
                'no_pembeli' => '6281200000001',
                'status' => 'Belum Lunas',
                'metode' => 'E2E_QRIS',
                'reference' => 'E2E-REFERENCE-001',
            ],
        );

        // State-coverage variants for invoice copy/tone regression (see invoice-detail.spec.js).
        $this->seedInvoiceVariant('E2E-INVOICE-PAID-FAILED-001', 'Gagal', 'Paid');
        $this->seedInvoiceVariant('E2E-INVOICE-LAPSED-001', 'Pending', 'Belum Lunas', 6);
        $this->seedInvoiceQris();
    }

    private function seedInvoiceVariant(string $orderId, string $orderStatus, string $paymentStatus, ?int $createdHoursAgo = null, string $metode = 'E2E_QRIS', ?string $paymentValue = null): void
    {
        $displayOrderId = $orderId . '_001';

        $pembelian = [
            'base_order_id' => $orderId,
            'invoice_version' => 1,
            'display_order_id' => $displayOrderId,
            'active_attempt_reference' => $displayOrderId,
            'username' => 'Anonim',
            'user_id' => '12345678',
            'zone' => '1234',
            'nickname' => 'E2E Player',
            'email_pembeli' => 'e2e-invoice@example.test',
            'layanan' => 'E2E Invoice Product',
            'harga' => 10000,
            'profit' => 0,
            'status' => $orderStatus,
            'tipe_transaksi' => 'game',
            'voucher' => null,
            'keterangan_sn' => null,
        ];

        if ($createdHoursAgo !== null) {
            $pembelian['created_at'] = now()->subHours($createdHoursAgo);
        }

        Pembelian::query()->updateOrCreate(['order_id' => $orderId], $pembelian);

        Pembayaran::query()->updateOrCreate(
            ['order_id' => $orderId],
            [
                'harga' => '10000',
                'no_pembayaran' => $paymentValue ?? ('E2E-PAYMENT-' . $orderId),
                'no_pembeli' => '6281200000001',
                'status' => $paymentStatus,
                'metode' => $metode,
                'reference' => 'E2E-REFERENCE-' . $orderId,
            ],
        );
    }

    private function seedInvoiceQris(): void
    {
        $baseUrl = rtrim((string) env('APP_URL', 'http://127.0.0.1'), '/');
        $qrRelativePath = 'assets/e2e/qr-fixture.png';

        // Generate a real QR PNG served by the app itself, so the invoice QR proxy
        // can be exercised end-to-end without calling any external service.
        $rendered = (string) (new QRCode(new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 8,
            'outputBase64' => true,
        ])))->render('E2E-QRIS-FIXTURE-' . str_repeat('ABCDEFGHIJ', 3));

        $absolutePath = public_path($qrRelativePath);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }
        file_put_contents($absolutePath, base64_decode(substr($rendered, strpos($rendered, ',') + 1)));

        $this->seedInvoiceVariant(
            'E2E-INVOICE-QRIS-001',
            'Pending',
            'Belum Lunas',
            null,
            'QRIS',
            $baseUrl . '/' . $qrRelativePath,
        );
    }

    private function seedStorefront(): void
    {
        $categoryType = CategoryType::query()->updateOrCreate(
            ['slug' => 'e2e-games'],
            ['name' => 'E2E Games', 'sort' => 1],
        );

        $category = Kategori::query()->updateOrCreate(
            ['kode' => 'e2e-game'],
            [
                'nama' => 'E2E Game',
                'sub_nama' => 'Deterministic test game',
                'status' => 'active',
                'thumbnail' => 'assets/logo/favicon.webp',
                'banner' => 'assets/logo/favicon.webp',
                'tipe' => 'game',
                'server_id' => false,
                'require_user_id' => true,
                'deskripsi_game' => '<p>Deterministic browser test category.</p>',
                'deskripsi_field' => 'Masukkan User ID untuk melanjutkan.',
                'category_type_id' => $categoryType->id,
            ],
        );

        $packagedProduct = Layanan::query()->updateOrCreate(
            ['kategori_id' => $category->id, 'provider_id' => 'e2e-product-1'],
            [
                'layanan' => 'E2E Product 10000',
                'provider' => 'manual',
                'harga' => 10000,
                'harga_member' => 10000,
                'harga_platinum' => 10000,
                'harga_gold' => 10000,
                'profit_member' => 0,
                'profit_platinum' => 0,
                'profit_gold' => 0,
                'catatan' => 'E2E product',
                'status' => 'available',
                'product_logo' => 'assets/logo/favicon.webp',
                'is_flash_sale' => false,
                'harga_flash_sale' => 0,
                'stock_flash_sale' => 0,
            ],
        );

        $package = Paket::query()->firstOrCreate(['nama' => 'E2E Package']);
        $package->layanan()->syncWithoutDetaching([
            $packagedProduct->id => [
                'product_logo' => 'assets/logo/favicon.webp',
            ],
        ]);

        // Second package group: regression fixture for cross-group nominal selection
        // (picking a nominal outside the first group must not revert to the first group).
        $instantProduct = Layanan::query()->updateOrCreate(
            ['kategori_id' => $category->id, 'provider_id' => 'e2e-product-3'],
            [
                'layanan' => 'E2E Instant 30000',
                'provider' => 'manual',
                'harga' => 30000,
                'harga_member' => 30000,
                'harga_platinum' => 30000,
                'harga_gold' => 30000,
                'profit_member' => 0,
                'profit_platinum' => 0,
                'profit_gold' => 0,
                'catatan' => 'E2E second package product',
                'status' => 'available',
                'product_logo' => 'assets/logo/favicon.webp',
                'is_flash_sale' => false,
                'harga_flash_sale' => 0,
                'stock_flash_sale' => 0,
            ],
        );

        $instantPackage = Paket::query()->firstOrCreate(['nama' => 'E2E Package Instant']);
        $instantPackage->layanan()->syncWithoutDetaching([
            $instantProduct->id => [
                'product_logo' => 'assets/logo/favicon.webp',
            ],
        ]);

        Layanan::query()->updateOrCreate(
            ['kategori_id' => $category->id, 'provider_id' => 'e2e-product-2'],
            [
                'layanan' => 'E2E Ungrouped 20000',
                'provider' => 'manual',
                'harga' => 20000,
                'harga_member' => 20000,
                'harga_platinum' => 20000,
                'harga_gold' => 20000,
                'profit_member' => 0,
                'profit_platinum' => 0,
                'profit_gold' => 0,
                'catatan' => 'E2E ungrouped product',
                'status' => 'available',
                'product_logo' => 'assets/logo/favicon.webp',
                'is_flash_sale' => false,
                'harga_flash_sale' => 0,
                'stock_flash_sale' => 0,
            ],
        );

        Method::query()->updateOrCreate(
            ['code' => 'E2E_QRIS'],
            [
                'name' => 'E2E QRIS',
                'images' => 'assets/payment/e2e-missing.webp',
                'keterangan' => 'E2E payment method with missing media fixture',
                'tipe' => 'qris',
                'payment' => 'Tripay',
                'fee_percent' => 0,
                'fix_fee' => 0,
                'min_pembelian' => 10000,
                'max_pembelian' => 1000000,
                'statuspayment' => true,
            ],
        );

        Voucher::query()->updateOrCreate(
            ['kode' => 'E2EPROMO10'],
            [
                'promo' => 10,
                'stock' => 10,
                'mintrx' => 0,
                'max_potongan' => 5000,
                'expired_at' => null,
            ],
        );
    }
}

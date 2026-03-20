<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PublicInvoiceResetRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_invoice_route_still_uses_base_order_id_for_reset_transactions(): void
    {
        $this->seedInvoiceDependencies();
        $this->registerSqliteInvoiceCollation();

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

        $layanan = Layanan::create([
            'kategori_id' => (string) $kategori->id,
            'layanan' => 'Weekly Pass',
            'provider_id' => 'ML-WP',
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'available',
            'provider' => 'bangjeff',
            'catatan' => 'Reset target provider',
            'is_flash_sale' => 0,
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-RESET-INVOICE-001',
            'username' => 'reset-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Reset User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'invoice_version' => 1,
            'display_order_id' => 'INV-RESET-INVOICE-001_001',
            'active_attempt_reference' => 'INV-RESET-INVOICE-001_001',
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'bangjeff',
            'active_provider_sku' => 'ML-WP',
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
            'reference' => 'REF-RESET-INVOICE-001',
        ]);

        $response = $this->get('/id/invoices/' . $pembelian->order_id);

        $response->assertOk();
        $response->assertSee($pembelian->order_id);
        $response->assertSee('Weekly Pass');
    }

    protected function seedInvoiceDependencies(): void
    {
        Route::getRoutes()->refreshNameLookups();

        View::share('config', (object) [
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
            'google_tag_manager_id' => null,
            'google_analytics_id' => null,
            'facebook_pixel_id' => null,
            'wa_key' => '',
            'nomor_admin' => '',
            'wa_number' => '',
            'ovo_admin' => '',
            'ovo1_admin' => '',
            'gopay_admin' => '',
            'gopay1_admin' => '',
            'dana_admin' => '',
            'shopeepay_admin' => '',
            'bca_admin' => '',
        ]);

        Method::create([
            'name' => 'QRIS',
            'images' => 'qris.png',
            'code' => 'QRIS',
            'keterangan' => 'QRIS',
            'tipe' => 'e-wallet',
            'payment' => 'tripay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);
    }

    protected function registerSqliteInvoiceCollation(): void
    {
        $pdo = DB::connection()->getPdo();

        if (! method_exists($pdo, 'sqliteCreateCollation')) {
            return;
        }

        $pdo->sqliteCreateCollation('utf8mb4_unicode_ci', static function (?string $left, ?string $right): int {
            return strcmp(mb_strtolower((string) $left), mb_strtolower((string) $right));
        });
    }
}

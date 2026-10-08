<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Halaman order tema `default` (Blade) dipakai produksi. Peringatan region
 * harus tersedia di HTML sebagai wadah terpisah di bawah form User ID, dan
 * tombol "Pesan Sekarang" harus punya jalur dimatikan oleh skrip yang sama.
 *
 * Logika tampil/sembunyikan diuji di `tests/frontend/orderRegionWarning.test.js`
 * dengan menjalankan `newkbrorder.js` yang asli; test ini mengunci kontrak
 * markup-nya supaya JS tidak lagi menemukan elemen yang dibutuhkannya.
 */
class OrderRegionWarningMarkupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'ipinfo.io/*' => Http::response([
                'ip' => '127.0.0.1',
                'city' => 'Test City',
                'region' => 'Test Region',
                'country' => 'ID',
                'org' => 'Test ISP',
            ], 200),
        ]);

        DB::table('setting_webs')->insert([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'logo.png',
            'url_wa' => 'wa',
            'url_ig' => 'ig',
            'url_tiktok' => 'tt',
            'url_youtube' => 'yt',
            'url_fb' => 'fb',
            'topupindo_api' => 'api',
            'warna1' => '#000000',
            'warna2' => '#000000',
            'warna3' => '#000000',
            'warna4' => '#000000',
            'paydisini_apikey' => 'key',
            'order_prefik' => 'TRX',
            'tripay_api' => 'test_api_key',
            'tripay_merchant_code' => 'test_merchant',
            'tripay_private_key' => 'test_private',
            'public_theme' => 'default',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create([
            'role' => 'Member',
            'balance' => 500000,
            'email' => 'member@example.test',
            'no_wa' => '08123456789',
        ]);

        $kategori = Kategori::factory()->create([
            'tipe' => 'game',
            'server_id' => 1,
            'require_user_id' => 1,
        ]);

        Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'provider' => 'manual',
            'provider_id' => 'manual-sku',
            'harga' => 100000,
            'harga_member' => 100000,
            'harga_platinum' => 100000,
            'harga_gold' => 100000,
            'profit_member' => 5,
            'profit_platinum' => 5,
            'profit_gold' => 5,
            'catatan' => 'Test',
            'status' => 'available',
        ]);

        Method::create([
            'name' => 'Saldo',
            'code' => 'SALDO',
            'tipe' => 'saldo',
            'payment' => 'saldo',
            'images' => 'saldo.png',
            'keterangan' => 'Bayar pakai saldo',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);

        $this->actingAs($user);
    }

    #[Test]
    public function halaman_order_legacy_memuat_wadah_peringatan_region_di_bawah_form_user_id(): void
    {
        $kategoriKode = Kategori::query()->value('kode');

        $response = $this->get('/id/' . $kategoriKode);

        $response->assertOk();

        $html = $response->getContent();

        // Wadah peringatan harus ada, tersembunyi, dan berada tepat setelah
        // blok nickname display di dalam form akun.
        $this->assertStringContainsString('id="account-region-warning"', $html);

        $warningCount = substr_count($html, 'id="account-region-warning"');
        $this->assertGreaterThanOrEqual(
            1,
            $warningCount,
            'Wadah peringatan region tidak dirender di halaman order.'
        );

        // Setiap wadah harus mulai dalam keadaan tersembunyi (atribut `hidden`).
        preg_match_all('/<div id="account-region-warning"[^>]*>/', $html, $matches);
        $this->assertNotEmpty($matches[0], 'Wadah peringatan harus berupa div dengan id yang dipakai skrip.');
        foreach ($matches[0] as $tag) {
            $this->assertStringContainsString(
                'hidden',
                $tag,
                'Wadah peringatan harus tersembunyi sampai provider melaporkan region non-ID.'
            );
        }

        // Tombol order harus ada supaya skrip bisa mematikannya.
        $this->assertStringContainsString('id="order-check"', $html);
    }

    #[Test]
    public function skrip_halaman_order_memuat_kunci_peringatan_dan_pemulihan_tombol(): void
    {
        $script = file_get_contents(public_path('assets/js/newkbrorder.js'));

        $this->assertStringContainsString("account-region-warning", $script);
        $this->assertStringContainsString(
            "Your account from region ",
            $script,
            'Copy peringatan region harus ada di skrip halaman order.'
        );
        $this->assertStringContainsString(
            "Only region ID allowed.",
            $script,
            'Copy peringatan region harus memuat batasan region ID.'
        );
        $this->assertStringContainsString(
            "setOrderButtonDisabled(true)",
            $script,
            'Skrip harus punya jalur mematikan tombol saat akun non-ID.'
        );
    }
}

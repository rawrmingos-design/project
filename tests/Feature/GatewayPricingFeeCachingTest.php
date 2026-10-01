<?php

namespace Tests\Feature;

use App\Http\Controllers\TriPayController;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Services\Gateway\GatewayPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Layar harga dan layar pembayaran memanggil API Tripay untuk menghitung fee
 * customer. Satu panggilan terukur ~1 detik di staging, jadi SETIAP panggilan
 * yang tidak perlu adalah waktu tunggu pembeli — dan saat Tripay lambat,
 * panggilan itu menyandera worker PHP-FPM sampai seluruh toko (termasuk bot)
 * ikut menunggu.
 *
 * Insiden nyata yang mengunci berkas ini:
 *  - Telegram mencatat `last_error_message: "Read timeout expired"` pada webhook
 *    bot; log staging penuh `TriPay fee calculator failed` (~15x/hari).
 *  - Akarnya BUKAN bot. `BotCommandHandler::paymentMethodsWithFees()` mengquote
 *    tiap metode lewat `GatewayPricingService::quote()`, dan quote() memanggil
 *    `gatewayCustomerFee()` yang TIDAK di-cache sama sekali — padahal
 *    kembarannya di `DepositPricingService::gatewayCustomerFee()` sudah
 *    di-cache 300 detik.
 *
 * Kunci test-nya bukan hasil angka, tapi JUMLAH panggilan API. Hasil benar
 * dengan 5 panggilan tetap bug: itu ongkos ~5 detik per layar.
 */
class GatewayPricingFeeCachingTest extends TestCase
{
    use RefreshDatabase;

    private const HARGA_LAYANAN = 10000;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('setting_webs')->insert([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test,web',
            'url_wa' => 'https://wa.me/123',
            'url_ig' => 'https://ig.com/test',
            'url_tiktok' => 'https://tiktok.com/test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://fb.com/test',
            'topupindo_api' => 'fake_api',
            'warna1' => '#fff',
            'warna2' => '#fff',
            'warna3' => '#fff',
            'warna4' => '#fff',
            'paydisini_apikey' => 'fake',
            'order_prefik' => 'TRX',
            'deposit_jalur' => 'tripay',
            'tripay_api' => 'fake_api_key',
            'tripay_merchant_code' => 'T1234',
            'tripay_private_key' => 'fake_private',
        ]);

        Method::create([
            'name' => 'QRIS',
            'code' => 'QRIS',
            'tipe' => 'qris',
            'images' => 'qris.png',
            'keterangan' => 'QRIS',
            'payment' => 'tripay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'min_pembelian' => 100,
            'max_pembelian' => 15000000,
            'statuspayment' => 1,
        ]);

        $kategori = Kategori::create([
            'nama' => 'Top Up Games',
            'sub_nama' => 'top-up-games',
        ]);

        Layanan::create([
            'kategori_id' => (string) $kategori->id,
            'layanan' => '25 Diamond',
            'provider_id' => 'FF25',
            'provider' => 'manual',
            'harga' => self::HARGA_LAYANAN,
            'harga_member' => self::HARGA_LAYANAN,
            'harga_gold' => self::HARGA_LAYANAN,
            'harga_platinum' => self::HARGA_LAYANAN,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
            'status' => 'available',
        ]);

        Cache::flush();
    }

    /**
     * Fake Tripay yang MENGHITUNG berapa kali API benar-benar ditembak. Rumus
     * fee-nya menyalin fee Tripay nyata (flat 750 + 0,7%), jadi assertion hasil
     * tidak bisa lolos dengan angka karangan.
     */
    private function fakeTripayCounting(): object
    {
        $fake = new class extends TriPayController
        {
            public int $calls = 0;

            private function feeFor(int $amount): int
            {
                return 750 + (int) ceil($amount * 0.007);
            }

            public function feeBreakdown($jumlah, $code): array
            {
                $this->calls++;
                $customer = $this->feeFor((int) $jumlah);

                return ['customer' => $customer, 'merchant' => 0, 'total' => $customer];
            }

            public function customerFee($jumlah, $code): int
            {
                // `quote()` menembak lewat customerFee(), bukan feeBreakdown().
                // Penghitungnya harus di sini juga; kalau tidak, test melihat 0
                // panggilan padahal API benar-benar dipanggil.
                $this->calls++;

                return $this->feeFor((int) $jumlah);
            }
        };

        $this->app->instance(TriPayController::class, $fake);

        return $fake;
    }

    private function quoteQris(): array
    {
        return app(GatewayPricingService::class)->quote([
            'service_id' => Layanan::query()->value('id'),
            'payment_method' => 'QRIS',
        ]);
    }

    /**
     * Inti regresi: layar harga dan layar pembayaran sama-sama mengquote metode
     * yang sama. Quote kedua dan seterusnya harus dilayani cache, bukan API.
     */
    public function test_quote_kedua_tidak_menembak_api_tripay_lagi(): void
    {
        $fake = $this->fakeTripayCounting();

        $this->quoteQris();
        $setelahPertama = $fake->calls;

        $this->assertGreaterThan(0, $setelahPertama, 'Quote pertama wajar menembak API Tripay.');

        $this->quoteQris();

        $this->assertSame(
            $setelahPertama,
            $fake->calls,
            'Quote kedua untuk metode & nominal yang sama tidak boleh menembak API Tripay lagi — '
            .'fee-nya harus dilayani cache, bukan dihitung ulang ~1 detik.'
        );
    }

    public function test_quote_berulang_tetap_dilayani_cache(): void
    {
        $fake = $this->fakeTripayCounting();

        $this->quoteQris();
        $setelahPertama = $fake->calls;

        for ($i = 0; $i < 4; $i++) {
            $this->quoteQris();
        }

        $this->assertSame(
            $setelahPertama,
            $fake->calls,
            'Empat quote tambahan harus dilayani cache, bukan API.'
        );
    }

    /**
     * Hasil yang dilihat pelanggan tidak boleh berubah karena caching.
     */
    public function test_total_yang_dibayar_pelanggan_konsisten(): void
    {
        $this->fakeTripayCounting();

        $pertama = $this->quoteQris();
        $kedua = $this->quoteQris();

        $this->assertSame(
            $pertama['data']['total_amount'],
            $kedua['data']['total_amount'],
            'Total yang ditampilkan tidak boleh berubah antar quote.'
        );

        $this->assertGreaterThan(
            self::HARGA_LAYANAN,
            $pertama['data']['total_amount'],
            'Total harus memuat fee customer Tripay di atas harga layanan.'
        );
    }
}

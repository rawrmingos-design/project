<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Services\OrderProcessingService;
use App\Services\ProviderOrderStatusSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Order yang SUDAH DI-RESET harus dicek statusnya pada attempt TERBARU
 * (`active_attempt_reference`, mis. `_002`), bukan pada `provider_order_id`
 * yang beku di attempt pertama.
 *
 * Bug nyata (produksi, order ITO261012065200LXGZ4N):
 *  - attempt pertama dikirim sebagai `...LXGZ4N` -> Gagal "Produk Sedang Gangguan"
 *  - admin reset 2x; attempt `...LXGZ4N_002` dikirim dan SUCCESS di provider
 *    (SN terbit 13:58:56) -- tercatat di log produksi
 *  - admin klik "Cek Status": retry menanyakan `...LXGZ4N` (yang lama), provider
 *    menjawab "Gagal" lagi, order TETAP Gagal
 *
 * Dua jalur rusak karena satu akar yang sama -- keduanya memakai
 * `provider_order_id` alih-alih attempt yang hidup:
 *  1. tombol "Cek Status" admin   -> OrderProcessingService::resolveProviderReference
 *  2. polling otomatis            -> ProviderOrderStatusSyncService::sync
 *
 * Aturan yang dikunci: kalau order punya attempt aktif yang berbeda dari
 * `provider_order_id`, attempt aktif ITU yang ditanyakan. Harus tetap benar
 * saat `active_attempt_token` menyimpan referensi lama/beku (bentuk data 8870).
 */
class DigiflazzResetAttemptStatusReferenceTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'ITO-TEST-RESET-REF';

    protected function setUp(): void
    {
        parent::setUp();

        // `setting_webs` punya banyak kolom NOT NULL -- pakai seeder resmi,
        // jangan rakit manual (rapuh tiap ada migrasi baru).
        $this->seed(\Database\Seeders\SettingWebsSeeder::class);

        SettingWeb::query()->first()->update([
            'username_digi' => 'test_digi',
            'api_key_digi' => 'test_digi_key',
        ]);
    }

    private function makeLayanan(): Layanan
    {
        return Layanan::factory()->create([
            'layanan' => 'Diamond 2166',
            'provider' => 'digiflazz',
            'provider_id' => 'DMMLBB2166',
            'status' => 'available',
        ]);
    }

    /**
     * Cerminan bentuk data order 8870 di produksi: `provider_order_id` DAN
     * `active_attempt_token` sama-sama beku di base; hanya
     * `active_attempt_reference` yang menunjuk attempt hidup.
     */
    private function makeResetOrder(Layanan $layanan): Pembelian
    {
        return Pembelian::create([
            'order_id' => self::BASE,
            'username' => 'reset-user',
            'user_id' => '184960072',
            'zone' => '2953',
            'nickname' => 'Reset User',
            'layanan' => $layanan->layanan,
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'digiflazz',
            'active_provider_sku' => 'DMMLBB2166',
            'provider_order_id' => self::BASE,
            'active_attempt_token' => self::BASE,
            'active_attempt_reference' => self::BASE . '_002',
            'display_order_id' => self::BASE . '_002',
            'invoice_version' => 2,
            'reset_status' => 'failed',
            'reset_count' => 2,
            'status' => 'Gagal',
            'keterangan_sn' => 'Produk Sedang Gangguan',
            'harga' => 559507,
            'profit' => 41445,
            'tipe_transaksi' => 'game',
        ]);
    }

    /** Http::fake yang mencatat SETIAP ref_id yang benar-benar dikirim. */
    private function fakeDigiflazz(array &$seen, string $status = 'Sukses'): void
    {
        Http::fake(function ($request) use (&$seen, $status) {
            $payload = json_decode($request->body(), true) ?: [];
            $seen[] = (string) ($payload['ref_id'] ?? '');

            $isSuccess = $status === 'Sukses';

            return Http::response([
                'data' => [
                    'ref_id' => $payload['ref_id'] ?? null,
                    'status' => $status,
                    'rc' => $isSuccess ? '00' : '03',
                    'message' => $isSuccess ? 'Transaksi Sukses' : 'Transaksi Pending',
                    'sn' => $isSuccess ? 'SN-TEST-002' : '',
                ],
            ]);
        });
    }

    /** 1. JALUR RETRY -- tombol "Cek Status" di admin. */
    public function test_retry_status_menanyakan_attempt_terbaru_bukan_provider_order_id_yang_beku(): void
    {
        $layanan = $this->makeLayanan();
        $pembelian = $this->makeResetOrder($layanan);

        $seen = [];
        $this->fakeDigiflazz($seen);

        $result = app(OrderProcessingService::class)->process($pembelian, 'retry_status');

        $this->assertNotEmpty($seen, 'Retry harus benar-benar menembak provider.');
        $this->assertSame(
            [self::BASE . '_002'],
            $seen,
            'Retry harus menanyakan attempt terbaru (_002). Menanyakan provider_order_id yang beku selalu menjawab Gagal.',
        );
        $this->assertSame('Sukses', $result['order_status']);
    }

    /** 2. JALUR POLLING OTOMATIS. */
    public function test_polling_otomatis_juga_memakai_attempt_terbaru(): void
    {
        $layanan = $this->makeLayanan();
        $pembelian = $this->makeResetOrder($layanan);

        DB::table('pembayarans')->insert([
            'order_id' => $pembelian->order_id,
            'harga' => 559507,
            'no_pembayaran' => 'PAY-RESET-REF',
            'no_pembeli' => '081234073677',
            'status' => 'Lunas',
            'metode' => 'NQ',
            'reference' => 'REF-RESET',
            'expired_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Poll hanya menyapu status non-final. Order ini berstatus Gagal justru
        // karena bug-nya, jadi kita mulai dari Processing supaya poll memang
        // terlihat mengambil attempt yang benar.
        DB::table('pembelians')->where('id', $pembelian->id)->update(['status' => 'Processing']);

        $seen = [];
        $this->fakeDigiflazz($seen);

        app(ProviderOrderStatusSyncService::class)->sync('digiflazz');

        $this->assertNotEmpty($seen, 'Polling harus menembak provider.');
        $this->assertSame(
            [self::BASE . '_002'],
            $seen,
            'Polling harus memakai attempt terbaru, bukan provider_order_id yang beku.',
        );
        $this->assertSame('Sukses', Pembelian::query()->find($pembelian->id)->status);
    }

    /** Guard: order TANPA reset tetap memakai provider_order_id seperti semula. */
    public function test_order_tanpa_reset_tetap_memakai_provider_order_id(): void
    {
        $layanan = $this->makeLayanan();

        $pembelian = Pembelian::create([
            'order_id' => 'ITO-TEST-NO-RESET',
            'username' => 'plain-user',
            'user_id' => '999001',
            'zone' => '1001',
            'nickname' => 'Plain User',
            'layanan' => $layanan->layanan,
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'digiflazz',
            'active_provider_sku' => 'DMMLBB2166',
            'provider_order_id' => 'ITO-TEST-NO-RESET',
            'active_attempt_token' => 'ITO-TEST-NO-RESET',
            'active_attempt_reference' => 'ITO-TEST-NO-RESET',
            'display_order_id' => 'ITO-TEST-NO-RESET',
            'invoice_version' => 0,
            'status' => 'Processing',
            'harga' => 10000,
            'profit' => 500,
            'tipe_transaksi' => 'game',
        ]);

        $seen = [];
        $this->fakeDigiflazz($seen, 'Pending');

        app(OrderProcessingService::class)->process($pembelian, 'retry_status');

        $this->assertSame(['ITO-TEST-NO-RESET'], $seen);
    }

    /**
     * Kelas bug yang SAMA, di kolom lain: saat hasil status diterapkan, service
     * menulis `active_attempt_token` dari `transaction_id` yang dikirim PEMANGGIL.
     * Untuk order yang sudah direset, polling mengirim `provider_order_id` (beku)
     * -- jadi hasil yang benar bisa tetap menulis ulang referensi lama.
     *
     * Efeknya di jalur VIP: `resolveVipStatusReference()` membaca token itu, jadi
     * retry VIP bisa kembali menanyakan attempt yang salah.
     */
    public function test_status_sukses_tidak_menulis_ulang_referensi_beku_ke_token(): void
    {
        $layanan = $this->makeLayanan();
        $pembelian = $this->makeResetOrder($layanan);

        DB::table('pembayarans')->insert([
            'order_id' => $pembelian->order_id,
            'harga' => 559507,
            'no_pembayaran' => 'PAY-RESET-TOKEN',
            'no_pembeli' => '081234073677',
            'status' => 'Lunas',
            'metode' => 'NQ',
            'reference' => 'REF-RESET-TOKEN',
            'expired_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pembelians')->where('id', $pembelian->id)->update([
            'status' => 'Processing',
            'active_attempt_token' => null,
        ]);

        $seen = [];
        $this->fakeDigiflazz($seen);

        app(ProviderOrderStatusSyncService::class)->sync('digiflazz');

        $fresh = Pembelian::query()->find($pembelian->id);

        $this->assertSame('Sukses', $fresh->status);
        $this->assertSame(
            self::BASE . '_002',
            trim((string) $fresh->active_attempt_token),
            'Token tidak boleh diisi ulang dengan referensi beku -- jalur VIP membaca kolom ini.',
        );
        $this->assertSame(
            self::BASE . '_002',
            trim((string) $fresh->provider_order_id),
            'Setelah attempt baru Sukses, provider_order_id harus menunjuk attempt yang benar-benar dieksekusi.',
        );
    }
}

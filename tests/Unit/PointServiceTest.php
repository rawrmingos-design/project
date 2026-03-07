<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PointService;
use App\Models\User;
use App\Models\Pembelian;
use App\Models\PointHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class PointServiceTest extends TestCase
{
    use RefreshDatabase;

    private PointService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PointService::class);

        // Setup setting_webs
        DB::table('setting_webs')->insert([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test',
            'keywords'      => 'test,web',
            'url_wa'        => 'wa',
            'url_ig'        => 'ig',
            'url_tiktok'    => 'tiktok',
            'url_youtube'   => 'yt',
            'url_fb'        => 'fb',
            'topupindo_api' => 'api1',
            'warna1'        => '#000',
            'warna2'        => '#000',
            'warna3'        => '#000',
            'warna4'        => '#000',
            'paydisini_apikey' => 'key',
            'order_prefik'  => 'TRX',
            'point_per_nominal' => 1,    // 1 poin per Rp 1.000
            'point_value' => 100,        // 1 poin = Rp 100
            'max_point_usage_percent' => 50, // maks 50% dari harga
        ]);

        $this->user = User::factory()->create([
            'point_balance' => 0,
        ]);
    }

    // =====================================================
    // earnPoints() tests
    // =====================================================

    /** @test */
    public function earn_points_berhasil_saat_transaksi_sukses()
    {
        $order = Pembelian::factory()->create([
            'username' => $this->user->username,
            'harga'    => 50000,
            'status'   => 'Proses',
            'layanan'  => 'Mobile Legends 86 Diamond',
        ]);

        $this->service->earnPoints($this->user, $order);

        // 50000 / 1000 * 1 = 50 poin
        $this->user->refresh();
        $this->assertEquals(50, $this->user->point_balance);
    }

    /** @test */
    public function earn_points_catat_history_earn()
    {
        $order = Pembelian::factory()->create([
            'username' => $this->user->username,
            'harga'    => 20000,
            'status'   => 'Proses',
        ]);

        $this->service->earnPoints($this->user, $order);

        $this->assertDatabaseHas('point_histories', [
            'user_id'  => $this->user->id,
            'order_id' => $order->order_id,
            'type'     => 'earn',
            'points'   => 20, // 20000 / 1000 * 1
        ]);
    }

    /** @test */
    public function earn_points_nol_jika_harga_kurang_dari_seribu()
    {
        $order = Pembelian::factory()->create([
            'username' => $this->user->username,
            'harga'    => 500,
            'status'   => 'Success',
        ]);

        $this->service->earnPoints($this->user, $order);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->point_balance);
        $this->assertDatabaseMissing('point_histories', ['user_id' => $this->user->id]);
    }

    /** @test */
    public function earn_points_hitung_dengan_rasio_kustom()
    {
        // Ubah setting: 2 poin per Rp 1.000
        DB::table('setting_webs')->where('id', 1)->update(['point_per_nominal' => 2]);

        $order = Pembelian::factory()->create([
            'username' => $this->user->username,
            'harga'    => 10000,
        ]);

        $this->service->earnPoints($this->user, $order);

        $this->user->refresh();
        $this->assertEquals(20, $this->user->point_balance); // 10000 / 1000 * 2
    }

    // =====================================================
    // redeemPoints() tests
    // =====================================================

    /** @test */
    public function redeem_points_berhasil_kurangi_saldo_dan_catat_history()
    {
        $this->user->update(['point_balance' => 100]);

        $result = $this->service->redeemPoints($this->user, 50, 'TRX-001', 'ML Diamond');

        $this->assertEquals(5000, $result); // 50 poin * Rp 100

        $this->user->refresh();
        $this->assertEquals(50, $this->user->point_balance); // 100 - 50

        $this->assertDatabaseHas('point_histories', [
            'user_id'  => $this->user->id,
            'order_id' => 'TRX-001',
            'type'     => 'redeem',
            'points'   => 50,
        ]);
    }

    /** @test */
    public function redeem_points_gagal_jika_saldo_tidak_cukup()
    {
        $this->user->update(['point_balance' => 10]);

        $result = $this->service->redeemPoints($this->user, 50, 'TRX-002', 'ML Diamond');

        $this->assertEquals(0, $result); // Gagal

        $this->user->refresh();
        $this->assertEquals(10, $this->user->point_balance); // Tidak berubah
    }

    /** @test */
    public function redeem_points_gagal_jika_jumlah_nol_atau_negatif()
    {
        $this->user->update(['point_balance' => 100]);

        $this->assertEquals(0, $this->service->redeemPoints($this->user, 0, 'TRX-003', 'Test'));
        $this->assertEquals(0, $this->service->redeemPoints($this->user, -10, 'TRX-004', 'Test'));

        $this->user->refresh();
        $this->assertEquals(100, $this->user->point_balance);
    }

    // =====================================================
    // calculateMaxRedeemable() tests
    // =====================================================

    /** @test */
    public function calculate_max_redeemable_dibatasi_oleh_persen_harga()
    {
        $this->user->update(['point_balance' => 1000]); // Rp 100.000 worth

        // Harga Rp 50.000, max 50%, batas dari harga = Rp 25.000 = 250 poin
        $result = $this->service->calculateMaxRedeemable(50000, 1000);

        $this->assertEquals(250, $result['max_points']);
        $this->assertEquals(25000, $result['max_discount']);
        $this->assertEquals(100, $result['point_value']);
    }

    /** @test */
    public function calculate_max_redeemable_dibatasi_oleh_saldo_poin_jika_lebih_kecil()
    {
        // User hanya punya 100 poin = Rp 10.000
        // Tapi max 50% dari Rp 50.000 = Rp 25.000
        // Karena saldo (Rp 10.000) < batas (Rp 25.000), pakai saldo
        $result = $this->service->calculateMaxRedeemable(50000, 100);

        $this->assertEquals(100, $result['max_points']);
        $this->assertEquals(10000, $result['max_discount']);
    }

    /** @test */
    public function calculate_max_redeemable_nol_jika_saldo_kosong()
    {
        $result = $this->service->calculateMaxRedeemable(50000, 0);

        $this->assertEquals(0, $result['max_points']);
        $this->assertEquals(0, $result['max_discount']);
    }
}

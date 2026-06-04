<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pembelian;
use App\Models\PointHistory;
use App\Events\TransactionSuccess;
use App\Listeners\AwardPointsListener;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class PointSystemFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup setting_webs dengan config poin
        DB::table('setting_webs')->insert([
            'id'                      => 1,
            'judul_web'               => 'Test Web',
            'deskripsi_web'           => 'Test',
            'keywords'                => 'test,web',
            'url_wa'                  => 'wa',
            'url_ig'                  => 'ig',
            'url_tiktok'              => 'tiktok',
            'url_youtube'             => 'yt',
            'url_fb'                  => 'fb',
            'topupindo_api'           => 'api1',
            'warna1'                  => '#000',
            'warna2'                  => '#000',
            'warna3'                  => '#000',
            'warna4'                  => '#000',
            'paydisini_apikey'        => 'key',
            'order_prefik'            => 'TRX',
            'point_per_nominal'       => 1,
            'point_value'             => 100,
            'max_point_usage_percent' => 50,
        ]);
    }

    protected function tearDown(): void
    {
        DB::table('setting_webs')->delete();
        parent::tearDown();
    }

    // =====================================================
    // EVENT & LISTENER TESTS
    // =====================================================

    /** @test */
    public function transaction_success_event_di_dispatch_oleh_pembelian_observer_saat_status_sukses()
    {
        Event::fake([TransactionSuccess::class]);

        $user = User::factory()->create(['point_balance' => 0]);
        $order = Pembelian::factory()->create([
            'username' => $user->username,
            'status'   => 'Proses',
            'harga'    => 10000,
        ]);

        // Update ke Success — harusnya trigger observer → dispatch event
        $order->update(['status' => 'Success']);

        Event::assertDispatched(TransactionSuccess::class, function ($event) use ($order) {
            return $event->pembelian->order_id === $order->order_id;
        });
    }

    /** @test */
    public function transaction_success_event_tidak_di_dispatch_saat_status_bukan_sukses()
    {
        Event::fake([TransactionSuccess::class]);

        $user = User::factory()->create();
        $order = Pembelian::factory()->create([
            'username' => $user->username,
            'status'   => 'Proses',
        ]);

        $order->update(['status' => 'Gagal']);

        Event::assertNotDispatched(TransactionSuccess::class);
    }

    /** @test */
    public function award_points_listener_tambah_poin_ke_user()
    {
        $user = User::factory()->create(['point_balance' => 0]);
        $order = Pembelian::factory()->create([
            'username' => $user->username,
            'harga'    => 50000,
            'status'   => 'Proses',
        ]);

        $event = new TransactionSuccess($order, $user);
        $listener = app(AwardPointsListener::class);
        $listener->handle($event);

        $user->refresh();
        $this->assertEquals(50, $user->point_balance); // 50000 / 1000 * 1 = 50
    }

    /** @test */
    public function award_points_listener_tidak_tambah_poin_jika_user_tidak_ada()
    {
        // Order tanpa username yang valid (guest order)
        $order = Pembelian::factory()->create([
            'username' => 'guest_12345_tidak_ada',
            'harga'    => 50000,
            'status'   => 'Proses',
        ]);

        // User null — harus tidak error dan tidak ada point history
        $event = new TransactionSuccess($order, null);
        $listener = app(AwardPointsListener::class);
        $listener->handle($event);

        $this->assertDatabaseCount('point_histories', 0);
    }

    /** @test */
    public function full_flow_poin_terakumulasi_dari_beberapa_transaksi()
    {
        $user = User::factory()->create(['point_balance' => 0]);

        $amounts = [10000, 20000, 5000]; // Total = 35 poin
        foreach ($amounts as $i => $amount) {
            $order = Pembelian::factory()->create([
                'username' => $user->username,
                'harga'    => $amount,
                'status'   => 'Proses',
            ]);
            $order->update(['status' => 'Success']);
        }

        $user->refresh();
        // 10 + 20 + 5 = 35 poin
        $this->assertEquals(35, $user->point_balance);
        $this->assertDatabaseCount('point_histories', 3);
    }

    // =====================================================
    // POINT BALANCE & HISTORY TESTS
    // =====================================================

    /** @test */
    public function user_point_histories_relasi_bekerja_dengan_benar()
    {
        $user = User::factory()->create(['point_balance' => 200]);

        PointHistory::create([
            'user_id'     => $user->id,
            'order_id'    => 'TRX-A01',
            'type'        => 'earn',
            'points'      => 100,
            'description' => 'Poin dari pembelian',
        ]);

        PointHistory::create([
            'user_id'     => $user->id,
            'order_id'    => 'TRX-A02',
            'type'        => 'redeem',
            'points'      => 50,
            'description' => 'Redeem poin',
        ]);

        $this->assertCount(2, $user->pointHistories);
    }

    /** @test */
    public function point_history_formatted_points_accessor()
    {
        $earn = new PointHistory(['type' => 'earn', 'points' => 100]);
        $redeem = new PointHistory(['type' => 'redeem', 'points' => 50]);

        $this->assertStringStartsWith('+', $earn->formatted_points);
        $this->assertStringStartsWith('-', $redeem->formatted_points);
        $this->assertStringContainsString('100', $earn->formatted_points);
        $this->assertStringContainsString('50', $redeem->formatted_points);
    }
}

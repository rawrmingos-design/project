<?php

namespace Tests\Feature;

use App\Http\Controllers\DigiflazzCallbackController;
use App\Models\AffiliateHistory;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\PointHistory;
use App\Models\SettingWeb;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\PointService;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Tests\TestCase;

class ResetSideEffectIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_success_callbacks_do_not_duplicate_points_affiliate_tier_or_notifications(): void
    {
        config(['providers.digiflazz.webhook_secret' => 'test-secret']);
        $this->seedSettingWeb();

        $uplink = User::factory()->create([
            'username' => 'uplink-user',
            'balance' => 0,
        ]);

        $user = User::factory()->create([
            'username' => 'callback-success-user',
            'uplink' => $uplink->username,
            'role' => 'Member',
            'point_balance' => 50,
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-IDEMPOTENT-SUCCESS-001',
            'username' => $user->username,
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Callback Success User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'used_points' => 5,
            'used_point_amount' => 500,
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-' . $pembelian->order_id,
        ]);

        app(PointService::class)->redeemPoints($user, 5, $pembelian->order_id, $pembelian->layanan);

        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendNotification')->once()->andReturn(['success' => true]);
        });

        $this->mock(EmailNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendTransactionEmail')->once()->andReturn(true);
        });

        $this->dispatchCallback($pembelian->order_id, 'Sukses', sn: 'SUCCESS-SN');
        $this->dispatchCallback($pembelian->order_id, 'Sukses', sn: 'SUCCESS-SN');

        $pembelian->refresh();
        $user->refresh();
        $uplink->refresh();

        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('Gold', $user->role);
        $this->assertSame(60, $user->point_balance);
        $this->assertSame(200, $uplink->balance);
        $this->assertSame(2, PointHistory::where('order_id', $pembelian->order_id)->count());
        $this->assertSame(1, PointHistory::where('order_id', $pembelian->order_id)->where('type', 'earn')->count());
        $this->assertSame(1, AffiliateHistory::where('order_id', $pembelian->order_id)->count());
    }

    public function test_duplicate_failed_callbacks_do_not_duplicate_refunds_or_notifications(): void
    {
        config(['providers.digiflazz.webhook_secret' => 'test-secret']);
        $this->seedSettingWeb();

        $user = User::factory()->create([
            'username' => 'callback-failed-user',
            'balance' => 1000,
            'point_balance' => 50,
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-IDEMPOTENT-FAILED-001',
            'username' => $user->username,
            'user_id' => '10002',
            'zone' => '2002',
            'nickname' => 'Callback Failed User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'used_points' => 10,
            'used_point_amount' => 1000,
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'SALDO',
            'reference' => 'REF-' . $pembelian->order_id,
        ]);

        app(PointService::class)->redeemPoints($user, 10, $pembelian->order_id, $pembelian->layanan);

        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendNotification')->once()->andReturn(['success' => true]);
        });

        $this->mock(EmailNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendTransactionEmail')->once()->andReturn(true);
        });

        $this->dispatchCallback($pembelian->order_id, 'Gagal', message: 'Provider failed');
        $this->dispatchCallback($pembelian->order_id, 'Gagal', message: 'Provider failed');

        $pembelian->refresh();
        $user->refresh();

        $this->assertSame('Gagal', $pembelian->status);
        $this->assertSame(50, $user->point_balance);
        $this->assertSame(16000, $user->balance);
        $this->assertSame(2, PointHistory::where('order_id', $pembelian->order_id)->count());
        $this->assertSame(1, PointHistory::where('order_id', $pembelian->order_id)->where('type', 'earn')->count());
    }

    private function dispatchCallback(string $refId, string $status, string $sn = '', string $message = '')
    {
        $payload = [
            'data' => [
                'ref_id' => $refId,
                'status' => $status,
                'sn' => $sn,
                'message' => $message,
            ],
        ];

        $content = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha1=' . hash_hmac('sha1', $content, (string) config('providers.digiflazz.webhook_secret'));

        $request = Request::create(
            '/wejizy/digi/payload',
            'POST',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE' => $signature,
                'HTTP_X_DIGIFLAZZ_EVENT' => 'testing',
            ],
            content: $content,
        );

        return app(DigiflazzCallbackController::class)->handle($request);
    }

    private function seedSettingWeb(): void
    {
        SettingWeb::create([
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
            'apigames_secret' => 'test_apigames_secret',
            'apigames_merchant' => 'test_apigames_merchant',
            'vip_apiid' => 'test_vip_id',
            'vip_apikey' => 'test_vip_key',
            'apikey_bangjeff' => 'test_bangjeff_key',
            'order_prefik' => 'INV',
            'commission_percent' => 20,
            'point_per_nominal' => 1,
            'point_value' => 100,
            'trx_count_gold' => 1,
            'trx_count_platinum' => 2,
        ]);
    }
}

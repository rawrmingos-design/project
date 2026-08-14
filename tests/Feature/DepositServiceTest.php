<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\User;
use App\Services\Deposit\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DepositServiceTest extends TestCase
{
    use RefreshDatabase;

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
            'name' => 'BCA',
            'code' => 'BCA',
            'tipe' => 'bank',
            'images' => 'bca.png',
            'keterangan' => 'Bank BCA',
            'payment' => 'Manual',
            'min_pembelian' => 10000,
            'max_pembelian' => 1000000,
            'statuspayment' => 1,
        ]);
    }

    private function fakeTripayResponse(): void
    {
        Http::fake([
            'tripay.co.id/*' => Http::response([
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'reference' => 'T123456789',
                    'amount' => 15000,
                    'pay_code' => '1234567890',
                    'pay_url' => 'https://tripay.co.id/checkout/T123456789',
                    'expired_time' => time() + 3600,
                ],
            ], 200),
        ]);
    }

    public function test_service_accepts_an_explicit_user_without_auth_state(): void
    {
        $this->fakeTripayResponse();
        $user = User::factory()->create(['role' => 'Member']);

        $result = app(DepositService::class)->create($user, [
            'jumlah' => 15000,
            'metode' => 'BCA',
            'source' => 'web',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame($user->username, $result['deposit']->username);
        $this->assertDatabaseHas('deposits', [
            'order_id' => $result['order_id'],
            'username' => $user->username,
            'source' => 'web',
        ]);
    }

    public function test_whatsapp_source_requires_both_external_identity_components(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $result = app(DepositService::class)->create($user, [
            'jumlah' => 15000,
            'metode' => 'BCA',
            'source' => 'whatsapp_gateway',
            'external_user_id' => '6281234567890',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('idempotency', $result['field']);
        $this->assertSame(0, Deposit::count());
    }

    public function test_same_whatsapp_message_replays_one_deposit_and_payment(): void
    {
        $this->fakeTripayResponse();
        $user = User::factory()->create(['role' => 'Member']);
        $input = [
            'jumlah' => 15000,
            'metode' => 'BCA',
            'source' => 'whatsapp_gateway',
            'external_user_id' => '6281234567890',
            'external_message_id' => 'fonnte-message-1',
        ];

        $first = app(DepositService::class)->create($user, $input);
        $second = app(DepositService::class)->create($user, $input);

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertTrue($second['idempotent_replay']);
        $this->assertSame($first['order_id'], $second['order_id']);
        $this->assertNotNull($second['expired_at']);
        $this->assertSame(1, Deposit::count());
        $this->assertSame(1, Pembayaran::count());
    }

    public function test_same_whatsapp_message_cannot_be_reused_for_different_payment_input(): void
    {
        $this->fakeTripayResponse();
        $user = User::factory()->create(['role' => 'Member']);
        $input = [
            'jumlah' => 15000,
            'metode' => 'BCA',
            'source' => 'whatsapp_gateway',
            'external_user_id' => '6281234567890',
            'external_message_id' => 'fonnte-message-2',
        ];

        $first = app(DepositService::class)->create($user, $input);
        $conflict = app(DepositService::class)->create($user, [
            ...$input,
            'jumlah' => 20000,
        ]);

        $this->assertTrue($first['success']);
        $this->assertFalse($conflict['success']);
        $this->assertSame('idempotency', $conflict['field']);
        $this->assertSame(1, Deposit::count());
    }
}

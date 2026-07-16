<?php

namespace Tests\Feature;

use App\Http\Controllers\TokoPayController;
use App\Http\Controllers\TriPayController;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderContactRequirementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Layanan $service;

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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::factory()->create([
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

        $this->service = Layanan::factory()->create([
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

        Method::create([
            'name' => 'QRIS',
            'code' => 'QRIS',
            'tipe' => 'e-wallet',
            'payment' => 'tripay',
            'images' => 'qris.png',
            'keterangan' => 'Tripay QRIS',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);

        Method::create([
            'name' => 'TokoPay VA',
            'code' => 'TOKOVA',
            'tipe' => 'virtual-account',
            'payment' => 'tokopay',
            'images' => 'tokopay.png',
            'keterangan' => 'TokoPay',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);
    }

    #[Test]
    public function ordered_endpoint_accepts_phone_only_via_whatsapp_field()
    {
        $response = $this->actingAs($this->user)->postJson(route('ordered'), $this->basePayload([
            'whatsapp' => '081234567890',
        ]));

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $order = Pembelian::query()->latest()->first();
        $this->assertNotNull($order);

        $payment = Pembayaran::query()->where('order_id', $order->order_id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('081234567890', $payment->no_pembeli);
    }

    #[Test]
    public function ordered_endpoint_accepts_email_only_and_stores_safe_phone_fallback()
    {
        $response = $this->actingAs($this->user)->postJson(route('ordered'), $this->basePayload([
            'email' => 'buyer@example.test',
        ]));

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $order = Pembelian::query()->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame('member@example.test', $order->email_pembeli);

        $payment = Pembayaran::query()->where('order_id', $order->order_id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('-', $payment->no_pembeli);
    }

    #[Test]
    public function ordered_endpoint_accepts_both_phone_and_email()
    {
        $response = $this->actingAs($this->user)->postJson(route('ordered'), $this->basePayload([
            'nomor' => '081234567890',
            'email' => 'buyer@example.test',
        ]));

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $order = Pembelian::query()->latest()->first();
        $payment = Pembayaran::query()->where('order_id', $order->order_id)->first();

        $this->assertSame('member@example.test', $order->email_pembeli);
        $this->assertSame('081234567890', $payment->no_pembeli);
    }

    #[Test]
    public function ordered_endpoint_rejects_when_phone_and_email_are_both_missing()
    {
        $response = $this->actingAs($this->user)->postJson(route('ordered'), $this->basePayload());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nomor', 'email']);
    }

    #[Test]
    public function ordered_endpoint_accepts_email_only_for_tripay_gateway()
    {
        $this->app->instance(TriPayController::class, new class extends TriPayController {
            public function request($idOrder, $jumlah, $method, $dataUser, $nohp)
            {
                return [
                    'success' => true,
                    'amount' => $jumlah,
                    'no_pembayaran' => 'PAY-' . $idOrder,
                    'reference' => 'REF-' . $idOrder,
                    'expired_at' => now()->addDay()->toIso8601String(),
                ];
            }
        });

        $response = $this->actingAs($this->user)->postJson(route('ordered'), $this->basePayload([
            'payment_method' => 'QRIS',
            'email' => 'buyer@example.test',
        ]));

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $order = Pembelian::query()->latest()->first();
        $this->assertNotNull($order);

        $payment = Pembayaran::query()->where('order_id', $order->order_id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('-', $payment->no_pembeli);
    }

    #[Test]
    public function ordered_endpoint_accepts_email_only_for_tokopay_gateway()
    {
        $this->app->instance(TokoPayController::class, new class extends TokoPayController {
            public function createAdvanceOrder($ref_id, $channel, $jumlah, $nickname, $phone_number, $service)
            {
                return [
                    'status' => 'Success',
                    'data' => [
                        'pay_code' => 'PAY-' . $ref_id,
                        'trx_id' => 'TP-' . $ref_id,
                        'total_bayar' => $jumlah,
                        'expired_at' => now()->addHours(3)->toIso8601String(),
                    ],
                ];
            }
        });

        $response = $this->actingAs($this->user)->postJson(route('ordered'), $this->basePayload([
            'payment_method' => 'TOKOVA',
            'email' => 'buyer@example.test',
        ]));

        $response->assertStatus(200)
            ->assertJson(['status' => true]);

        $order = Pembelian::query()->latest()->first();
        $this->assertNotNull($order);

        $payment = Pembayaran::query()->where('order_id', $order->order_id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('-', $payment->no_pembeli);
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'uid' => '12345',
            'zone' => '1234',
            'nickname' => 'TestNick',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'ktg_tipe' => 'game',
        ], $overrides);
    }
}

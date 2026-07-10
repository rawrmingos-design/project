<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV2CheckoutOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_v2_joki_checkout_does_not_require_uid_and_persists_joki_details(): void
    {
        $category = Kategori::factory()->create([
            'tipe' => 'joki',
            'require_user_id' => false,
        ]);

        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Joki Rank Epic',
            'provider' => 'manual',
            'provider_id' => 'joki-rank-epic',
            'harga_member' => 100000,
            'harga_platinum' => 100000,
            'harga_gold' => 100000,
            'profit_member' => 10000,
            'profit_platinum' => 10000,
            'profit_gold' => 10000,
        ]);

        Method::query()->create([
            'name' => 'Manual Transfer',
            'code' => 'MANUAL',
            'payment' => 'manual',
            'tipe' => 'manual',
            'images' => 'manual.png',
            'keterangan' => 'Manual transfer',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);

        $response = $this->postJson('/api/v2/order/store', [
            'service' => $service->id,
            'payment_method' => 'MANUAL',
            'nomor' => '081234567890',
            'ktg_tipe' => 'joki',
            'qty' => 2,
            'email_joki' => 'player@example.test',
            'password_joki' => 'secret-pass',
            'loginvia_joki' => 'Moonton',
            'nickname_joki' => 'PlayerOne',
            'request_joki' => 'Push sampai Legend',
            'catatan_joki' => 'Main malam',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('payment.amount', 200000);

        $orderId = $response->json('order_id');
        $order = Pembelian::query()->where('order_id', $orderId)->firstOrFail();

        $this->assertSame('-', $order->user_id);
        $this->assertSame('-', $order->zone);
        $this->assertSame('-', $order->nickname);
        $this->assertSame('joki', $order->tipe_transaksi);

        $this->assertDatabaseHas('data_joki', [
            'order_id' => $orderId,
            'email_joki' => 'player@example.test',
            'password_joki' => 'secret-pass',
            'loginvia_joki' => 'Moonton',
            'nickname_joki' => 'PlayerOne',
            'request_joki' => 'Push sampai Legend',
            'catatan_joki' => 'Main malam',
            'qty' => 2,
            'status_joki' => 'Pending',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Method;
use App\Models\Deposit;
use App\Models\Pembayaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DepositTransactionTest extends TestCase
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

    private function fakeTripayResponse()
    {
        Http::fake([
            'tripay.co.id/*' => Http::response([
                'success' => true,
                'message' => 'Success',
                'data' => [
                    'reference' => 'T123456789',
                    'merchant_ref' => 'DP123',
                    'payment_selection_type' => 'static',
                    'payment_method' => 'BCA',
                    'payment_name' => 'BCA',
                    'customer_name' => 'User',
                    'customer_email' => 'user@example.com',
                    'customer_phone' => '08123456789',
                    'callback_url' => 'http://example.com',
                    'return_url' => 'http://example.com',
                    'amount' => 10000,
                    'fee_merchant' => 0,
                    'fee_customer' => 0,
                    'total_fee' => 0,
                    'amount_received' => 10000,
                    'pay_code' => '1234567890',
                    'pay_url' => 'https://tripay.co.id/checkout/T123456789',
                    'checkout_url' => 'https://tripay.co.id/checkout/T123456789',
                    'status' => 'UNPAID',
                    'expired_time' => time() + 3600,
                    'order_items' => []
                ]
            ], 200)
        ]);
    }

    public function test_deposit_invoice_returns_json_for_ajax_request()
    {
        $this->fakeTripayResponse();

        $user = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($user)->postJson('/id/deposit', [
            'jumlah' => 15000,
            'metode' => 'BCA',
            'no_telfon' => '08123456789',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('amount', 15000)
                 ->assertJsonStructure(['order_id', 'pay_url', 'expired_at']);
    }

    public function test_deposit_returns_blade_view_for_regular_request()
    {
        $this->fakeTripayResponse();

        $user = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($user)->post('/id/deposit', [
            'jumlah' => 15000,
            'metode' => 'BCA',
            'no_telfon' => '08123456789',
        ]);

        $response->assertStatus(302);
        
        $deposit = Deposit::where('username', $user->username)->first();
        $this->assertNotNull($deposit);
        
        $response->assertRedirect(route('deposit.invoice', $deposit->order_id));
    }



    public function test_deposit_prevents_duplicate_pending_transactions()
    {
        $this->fakeTripayResponse();
        $user = User::factory()->create(['role' => 'Member']);

        // First request
        $this->actingAs($user)->postJson('/id/deposit', [
            'jumlah' => 15000,
            'metode' => 'BCA',
        ]);

        // Second request with exact same parameters within 2 minutes
        $response = $this->actingAs($user)->postJson('/id/deposit', [
            'jumlah' => 15000,
            'metode' => 'BCA',
        ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors('msg');
                 
        $this->assertStringContainsString('Transaksi deposit serupa sudah dibuat', session('errors')->first('msg'));
    }

    public function test_deposit_prevents_race_conditions_via_cache_lock()
    {
        $this->fakeTripayResponse();
        $user = User::factory()->create(['role' => 'Member']);

        // Manually acquire the lock to simulate a race condition (first request is processing)
        $submitLockKey = 'deposit-submit:' . sha1(implode('|', [
            $user->id,
            'BCA',
            15000,
            '', // normalized phone
        ]));
        
        Cache::add($submitLockKey, true, 30);

        // Try to submit while the lock is active
        $response = $this->actingAs($user)->postJson('/id/deposit', [
            'jumlah' => 15000,
            'metode' => 'BCA',
        ]);

        $response->assertStatus(302)
                 ->assertSessionHasErrors('msg');
                 
        $this->assertStringContainsString('Permintaan sebelumnya masih diproses', session('errors')->first('msg'));
    }
}

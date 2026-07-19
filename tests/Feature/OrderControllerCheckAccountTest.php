<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderControllerCheckAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        Http::preventStrayRequests();
    }

    public function test_check_account_uses_selected_layanan_dynamic_digiflazz_inquiry_sku(): void
    {
        $this->seed(\Database\Seeders\SettingWebsSeeder::class);
        DB::table('setting_webs')->where('id', 1)->update([
            'username_digi' => 'demo_digi_user',
            'api_key_digi' => 'demo_digi_key',
        ]);

        $kategori = Kategori::factory()->create([
            'kode' => 'custom-game',
            'tipe' => 'game',
        ]);

        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'check_id_enabled' => true,
            'check_id_provider' => 'digiflazz',
            'check_id_provider_sku' => 'CUSTOM_INQUIRY_SKU',
        ]);

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status' => false,
                'message' => 'User not found',
            ]),
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'data' => [
                    'status' => 'Sukses',
                    'customer_name' => 'Custom Dynamic Nick',
                    'message' => 'Transaksi Sukses',
                ],
            ]),
        ]);

        $response = $this->postJson('/ajax/check-account', [
            'uid' => 'CUSTOM_UID',
            'zone' => '',
            'kategori_kode' => 'custom-game',
            'service' => $layanan->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('status.code', 200)
            ->assertJsonPath('data.username', 'Custom Dynamic Nick');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.digiflazz.com/v1/transaction'
                && $request['buyer_sku_code'] === 'CUSTOM_INQUIRY_SKU'
                && $request['customer_no'] === 'CUSTOM_UID';
        });
    }

    public function test_check_account_rejects_layanan_from_different_category(): void
    {
        $requestedKategori = Kategori::factory()->create([
            'kode' => 'custom-game',
            'tipe' => 'game',
        ]);
        $otherKategori = Kategori::factory()->create([
            'kode' => 'other-game',
            'tipe' => 'game',
        ]);

        $layanan = Layanan::factory()->create([
            'kategori_id' => $otherKategori->id,
            'check_id_enabled' => true,
            'check_id_provider' => 'digiflazz',
            'check_id_provider_sku' => 'OTHER_SKU',
        ]);

        $response = $this->postJson('/ajax/check-account', [
            'uid' => 'CUSTOM_UID',
            'kategori_kode' => $requestedKategori->kode,
            'service' => $layanan->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status.code', 422);

        Http::assertNothingSent();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VipResellerCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_vip_callback_maps_success_status(): void
    {
        $this->createSettings();

        Pembelian::query()->create([
            'order_id' => 'ORDER-VIP-001',
            'username' => 'viptest',
            'user_id' => '12345678',
            'zone' => '2001',
            'layanan' => 'Diamond FF 120',
            'profit' => 0,
            'status' => 'Pending',
            'provider_order_id' => 'VP123',
            'harga' => 15000,
        ]);

        Pembayaran::query()->create([
            'order_id' => 'ORDER-VIP-001',
            'harga' => '15000',
            'no_pembayaran' => 'QRIS-ORDER-VIP-001',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        $response = $this->withHeaders([
            'X-Client-Signature' => md5('vip-idvip-key'),
        ])->postJson('/wejizy/vip/callback', [
            'data' => [
                'trxid' => 'VP123',
                'data' => '12345678',
                'zone' => '2001',
                'service' => 'Diamond FF 120',
                'status' => 'success',
                'note' => '',
                'price' => 15000,
            ],
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pembelians', [
            'order_id' => 'ORDER-VIP-001',
            'status' => 'Sukses',
            'provider_order_id' => 'VP123',
        ]);
    }

    public function test_vip_callback_maps_partial_to_processing_with_note(): void
    {
        $this->createSettings();

        Pembelian::query()->create([
            'order_id' => 'ORDER-VIP-002',
            'username' => 'viptest',
            'user_id' => '12345678',
            'zone' => '2001',
            'layanan' => 'Diamond FF 355',
            'profit' => 0,
            'status' => 'Pending',
            'provider_order_id' => 'VP999',
            'harga' => 40000,
        ]);

        Pembayaran::query()->create([
            'order_id' => 'ORDER-VIP-002',
            'harga' => '40000',
            'no_pembayaran' => 'QRIS-ORDER-VIP-002',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
        ]);

        $response = $this->withHeaders([
            'X-Client-Signature' => md5('vip-idvip-key'),
        ])->postJson('/wejizy/vip/callback', [
            'data' => [
                'trxid' => 'VP999',
                'data' => '12345678',
                'zone' => '2001',
                'service' => 'Diamond FF 355',
                'status' => 'partial',
                'note' => 'Sebagian berhasil diproses',
                'price' => 40000,
            ],
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pembelians', [
            'order_id' => 'ORDER-VIP-002',
            'status' => 'Processing',
        ]);

        $this->assertStringContainsString(
            'VIP partial',
            (string) Pembelian::query()->where('order_id', 'ORDER-VIP-002')->value('keterangan_sn')
        );
    }

    private function createSettings(): void
    {
        SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Description',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'topupindo-test',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'paydisini-test',
            'order_prefik' => 'INV',
            'vip_apiid' => 'vip-id',
            'vip_apikey' => 'vip-key',
        ]);
    }
}

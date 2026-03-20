<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Kategori;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\ResetCallbackDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResetOutboundCallbackIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_reset_status_updates_do_not_resend_delivered_callbacks(): void
    {
        config()->set('providers.digiflazz.webhook_secret', 'digiflazz-secret');

        Http::fake([
            'https://partner.example.test/reset-callback' => Http::response(['ok' => true], 200),
        ]);

        $user = User::create([
            'name' => 'Partner Member',
            'username' => 'partner-member',
            'email' => 'partner@example.test',
            'password' => bcrypt('password'),
            'role' => 'Member',
            'api_key' => 'partner-api-key',
            'balance' => 0,
            'point_balance' => 0,
            'reset_callback_enabled' => true,
            'reset_callback_url' => 'https://partner.example.test/reset-callback',
            'reset_callback_secret' => 'callback-secret',
            'reset_callback_signing_algorithm' => 'sha256',
            'reset_callback_version' => 1,
        ]);

        $kategori = Kategori::create([
            'nama' => 'Mobile Legends',
            'sub_nama' => 'Diamonds',
        ]);

        $layanan = Layanan::create([
            'layanan' => 'Mobile Legends 86 Diamond',
            'provider_id' => 'digiflazz-ml-86',
            'provider' => 'digiflazz',
            'harga' => 10000,
            'harga_member' => 11000,
            'harga_platinum' => 11500,
            'harga_gold' => 12000,
            'profit_member' => 10,
            'profit_platinum' => 15,
            'profit_gold' => 20,
            'status' => 'active',
            'kategori_id' => $kategori->getKey(),
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-RESET-CB-002',
            'base_order_id' => 'INV-RESET-CB-002',
            'display_order_id' => 'INV-RESET-CB-002_001',
            'active_attempt_reference' => 'INV-RESET-CB-002_001',
            'invoice_version' => 1,
            'reset_count' => 1,
            'reset_status' => 'processing',
            'username' => $user->username,
            'layanan' => 'Mobile Legends 86 Diamond',
            'harga' => 12000,
            'profit' => 1000,
            'user_id' => '12345678',
            'zone' => '1234',
            'status' => 'Pending',
            'provider_order_id' => 'INV-RESET-CB-002_001',
            'active_layanan_id' => $layanan->getKey(),
            'active_provider_code' => 'digiflazz',
            'active_provider_sku' => 'digiflazz-ml-86',
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => 12000,
            'no_pembayaran' => 'PAY-RESET-002',
            'no_pembeli' => '081234567891',
            'status' => 'Lunas',
            'metode' => 'API Saldo',
            'reference' => 'PARTNER-REF-002',
        ]);

        $payload = [
            'data' => [
                'ref_id' => 'INV-RESET-CB-002_001',
                'status' => 'Sukses',
                'sn' => 'SN-RESET-002',
                'message' => 'Success from provider',
            ],
        ];

        $headers = [
            'X-Hub-Signature' => 'sha1=' . hash_hmac('sha1', json_encode($payload), 'digiflazz-secret'),
            'X-Digiflazz-Event' => 'update',
        ];

        $this->withHeaders($headers)->postJson('/wejizy/digi/payload', $payload)->assertOk();
        $this->withHeaders($headers)->postJson('/wejizy/digi/payload', $payload)->assertOk();

        Http::assertSentCount(1);

        $delivery = ResetCallbackDelivery::firstOrFail();

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame(1, $delivery->attempt_count);
        $this->assertCount(1, ResetCallbackDelivery::all());
    }
}

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

class ResetOutboundCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_delivers_reset_callback_payload_after_status_transition(): void
    {
        config()->set('providers.digiflazz.webhook_secret', 'digiflazz-secret');

        Http::fake([
            'https://partner.example.test/reset-callback' => Http::response(['ok' => true], 202),
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
            'reset_callback_version' => 2,
        ]);

        $kategori = Kategori::create([
            'nama' => 'Mobile Legends',
            'sub_nama' => 'Diamonds',
        ]);

        $layanan = Layanan::create([
            'layanan' => 'Mobile Legends 86 Diamond',
            'provider_id' => 'digi-ml-86',
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
            'order_id' => 'INV-RESET-CB-001',
            'base_order_id' => 'INV-RESET-CB-001',
            'display_order_id' => 'INV-RESET-CB-001_001',
            'active_attempt_reference' => 'INV-RESET-CB-001_001',
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
            'provider_order_id' => 'INV-RESET-CB-001_001',
            'active_layanan_id' => $layanan->getKey(),
            'active_provider_code' => 'vip',
            'active_provider_sku' => 'vip-ml-86',
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => 12000,
            'no_pembayaran' => 'PAY-RESET-001',
            'no_pembeli' => '081234567890',
            'status' => 'Lunas',
            'metode' => 'API Saldo',
            'reference' => 'PARTNER-REF-001',
        ]);

        $payload = [
            'data' => [
                'ref_id' => 'INV-RESET-CB-001_001',
                'status' => 'Sukses',
                'sn' => 'SN-RESET-001',
                'message' => 'Success from provider',
            ],
        ];

        $response = $this->withHeaders([
            'X-Hub-Signature' => 'sha1=' . hash_hmac('sha1', json_encode($payload), 'digiflazz-secret'),
            'X-Digiflazz-Event' => 'update',
        ])->postJson('/wejizy/digi/payload', $payload);

        $response->assertOk();

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return $request->url() === 'https://partner.example.test/reset-callback'
                && $request->hasHeader('X-Reset-Callback-Event', 'reset_transaction.status_changed')
                && $request->hasHeader('X-Reset-Callback-Idempotency-Key')
                && $request->hasHeader('X-Reset-Callback-Signature-Alg', 'sha256')
                && $request->hasHeader('X-Reset-Callback-Version', '2')
                && $body['order']['order_id'] === 'INV-RESET-CB-001'
                && $body['order']['display_order_id'] === 'INV-RESET-CB-001_001'
                && $body['order']['attempt_reference'] === 'INV-RESET-CB-001_001'
                && $body['order']['invoice_version'] === 1
                && $body['order']['reference_number'] === 'PARTNER-REF-001'
                && $body['transition']['from'] === 'Pending'
                && $body['transition']['to'] === 'Success'
                && $body['provider']['code'] === 'vip'
                && $body['provider']['sku'] === 'vip-ml-86'
                && $body['service']['name'] === 'Mobile Legends 86 Diamond'
                && $body['service']['provider'] === 'vip'
                && $body['service']['provider_sku'] === 'vip-ml-86'
                && $body['status']['api_code'] === 'Success';
        });

        $delivery = ResetCallbackDelivery::firstOrFail();

        $this->assertSame('delivered', $delivery->status);
        $this->assertSame('reset-callback:' . $pembelian->getKey() . ':1:Success', $delivery->idempotency_key);
        $this->assertSame(202, $delivery->last_response_status);
        $this->assertSame('INV-RESET-CB-001_001', $delivery->attempt_reference);
        $this->assertNotNull($delivery->delivered_at);
    }
}

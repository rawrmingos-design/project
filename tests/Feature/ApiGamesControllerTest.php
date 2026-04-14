<?php

namespace Tests\Feature;

use App\Http\Controllers\provider\ApiGamesController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiGamesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_apigames_order_request_uses_v2_payload_and_server_id_field(): void
    {
        Http::fake(function ($request) {
            $this->assertSame('https://v1.apigames.id/v2/transaksi', $request->url());
            $this->assertSame('POST', $request->method());

            $payload = $request->data();

            $this->assertSame('REF-API-001', $payload['ref_id'] ?? null);
            $this->assertSame('merchant-123', $payload['merchant_id'] ?? null);
            $this->assertSame('ML86', $payload['produk'] ?? null);
            $this->assertSame('12345678', $payload['tujuan'] ?? null);
            $this->assertSame('2001', $payload['server_id'] ?? null);
            $this->assertSame(md5('merchant-123:secret-123:REF-API-001'), $payload['signature'] ?? null);

            return Http::response([
                'status' => 1,
                'data' => [
                    'trx_id' => 'TRX-001',
                    'ref_id' => 'REF-API-001',
                    'status' => 'Pending',
                    'message' => 'Transaksi pending',
                ],
            ]);
        });

        $controller = new ApiGamesController([
            'merchant_id' => 'merchant-123',
            'secret_key' => 'secret-123',
            'endpoint' => 'https://v1.apigames.id/v2',
        ]);

        $response = $controller->order('12345678', '2001', 'ML86', 'REF-API-001');

        $this->assertTrue($response['result']);
        $this->assertSame('Pending', $response['data']['status']);
    }

    public function test_apigames_status_request_uses_v2_status_endpoint(): void
    {
        Http::fake(function ($request) {
            $this->assertSame('https://v1.apigames.id/v2/transaksi/status', $request->url());
            $this->assertSame('POST', $request->method());

            $payload = $request->data();

            $this->assertSame('REF-API-002', $payload['ref_id'] ?? null);
            $this->assertSame('merchant-123', $payload['merchant_id'] ?? null);
            $this->assertSame(md5('merchant-123:secret-123:REF-API-002'), $payload['signature'] ?? null);

            return Http::response([
                'status' => 1,
                'data' => [
                    'trx_id' => 'TRX-002',
                    'ref_id' => 'REF-API-002',
                    'status' => 'Sukses',
                    'sn' => 'SN-OK',
                    'message' => 'Transaksi sukses',
                ],
            ]);
        });

        $controller = new ApiGamesController([
            'merchant_id' => 'merchant-123',
            'secret_key' => 'secret-123',
            'endpoint' => 'https://v1.apigames.id/v2',
        ]);

        $response = $controller->status('REF-API-002');

        $this->assertTrue($response['result']);
        $this->assertSame('Sukses', $response['data']['status']);
    }

    public function test_apigames_balance_request_uses_account_info_endpoint(): void
    {
        Http::fake(function ($request) {
            $this->assertSame('https://v1.apigames.id/merchant/merchant-123?signature=' . md5('merchant-123' . 'secret-123'), $request->url());
            $this->assertSame('GET', $request->method());

            return Http::response([
                'status' => 1,
                'message' => 'Sukses !',
                'data' => [
                    'merchant_id' => 'merchant-123',
                    'nama' => 'Demo Merchant',
                    'saldo' => 245,
                ],
            ]);
        });

        $controller = new ApiGamesController([
            'merchant_id' => 'merchant-123',
            'secret_key' => 'secret-123',
            'endpoint' => 'https://v1.apigames.id/v2',
        ]);

        $response = $controller->balance();

        $this->assertTrue($response['result']);
        $this->assertSame(245, $response['balance']);
        $this->assertSame('merchant-123', $response['data']['merchant_id']);
    }
}

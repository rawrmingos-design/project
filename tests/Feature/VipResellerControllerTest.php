<?php

namespace Tests\Feature;

use App\Http\Controllers\provider\VipResellerController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VipResellerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_vip_status_request_uses_api_key_and_signature(): void
    {
        Http::fake(function ($request) {
            parse_str($request->body(), $payload);

            $this->assertSame('apikey-123', $payload['key'] ?? null);
            $this->assertSame(md5('apiid-123apikey-123'), $payload['sign'] ?? null);
            $this->assertSame('status', $payload['type'] ?? null);
            $this->assertSame('VP123', $payload['trxid'] ?? null);

            return Http::response([
                'result' => true,
                'data' => [],
                'message' => 'ok',
            ]);
        });

        $controller = new VipResellerController([
            'api_id' => 'apiid-123',
            'api_key' => 'apikey-123',
        ]);

        $response = $controller->status('VP123');

        $this->assertTrue($response['result']);
    }

    public function test_vip_nickname_request_uses_documented_parameters(): void
    {
        Http::fake(function ($request) {
            parse_str($request->body(), $payload);

            $this->assertSame('get-nickname', $payload['type'] ?? null);
            $this->assertSame('MLBB', $payload['code'] ?? null);
            $this->assertSame('12345678', $payload['target'] ?? null);
            $this->assertSame('2001', $payload['additional_target'] ?? null);

            return Http::response([
                'result' => true,
                'data' => 'nickname',
                'message' => 'Success.',
            ]);
        });

        $controller = new VipResellerController([
            'api_id' => 'apiid-123',
            'api_key' => 'apikey-123',
        ]);

        $response = $controller->username('12345678', '2001', 'MLBB');

        $this->assertTrue($response['result']);
        $this->assertSame('nickname', $response['data']);
    }
}

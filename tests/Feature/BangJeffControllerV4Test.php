<?php

namespace Tests\Feature;

use App\Http\Controllers\provider\BangJeffController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BangJeffControllerV4Test extends TestCase
{
    public function test_balance_request_uses_v4_signature_headers_and_payload(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-31 10:15:00+07:00'));

        Http::fake([
            'https://sandbox-api.bangjeff.com/api/v4/balance' => Http::response([
                'rc' => '00',
                'message' => 'Success',
                'data' => [
                    'balance' => [
                        'currency' => 'IDR',
                        'value' => 324500,
                    ],
                ],
            ], 200),
        ]);

        try {
            $controller = new BangJeffController([
                'api_key' => 'api-key-xyz',
                'endpoint' => 'https://sandbox-api.bangjeff.com',
                'region' => 'ID',
            ]);

            $response = $controller->balance();

            $this->assertSame('00', $response['rc']);
            $this->assertSame(324500, $response['data']['balance']['value']);

            Http::assertSent(function ($request): bool {
                $payload = ['region' => 'ID'];
                $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
                $timestamp = '2026-03-31T10:15:00+07:00';
                $signaturePayload = 'POST:api/v4/balance:' . md5((string) $payloadJson) . ':' . $timestamp;
                $expectedSignature = hash_hmac('sha256', $signaturePayload, 'api-key-xyz');

                return $request->url() === 'https://sandbox-api.bangjeff.com/api/v4/balance'
                    && $request->hasHeader('X-Client-Id', 'api-key-xyz')
                    && $request->hasHeader('X-Request-Time', $timestamp)
                    && $request->hasHeader('X-Signature', $expectedSignature)
                    && $request->hasHeader('Content-Type', 'application/json')
                    && $request->data() === $payload;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_auto_switches_to_sandbox_endpoint_in_local_like_environment(): void
    {
        config()->set('providers.bangjeff.use_sandbox_on_local', true);
        config()->set('providers.bangjeff.sandbox_base_url', 'https://sandbox-api.bangjeff.com');

        Http::fake([
            'https://sandbox-api.bangjeff.com/api/v4/balance' => Http::response([
                'rc' => '00',
                'message' => 'Success',
                'data' => ['balance' => ['currency' => 'IDR', 'value' => 1000]],
            ], 200),
        ]);

        $controller = new BangJeffController([
            'api_key' => 'api-key-xyz',
            'endpoint' => 'https://distribution-api.bangjeff.com',
            'region' => 'ID',
        ]);

        $controller->balance();

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sandbox-api.bangjeff.com/api/v4/balance');
    }

    public function test_it_can_disable_local_sandbox_switch_via_config(): void
    {
        config()->set('providers.bangjeff.use_sandbox_on_local', false);
        config()->set('providers.bangjeff.sandbox_base_url', 'https://sandbox-api.bangjeff.com');

        Http::fake([
            'https://distribution-api.bangjeff.com/api/v4/balance' => Http::response([
                'rc' => '00',
                'message' => 'Success',
                'data' => ['balance' => ['currency' => 'IDR', 'value' => 1000]],
            ], 200),
        ]);

        $controller = new BangJeffController([
            'api_key' => 'api-key-xyz',
            'endpoint' => 'https://distribution-api.bangjeff.com',
            'region' => 'ID',
        ]);

        $controller->balance();

        Http::assertSent(fn ($request): bool => $request->url() === 'https://distribution-api.bangjeff.com/api/v4/balance');
    }
}

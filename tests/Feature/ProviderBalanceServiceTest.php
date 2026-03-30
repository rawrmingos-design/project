<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Services\ProviderBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_vip_balance_and_updates_provider_record(): void
    {
        Http::fake([
            'https://vip-reseller.co.id/api/profile' => Http::response([
                'result' => true,
                'data' => [
                    'balance' => 200500,
                ],
                'message' => 'Successfully got your account details.',
            ]),
        ]);

        $provider = Provider::create([
            'code' => 'vip_reseller',
            'name' => 'VIP Reseller',
            'api_username' => 'apiid-123',
            'api_key' => 'apikey-123',
            'is_active' => true,
            'balance' => 0,
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame(200500.0, (float) $provider->balance);
        $this->assertNotNull($provider->last_check_at);
    }

    public function test_it_normalizes_vip_balance_string_format_before_save(): void
    {
        Http::fake([
            'https://vip-reseller.co.id/api/profile' => Http::response([
                'result' => true,
                'data' => [
                    'balance' => '1.234,56',
                ],
                'message' => 'Successfully got your account details.',
            ]),
        ]);

        $provider = Provider::create([
            'code' => 'vip',
            'name' => 'VIP',
            'api_username' => 'apiid-123',
            'api_key' => 'apikey-123',
            'is_active' => true,
            'balance' => 0,
        ]);

        app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertSame(1234.56, (float) $provider->balance);
    }

    public function test_it_reads_bangjeff_v4_balance_value_field(): void
    {
        Http::fake([
            'https://sandbox-api.bangjeff.com/api/v4/balance' => Http::response([
                'rc' => '00',
                'message' => 'Success',
                'data' => [
                    'balance' => [
                        'currency' => 'IDR',
                        'value' => 987654,
                    ],
                ],
            ]),
        ]);

        $provider = Provider::create([
            'code' => 'bangjeff',
            'name' => 'BangJeff',
            'api_key' => 'bj-api-key',
            'api_endpoint' => 'https://sandbox-api.bangjeff.com',
            'is_active' => true,
            'balance' => 0,
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame(987654.0, (float) $provider->balance);
    }
}

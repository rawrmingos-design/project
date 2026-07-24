<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\SettingWeb;
use App\Services\ProviderBalanceService;
use App\Services\Providers\BangJeffService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BangJeffServiceCredentialsTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        SettingWeb::query()->delete();
        Provider::query()->delete();
    }

    public function test_admin_settings_api_key_is_used_for_balance_requests(): void
    {
        $this->createSettings('admin-key-123');
        config()->set('providers.bangjeff.api_key', null);
        config()->set('providers.bangjeff.use_sandbox_on_local', true);
        config()->set('providers.bangjeff.sandbox_base_url', 'https://sandbox-api.bangjeff.com');

        Http::fake([
            'https://sandbox-api.bangjeff.com/api/v4/balance' => Http::response([
                'rc' => '00',
                'message' => 'Success',
                'data' => ['balance' => ['currency' => 'IDR', 'value' => 1000]],
            ]),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 10:15:00+07:00'));

        try {
            (new BangJeffService())->balance();

            Http::assertSent(fn ($request): bool => $request->hasHeader('X-Client-Id', 'admin-key-123'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_explicit_override_api_key_wins_over_admin_settings(): void
    {
        $this->createSettings('admin-key-123');
        config()->set('providers.bangjeff.api_key', null);
        config()->set('providers.bangjeff.use_sandbox_on_local', true);
        config()->set('providers.bangjeff.sandbox_base_url', 'https://sandbox-api.bangjeff.com');

        Http::fake([
            'https://sandbox-api.bangjeff.com/api/v4/balance' => Http::response([
                'rc' => '00',
                'message' => 'Success',
                'data' => ['balance' => ['currency' => 'IDR', 'value' => 1000]],
            ]),
        ]);

        (new BangJeffService(['api_key' => 'override-key-456']))->balance();

        Http::assertSent(fn ($request): bool => $request->hasHeader('X-Client-Id', 'override-key-456'));
    }

    public function test_env_config_key_is_used_when_admin_settings_key_is_empty(): void
    {
        $this->createSettings('');
        config()->set('providers.bangjeff.api_key', 'env-key-789');
        config()->set('providers.bangjeff.use_sandbox_on_local', true);
        config()->set('providers.bangjeff.sandbox_base_url', 'https://sandbox-api.bangjeff.com');

        Http::fake([
            'https://sandbox-api.bangjeff.com/api/v4/balance' => Http::response([
                'rc' => '00',
                'message' => 'Success',
                'data' => ['balance' => ['currency' => 'IDR', 'value' => 1000]],
            ]),
        ]);

        (new BangJeffService())->balance();

        Http::assertSent(fn ($request): bool => $request->hasHeader('X-Client-Id', 'env-key-789'));
    }

    public function test_provider_balance_service_uses_admin_key_over_bangjeff_provider_row_key(): void
    {
        $this->createSettings('admin-key-123');
        config()->set('providers.bangjeff.api_key', 'env-key-789');
        config()->set('providers.bangjeff.use_sandbox_on_local', true);
        config()->set('providers.bangjeff.sandbox_base_url', 'https://sandbox-api.bangjeff.com');

        $provider = Provider::query()->create([
            'code' => 'bangjeff',
            'name' => 'BangJeff',
            'balance' => 0,
            'is_active' => true,
        ]);

        Http::fake([
            'https://sandbox-api.bangjeff.com/api/v4/balance' => Http::response([
                'rc' => '00',
                'message' => 'Success',
                'data' => ['balance' => ['currency' => 'IDR', 'value' => 582]],
            ]),
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $this->assertTrue($result['success']);
        $this->assertSame(582.0, $result['balance']);
        $this->assertEquals(582.0, (float) $provider->fresh()->balance);
        Http::assertSent(fn ($request): bool => $request->hasHeader('X-Client-Id', 'admin-key-123'));
    }

    private function createSettings(string $bangJeffApiKey): SettingWeb
    {
        return SettingWeb::query()->create([
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/test',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'test_api',
            'warna1' => '#222222',
            'warna2' => '#d06800',
            'warna3' => '#ffa54a',
            'warna4' => '#ff8040',
            'paydisini_apikey' => 'test_paydisini',
            'order_prefik' => 'INV',
            'apikey_bangjeff' => $bangJeffApiKey,
        ]);
    }
}

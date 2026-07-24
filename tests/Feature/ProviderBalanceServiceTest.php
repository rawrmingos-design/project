<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\SettingWeb;
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

        $this->createSettings([
            'vip_apiid' => 'settings-apiid',
            'vip_apikey' => 'settings-apikey',
            'vip_sign' => 'settings-sign',
        ]);

        $provider = Provider::create([
            'code' => 'vip_reseller',
            'name' => 'VIP Reseller',
            'is_active' => true,
            'balance' => 0,
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame(200500.0, (float) $provider->balance);
        $this->assertNotNull($provider->last_check_at);
        Http::assertSent(fn ($request): bool => $request->data()['key'] === 'settings-apikey'
            && $request->data()['sign'] === 'settings-sign');
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

        $this->createSettings([
            'vip_apiid' => 'settings-apiid',
            'vip_apikey' => 'settings-apikey',
        ]);

        $provider = Provider::create([
            'code' => 'vip',
            'name' => 'VIP',
            'is_active' => true,
            'balance' => 0,
        ]);

        app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertSame(1234.56, (float) $provider->balance);
    }

    public function test_it_reads_bangjeff_v4_balance_value_field(): void
    {
        $this->createSettings([
            'apikey_bangjeff' => 'settings-bangjeff-key',
        ]);
        config()->set('providers.bangjeff.api_key', null);
        config()->set('providers.bangjeff.use_sandbox_on_local', true);
        config()->set('providers.bangjeff.sandbox_base_url', 'https://sandbox-api.bangjeff.com');

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
            'api_endpoint' => 'https://sandbox-api.bangjeff.com',
            'is_active' => true,
            'balance' => 0,
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame(987654.0, (float) $provider->balance);
    }

    public function test_it_syncs_apigames_balance_and_updates_provider_record(): void
    {
        Http::fake([
            'https://v1.apigames.id/merchant/settings-merchant*' => Http::response([
                'status' => 1,
                'message' => 'Sukses !',
                'data' => [
                    'merchant_id' => 'demo-merchant',
                    'nama' => 'Demo Merchant',
                    'saldo' => 245000,
                ],
            ]),
        ]);

        $this->createSettings([
            'apigames_merchant' => 'settings-merchant',
            'apigames_secret' => 'settings-secret',
        ]);

        $provider = Provider::create([
            'code' => 'apigames',
            'name' => 'ApiGames',
            'api_endpoint' => 'https://v1.apigames.id/v2',
            'is_active' => true,
            'balance' => 0,
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame(245000.0, (float) $provider->balance);
        $this->assertNotNull($provider->last_check_at);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/merchant/settings-merchant'));
    }

    public function test_it_syncs_digiflazz_balance_using_settings_credentials_over_provider_row(): void
    {
        $this->createSettings([
            'username_digi' => 'settings-digi-user',
            'api_key_digi' => 'settings-digi-key',
        ]);

        Http::fake([
            'https://api.digiflazz.com/v1/cek-saldo' => Http::response([
                'data' => [
                    'deposit' => 321000,
                ],
            ]),
        ]);

        $provider = Provider::create([
            'code' => 'digiflazz',
            'name' => 'Digiflazz',
            'is_active' => true,
            'balance' => 0,
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame(321000.0, (float) $provider->balance);
        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return ($data['username'] ?? null) === 'settings-digi-user'
                && ($data['cmd'] ?? null) === 'deposit'
                && ($data['sign'] ?? null) === md5('settings-digi-user' . 'settings-digi-key' . 'depo');
        });
    }

    public function test_it_syncs_sufpayment_balance_and_updates_provider_record(): void
    {
        Http::fake([
            'https://sufpayment.com/api/v1/account' => Http::response([
                'response' => true,
                'data' => [
                    'username' => 'demouser',
                    'level' => 'basic',
                    'balance' => 31361,
                ],
            ]),
        ]);

        $this->createSettings([
            'sufpayment_api_id' => '713',
            'sufpayment_api_key' => 'settings-sufpayment-key',
            'sufpayment_secret_key' => 'settings-sufpayment-secret',
        ]);

        $provider = Provider::query()->where('code', 'sufpayment')->firstOrFail();
        $provider->update([
            'name' => 'SufPayment',
            'api_endpoint' => 'https://sufpayment.com/api/v1',
            'is_active' => true,
            'balance' => 0,
            'last_check_at' => null,
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertTrue($result['success']);
        $this->assertSame(31361.0, (float) $provider->balance);
        $this->assertNotNull($provider->last_check_at);
        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return $request->url() === 'https://sufpayment.com/api/v1/account'
                && ($data['api_id'] ?? null) === '713'
                && ($data['api_key'] ?? null) === 'settings-sufpayment-key'
                && ($data['secret_key'] ?? null) === 'settings-sufpayment-secret';
        });
    }

    public function test_it_preserves_sufpayment_balance_when_provider_rejects_credentials(): void
    {
        Http::fake([
            'https://sufpayment.com/api/v1/account' => Http::response([
                'response' => false,
                'data' => [
                    'msg' => 'Invalid Secret Key',
                ],
            ]),
        ]);

        $this->createSettings([
            'sufpayment_api_id' => '713',
            'sufpayment_api_key' => 'settings-sufpayment-key',
            'sufpayment_secret_key' => 'bad-secret',
        ]);

        $provider = Provider::query()->where('code', 'sufpayment')->firstOrFail();
        $provider->update([
            'name' => 'SufPayment',
            'api_endpoint' => 'https://sufpayment.com/api/v1',
            'is_active' => true,
            'balance' => 5000,
            'last_check_at' => null,
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $provider->refresh();

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid Secret Key', $result['message']);
        $this->assertSame(5000.0, (float) $provider->balance);
        $this->assertNull($provider->last_check_at);
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::create(array_merge([
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Test store description',
            'keywords' => 'test',
            'logo_header' => 'assets/logo/header.png',
            'logo_footer' => 'assets/logo/footer.png',
            'logo_favicon' => 'assets/logo/favicon.ico',
            'url_wa' => 'https://wa.me/620000000000',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'topupindo_api' => 'test-topupindo-key',
            'apikey_bangjeff' => 'test-bangjeff-key',
            'apikey_aoshi' => 'test-aoshi-key',
            'api_mobilegamestore' => 'test-mgs-key',
            'paydisini_apikey' => 'test-paydisini-key',
            'tripay_api' => 'test-tripay-api',
            'tripay_merchant_code' => 'T0000',
            'tripay_private_key' => 'test-tripay-private',
            'duitku_merchant_code' => 'D0000',
            'duitku_merchant_key' => 'test-duitku-key',
            'duitku_callback_url' => 'https://example.test/callback',
            'duitku_mode' => 'sandbox',
            'deposit_jalur' => 'duitku',
            'duitku_enabled' => 0,
            'tokopay_merchant_id' => 'M0000',
            'tokopay_secret_key' => 'test-tokopay-secret',
            'username_digi' => 'test-digi-user',
            'api_key_digi' => 'test-digi-key',
            'apigames_merchant' => 'test-apigames-merchant',
            'apigames_secret' => 'test-apigames-secret',
            'sufpayment_api_id' => 'test-sufpayment-api-id',
            'sufpayment_api_key' => 'test-sufpayment-api-key',
            'sufpayment_secret_key' => 'test-sufpayment-secret-key',
            'vip_apiid' => 'test-vip-apiid',
            'vip_apikey' => 'test-vip-apikey',
            'nomor_admin' => '620000000000',
            'wa_key' => 'test-wa-key',
            'wa_number' => '620000000000',
            'ovo_admin' => '0',
            'ovo1_admin' => '0',
            'gopay_admin' => '0',
            'gopay1_admin' => '0',
            'dana_admin' => '0',
            'shopeepay_admin' => '0',
            'bca_admin' => '0',
            'order_prefik' => 'TS',
            'commission_percent' => 20,
            'profit_member' => 10,
            'profit_platinum' => 10,
            'profit_gold' => 10,
            'trx_count_gold' => 50,
            'trx_count_platinum' => 100,
        ], $overrides));
    }
}

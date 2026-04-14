<?php

namespace Tests\Feature;

use App\Http\Controllers\ApiCheckController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_primary_success_skips_other_fallbacks(): void
    {
        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => true,
                'nickname' => 'Primary ML Nick',
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', '2222', 'Mobile Legends');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('Primary ML Nick', $result['data']['username']);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api-cek-id-game-ten.vercel.app/api/check-id-game');
    }

    public function test_velixs_success_runs_after_primary_failure_and_skips_apigames(): void
    {
        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status' => true,
                'data' => ['username' => 'Velixs Nick'],
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', '2222', 'Mobile Legends');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('Velixs Nick', $result['data']['username']);

        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'v1.apigames.id'));
    }

    public function test_apigames_handles_mobile_legends_after_primary_and_velixs_fail(): void
    {
        $this->seedApiGamesSettings();

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status' => false,
                'message' => 'User not found',
            ]),
            'https://v1.apigames.id/merchant/demo-merchant/cek-username/mobilelegend*' => Http::response([
                'status' => 1,
                'rc' => 0,
                'message' => 'Data Found',
                'data' => [
                    'is_valid' => true,
                    'username' => 'ApiGames ML Nick',
                ],
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', '2222', 'Mobile Legends');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('ApiGames ML Nick', $result['data']['username']);

        Http::assertSentCount(3);
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/cek-username/mobilelegend')) {
                return false;
            }

            return $request['user_id'] === '1234562222'
                && $request['signature'] === md5('demo-merchant' . 'demo-secret');
        });
    }

    public function test_apigames_handles_free_fire_after_primary_and_velixs_fail(): void
    {
        $this->seedApiGamesSettings();

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status' => false,
                'message' => 'User not found',
            ]),
            'https://v1.apigames.id/merchant/demo-merchant/cek-username/freefire*' => Http::response([
                'status' => 1,
                'rc' => 0,
                'message' => 'Data Found',
                'data' => [
                    'is_valid' => true,
                    'username' => 'ApiGames FF Nick',
                ],
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('777888', null, 'Free Fire');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('ApiGames FF Nick', $result['data']['username']);

        Http::assertSentCount(3);
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/cek-username/freefire')) {
                return false;
            }

            return $request['user_id'] === '777888';
        });
    }

    public function test_unsupported_apigames_game_stops_after_velixs_and_returns_not_found(): void
    {
        $this->seedApiGamesSettings();

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status' => false,
                'message' => 'User not found',
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', null, 'Valorant');

        $this->assertSame(404, $result['status']['code']);
        $this->assertSame('User not found', $result['status']['message']);

        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'v1.apigames.id'));
    }

    public function test_missing_apigames_credentials_skips_third_fallback_safely(): void
    {
        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status' => false,
                'message' => 'User not found',
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', '2222', 'Mobile Legends');

        $this->assertSame(404, $result['status']['code']);
        $this->assertSame('ApiGames credentials are not configured.', $result['status']['message']);

        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'v1.apigames.id'));
    }

    public function test_invalid_apigames_response_returns_not_found(): void
    {
        $this->seedApiGamesSettings();

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status' => false,
                'message' => 'User not found',
            ]),
            'https://v1.apigames.id/merchant/demo-merchant/cek-username/mobilelegend*' => Http::response([
                'status' => 1,
                'rc' => 0,
                'message' => 'Data Not Found',
                'data' => [
                    'is_valid' => false,
                    'username' => '',
                ],
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', '2222', 'Mobile Legends');

        $this->assertSame(404, $result['status']['code']);
        $this->assertSame('Data Not Found', $result['status']['message']);

        Http::assertSentCount(3);
    }

    public function test_successful_result_is_cached_for_identical_request(): void
    {
        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => true,
                'nickname' => 'Cached Nick',
            ]),
        ]);

        $controller = app(ApiCheckController::class);

        $first = $controller->check('888999', '1000', 'Mobile Legends');
        $second = $controller->check('888999', '1000', 'Mobile Legends');

        $this->assertSame('Cached Nick', $first['data']['username']);
        $this->assertSame('Cached Nick', $second['data']['username']);

        Http::assertSentCount(1);
    }

    private function seedApiGamesSettings(?string $merchant = 'demo-merchant', ?string $secret = 'demo-secret'): void
    {
        DB::table('setting_webs')->insert([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Description',
            'keywords' => 'test',
            'logo_header' => null,
            'logo_footer' => null,
            'logo_favicon' => null,
            'url_wa' => 'https://wa.me/6280000000000',
            'url_ig' => 'https://example.com/ig',
            'url_tiktok' => 'https://example.com/tiktok',
            'url_youtube' => 'https://example.com/youtube',
            'url_fb' => 'https://example.com/facebook',
            'topupindo_api' => '',
            'apikey_bangjeff' => null,
            'apikey_aoshi' => null,
            'api_mobilegamestore' => null,
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => '',
            'tripay_api' => null,
            'tripay_merchant_code' => null,
            'tripay_private_key' => null,
            'duitku_merchant_code' => null,
            'duitku_merchant_key' => null,
            'duitku_callback_url' => null,
            'duitku_return_url' => null,
            'duitku_mode' => 'sandbox',
            'deposit_jalur' => 'duitku',
            'duitku_enabled' => 0,
            'tokopay_merchant_id' => null,
            'tokopay_secret_key' => null,
            'username_digi' => null,
            'api_key_digi' => null,
            'apigames_secret' => $secret,
            'apigames_merchant' => $merchant,
            'vip_apiid' => null,
            'vip_apikey' => null,
            'nomor_admin' => null,
            'wa_key' => null,
            'wa_number' => null,
            'ovo_admin' => null,
            'ovo1_admin' => null,
            'gopay_admin' => null,
            'gopay1_admin' => null,
            'dana_admin' => null,
            'shopeepay_admin' => null,
            'bca_admin' => null,
            'order_prefik' => 'INV',
            'commission_percent' => 20,
            'point_per_nominal' => 1,
            'point_value' => 100,
            'max_point_usage_percent' => 50,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
            'trx_count_gold' => 50,
            'trx_count_platinum' => 100,
            'google_analytics_id' => null,
            'facebook_pixel_id' => null,
            'google_tag_manager_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

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

        // Pastikan migration tereksekusi pada sqlite memory
        $this->artisan('migrate');

        Cache::flush();
        putenv('VELIXS_API_KEY=test-velixs-key');
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

    public function test_unsupported_game_returns_not_found_without_digiflazz_request(): void
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
        $this->assertNotSame('', $result['status']['message']);

        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.digiflazz.com'));
    }

    public function test_missing_apigames_credentials_still_runs_selfhosted_fallback(): void
    {
        $this->enableSelfHostedCheckId();

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status' => false,
                'message' => 'User not found',
            ]),
            'https://cekid.jasakoding.web.id/api/check*' => Http::response([
                'status' => true,
                'data' => ['username' => 'Self Hosted Nick'],
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', null, 'Valorant');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('Self Hosted Nick', $result['data']['username']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.digiflazz.com'));
    }

    public function test_missing_velixs_key_does_not_send_request_to_velixs(): void
    {
        config(['providers.check_id.selfhosted.enabled' => false]);
        putenv('VELIXS_API_KEY=');

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', null, 'Valorant');

        $this->assertSame(404, $result['status']['code']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.velixs.com'));
    }

    public function test_all_checker_fallbacks_never_call_digiflazz(): void
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
                'data' => ['is_valid' => false, 'username' => ''],
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', '2222', 'Mobile Legends');

        $this->assertSame(404, $result['status']['code']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.digiflazz.com'));
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
        $this->assertNotSame('', $result['status']['message']);

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
        $this->assertNotSame('', $result['status']['message']);

        Http::assertSentCount(3);
    }

    public function test_selfhosted_runs_before_other_checkers_and_before_digiflazz(): void
    {
        $this->seedApiGamesSettings();
        $this->enableSelfHostedCheckId();

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
                'message' => 'Data Not Found',
                'data' => ['is_valid' => false, 'username' => ''],
            ]),
            'https://cekid.jasakoding.web.id/api/check*' => Http::response([
                'status' => true,
                'message' => 'ID berhasil ditemukan',
                'data' => [
                    'username' => 'Self Hosted Nick',
                    'user_id' => '123456',
                    'zone' => '2222',
                ],
                'provider' => 'codashop',
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', '2222', 'Mobile Legends');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('Self Hosted Nick', $result['data']['username']);

        // Self-hosted dipanggil pertama — hanya 1 request, yang lain tidak disentuh.
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            if (! str_starts_with($request->url(), 'https://cekid.jasakoding.web.id/api/check?')) {
                return false;
            }

            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->hasHeader('x-api-key', 'selfhosted-secret')
                && ($query['slug'] ?? '') === 'mobile-legends'
                && ($query['id'] ?? '') === '123456'
                && ($query['zone'] ?? '') === '2222'
                && ($query['fallback'] ?? '') === '1'
                && ($query['cache'] ?? '') === '1';
        });
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.digiflazz.com'));
    }

    public function test_selfhosted_rejects_non_https_or_local_url_without_requesting_it(): void
    {
        $this->seedApiGamesSettings();
        $this->enableSelfHostedCheckId('http://127.0.0.1:3010');

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
                'message' => 'Data Not Found',
                'data' => ['is_valid' => false, 'username' => ''],
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', '2222', 'Mobile Legends');

        $this->assertSame(404, $result['status']['code']);
        $this->assertNotSame('', $result['status']['message']);

        Http::assertSentCount(3);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '127.0.0.1'));
    }

    public function test_unsupported_apigames_game_can_still_use_selfhosted_fallback(): void
    {
        $this->enableSelfHostedCheckId();

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status' => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status' => false,
                'message' => 'User not found',
            ]),
            'https://cekid.jasakoding.web.id/api/check*' => Http::response([
                'status' => true,
                'data' => ['username' => 'Valorant Self Hosted Nick'],
                'provider' => 'unipin',
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('VALORANT_UID', null, 'Valorant');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('Valorant Self Hosted Nick', $result['data']['username']);

        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'v1.apigames.id'));
        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            return str_starts_with($request->url(), 'https://cekid.jasakoding.web.id/api/check?')
                && ($query['slug'] ?? '') === 'valorant';
        });
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

        // Hanya 1 request ke API — request ke-2 dikembalikan dari cache (DB atau short-term Cache)
        Http::assertSentCount(1);
    }

    private function createVerifiedTableIfMissing(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('verified_game_accounts')) {
            \Illuminate\Support\Facades\Schema::create('verified_game_accounts', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('game');
                $table->string('user_id');
                $table->string('zone_id')->nullable();
                $table->string('nickname');
                $table->string('source')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_db_cache_is_hit_before_any_external_api(): void
    {
        $this->createVerifiedTableIfMissing();

        // Seed DB cache dengan hasil verifikasi sebelumnya
        \App\Models\VerifiedGameAccount::create([
            'game'     => 'mobile_legends',
            'user_id'  => '555666',
            'zone_id'  => '9999',
            'nickname' => 'DB Cached Nick',
            'source'   => 'primary',
        ]);

        // Tidak ada Http::fake() — jika ada request ke API, test akan throw error
        Http::preventStrayRequests();

        $result = app(ApiCheckController::class)->check('555666', '9999', 'Mobile Legends');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('DB Cached Nick', $result['data']['username']);

        // Tidak ada request ke API sama sekali
        Http::assertNothingSent();
    }

    public function test_successful_result_is_saved_to_db_cache(): void
    {
        $this->createVerifiedTableIfMissing();

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status'   => true,
                'nickname' => 'Saved To DB Nick',
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('111222', '3333', 'Mobile Legends');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('Saved To DB Nick', $result['data']['username']);

        // Pastikan disimpan ke tabel DB
        $this->assertDatabaseHas('verified_game_accounts', [
            'game'     => 'mobile_legends',
            'user_id'  => '111222',
            'zone_id'  => '3333',
            'nickname' => 'Saved To DB Nick',
            'source'   => 'primary',
        ]);
    }

    public function test_checker_fallbacks_never_call_digiflazz(): void
    {
        $this->seedApiGamesSettings();

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status'  => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status'  => false,
                'message' => 'User not found',
            ]),
            'https://v1.apigames.id/merchant/demo-merchant/cek-username/mobilelegend*' => Http::response([
                'status'  => 1,
                'message' => 'Data Not Found',
                'data'    => ['is_valid' => false, 'username' => ''],
            ]),
        ]);

        $result = app(ApiCheckController::class)->check('123456', '2222', 'Mobile Legends');

        $this->assertSame(404, $result['status']['code']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.digiflazz.com'));
        Http::assertSentCount(3);
    }

    public function test_legacy_inquiry_configuration_cannot_trigger_digiflazz(): void
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

        $result = app(ApiCheckController::class)->check('LEGACY_UID', null, 'Valorant');

        $this->assertSame(404, $result['status']['code']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.digiflazz.com'));
    }

    private function enableSelfHostedCheckId(string $baseUrl = 'https://cekid.jasakoding.web.id', string $apiKey = 'selfhosted-secret'): void
    {
        config([
            'providers.check_id.selfhosted.enabled' => true,
            'providers.check_id.selfhosted.base_url' => $baseUrl,
            'providers.check_id.selfhosted.api_key' => $apiKey,
            'providers.check_id.selfhosted.timeout' => 5,
            'providers.check_id.selfhosted.connect_timeout' => 3,
        ]);
    }

    private function seedApiGamesSettings(?string $merchant = 'demo-merchant', ?string $secret = 'demo-secret'): void
    {
        $this->seed(\Database\Seeders\SettingWebsSeeder::class);
        DB::table('setting_webs')->where('id', 1)->update([
            'apigames_secret' => $secret,
            'apigames_merchant' => $merchant,
        ]);
    }
}

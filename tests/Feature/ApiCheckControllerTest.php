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
        $this->assertSame('No Digiflazz inquiry SKU configured for game: valorant.', $result['status']['message']);

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
        $this->assertSame('No Digiflazz inquiry SKU configured for game: mobile_legends.', $result['status']['message']);

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
        $this->assertSame('No Digiflazz inquiry SKU configured for game: mobile_legends.', $result['status']['message']);

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

    public function test_digiflazz_fallback_is_skipped_when_no_sku_configured(): void
    {
        // Mobile Legends tidak ada DIGIFLAZZ_INQUIRY_SKUS (null), jadi Digiflazz tidak dipanggil
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

        // Digiflazz tidak dipanggil karena SKU null
        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'api.digiflazz.com'));
        Http::assertSentCount(3);
    }

    public function test_digiflazz_fallback_returns_customer_name_on_success(): void
    {
        $this->seedDigiflazzSettings();

        Http::fake([
            'https://api-cek-id-game-ten.vercel.app/api/check-id-game' => Http::response([
                'status'  => false,
                'message' => 'Data not found',
            ]),
            'https://api.velixs.com/idgames-checker' => Http::response([
                'status'  => false,
                'message' => 'User not found',
            ]),
            // Digiflazz inquiry response — status Sukses + customer_name
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'data' => [
                    'status'        => 'Sukses',
                    'customer_name' => 'Valorant Nick From Digiflazz',
                    'message'       => 'Transaksi Sukses',
                ],
            ]),
        ]);

        // Kita test dengan game yang tidak support ApiGames tapi ada di Digiflazz,
        // menggunakan reflection untuk override konstanta
        $controller = new class extends ApiCheckController {
            // Override konstanta melalui subclass untuk testing
            protected const DIGIFLAZZ_INQUIRY_SKUS = [
                'valorant' => 'VLR_TEST_SKU',
            ];
        };

        $result = $controller->check('VALORANT_UID', null, 'Valorant');

        $this->assertSame(200, $result['status']['code']);
        $this->assertSame('Valorant Nick From Digiflazz', $result['data']['username']);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'api.digiflazz.com'));
    }

    private function seedDigiflazzSettings(): void
    {
        $this->seed(\Database\Seeders\SettingWebsSeeder::class);
        DB::table('setting_webs')->where('id', 1)->update([
            'username_digi' => 'demo_digi_user',
            'api_key_digi' => 'demo_digi_key',
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

<?php

namespace App\Http\Controllers;

use App\Models\VerifiedGameAccount;
use App\Models\SettingWeb;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ApiCheckController extends Controller
{
    private const APIGAMES_GAME_CODES = [
        'mobile_legends' => 'mobilelegend',
        'mobile_legends_bang_bang' => 'mobilelegend',
        'mobilelegend' => 'mobilelegend',
        'mlbb' => 'mobilelegend',
        'free_fire' => 'freefire',
        'garena_free_fire' => 'freefire',
        'freefire' => 'freefire',
        'free_fire_max' => 'freefire',
        'higgs' => 'higgs',
        'higgs_domino' => 'higgs',
    ];

    private const VELIXS_GAME_CODES = [
        'free_fire' => 'freefire',
        'garena_free_fire' => 'freefire',
        'freefire' => 'freefire',
        'mobile_legends' => 'mobilelegend',
        'mobile_legends_bang_bang' => 'mobilelegend',
        'mobilelegend' => 'mobilelegend',
        'mlbb' => 'mobilelegend',
        'pubg_mobile' => 'pubgm',
        'pubgm' => 'pubgm',
        'genshin_impact' => 'genshin',
        'genshin' => 'genshin',
        'valorant' => 'valorant',
        'honkai_star_rail' => 'honkai_star_rail',
        'honkaistarrail' => 'honkai_star_rail',
        'clash_of_clans' => 'coc',
        'coc' => 'coc',
        'call_of_duty' => 'codm',
        'call_of_duty_mobile' => 'codm',
        'codm' => 'codm',
    ];

    /** Kode negara (ISO-2) -> nama tampil untuk badge region. */
    private const GOPAY_COUNTRY_NAMES = [
        // Asia Tenggara
        'ID' => 'Indonesia',
        'MY' => 'Malaysia',
        'SG' => 'Singapore',
        'PH' => 'Philippines',
        'TH' => 'Thailand',
        'VN' => 'Vietnam',
        'KH' => 'Cambodia',
        'LA' => 'Laos',
        'MM' => 'Myanmar',
        'BN' => 'Brunei',
        'TL' => 'Timor-Leste',
        // Asia lain
        'IN' => 'India',
        'BD' => 'Bangladesh',
        'PK' => 'Pakistan',
        'NP' => 'Nepal',
        'LK' => 'Sri Lanka',
        'TW' => 'Taiwan',
        'HK' => 'Hong Kong',
        'MO' => 'Macau',
        'JP' => 'Japan',
        'KR' => 'South Korea',
        'CN' => 'China',
        'MN' => 'Mongolia',
        // Timur Tengah
        'TR' => 'Turkey',
        'SA' => 'Saudi Arabia',
        'AE' => 'United Arab Emirates',
        'QA' => 'Qatar',
        'KW' => 'Kuwait',
        'IQ' => 'Iraq',
        'EG' => 'Egypt',
        // Amerika
        'US' => 'United States',
        'BR' => 'Brazil',
        'MX' => 'Mexico',
        'AR' => 'Argentina',
        'CO' => 'Colombia',
        'PE' => 'Peru',
        'CL' => 'Chile',
        // Lainnya
        'RU' => 'Russia',
        'AU' => 'Australia',
        'NZ' => 'New Zealand',
        'GB' => 'United Kingdom',
    ];

    public function check($user_id = null, $zone_id = null, $game = null): array
    {
        if (! $user_id || ! $game) {
            Log::warning('ApiCheckController:check - Validation failed: User ID or Game is missing.');

            return ['status' => ['code' => 400, 'message' => 'User ID and Game are required.']];
        }

        $parsedGame = $this->normalizeGameKey($game);

        // 1. Cek DB persistent cache terlebih dahulu — tidak perlu hit API sama sekali.
        $dbCached = $this->lookupDbCache($parsedGame, (string) $user_id, $zone_id);

        if ($dbCached !== null) {
            Log::debug('ApiCheckController:check - Returning from DB cache.', [
                'game'    => $parsedGame,
                'user_id' => $user_id,
                'zone_id' => $zone_id,
                'source'  => $dbCached->source,
            ]);

            $data = [
                'status' => ['code' => 200, 'message' => 'User found'],
                'data'   => [
                    'user_id'  => $user_id,
                    'username' => $dbCached->nickname,
                ],
            ];

            if (! empty($zone_id)) {
                $data['data']['zone_id'] = $zone_id;
            }

            // Region akun tersimpan (mis. dari GoPay) ikut dikembalikan dari cache,
            // supaya badge region tetap tampil tanpa perlu hit provider lagi.
            $cachedCountry = strtoupper(trim((string) ($dbCached->country_origin ?? '')));

            // Backfill: akun yang di-cache sebelum fitur region ada belum punya
            // country_origin. Ambil sekali dari GoPay (bila aktif + game terpetakan),
            // simpan, lalu pakai. Gagal = diam-diam lanjut tanpa region.
            if ($cachedCountry === '') {
                $backfillRegion = $this->lookupRegionOnly($parsedGame, (string) $user_id, $zone_id ? (string) $zone_id : null);
                if (is_array($backfillRegion)) {
                    $cachedCountry = strtoupper(trim((string) ($backfillRegion['code'] ?? '')));
                    if ($cachedCountry !== '') {
                        $this->saveToDbCache($parsedGame, (string) $user_id, $zone_id, (string) $dbCached->nickname, (string) $dbCached->source, $cachedCountry);
                    }
                }
            }

            if ($cachedCountry !== '') {
                $data['data']['account_region'] = [
                    'code'     => $cachedCountry,
                    'reported' => self::GOPAY_COUNTRY_NAMES[$cachedCountry] ?? $cachedCountry,
                    'provider' => $dbCached->source ?: 'cache',
                ];
            }

            return $data;
        }

        // 2. Short-term Laravel cache (10 menit) untuk deduplicate request bersamaan.
        // Versi key baru mengisolasi hasil dari cache inquiry Digiflazz lama.
        $cacheKey = "check_id_v2_{$parsedGame}_{$user_id}_{$zone_id}";

        $result = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($parsedGame, $user_id, $zone_id) {
            // GoPay Games checker (opt-in) — sumber region (countryOrigin). Cepat
            // gagal bila nonaktif atau game tidak terpetakan, lalu lanjut ke chain.
            $goPayResult = $this->connectGoPay($parsedGame, (string) $user_id, $zone_id ? (string) $zone_id : null);

            if ($this->isSuccessfulResult($goPayResult)) {
                return $goPayResult;
            }

            // VCGamers checker (opt-in) — cadangan GoPay, juga melaporkan region.
            $vcgResult = $this->connectVcgamers($parsedGame, (string) $user_id, $zone_id ? (string) $zone_id : null);

            if ($this->isSuccessfulResult($vcgResult)) {
                return $vcgResult;
            }

            // Self-hosted API is the preferred checker. It owns the live provider
            // metadata and avoids waiting on unrelated external APIs first.
            if ($this->selfHostedCircuitOpen($parsedGame)) {
                $selfHostedResult = $this->failedResult('Self-hosted check ID circuit is cooling down.', true);
            } else {
                $selfHostedResult = $this->connectSelfHosted($parsedGame, (string) $user_id, $zone_id ? (string) $zone_id : null);
                $this->recordSelfHostedCircuit($parsedGame, $selfHostedResult);
            }

            if ($this->isSuccessfulResult($selfHostedResult)) {
                return $selfHostedResult;
            }

            // Primary free API
            $primaryResult = $this->connectPrimary([
                'game'    => $parsedGame,
                'user_id' => (string) $user_id,
                'zone_id' => $zone_id,
            ]);

            if ($this->isSuccessfulResult($primaryResult)) {
                return $primaryResult;
            }

            // Velixs API
            $velixsResult = $this->connectVelixs($parsedGame, (string) $user_id);

            if ($this->isSuccessfulResult($velixsResult)) {
                return $velixsResult;
            }

            // ApiGames API (hanya game yang didukung)
            $apiGamesResult = $this->supportsApiGamesGame($parsedGame)
                ? $this->connectApiGames($parsedGame, (string) $user_id, $zone_id ? (string) $zone_id : null)
                : $this->failedResult("ApiGames does not support game: {$parsedGame}.");

            if ($this->isSuccessfulResult($apiGamesResult)) {
                return $apiGamesResult;
            }

            Log::warning('ApiCheckController:check - All providers failed.', [
                'primary_response'    => $primaryResult,
                'velixs_response'     => $velixsResult,
                'apigames_response'   => $apiGamesResult,
                'selfhosted_response' => $selfHostedResult,
            ]);

            $failedProvider = $selfHostedResult['message'] !== null
                ? $selfHostedResult
                : ($apiGamesResult['message'] !== null
                    ? $apiGamesResult
                    : ($velixsResult['message'] !== null ? $velixsResult : $primaryResult));

            return $this->failedResult(
                $failedProvider['message'] ?? 'User not found.',
                ($failedProvider['unavailable'] ?? false) === true,
            );
        });

        // Jangan menyimpan kegagalan provider; request berikutnya harus bisa mencoba
        // lagi setelah circuit cooldown, bukan menerima error lama 10 menit.
        if (($result['unavailable'] ?? false) === true) {
            Cache::forget($cacheKey);
        }

        if ($this->isSuccessfulResult($result)) {
            $nickname = $result['nickname'] ?? null;
            $source   = $result['source'] ?? 'unknown';
            $accountRegion = is_array($result['account_region'] ?? null)
                ? $result['account_region']
                : null;

            $regionCode = is_array($accountRegion)
                ? strtoupper(trim((string) ($accountRegion['code'] ?? '')))
                : '';
            $this->saveToDbCache($parsedGame, (string) $user_id, $zone_id, $nickname, $source, $regionCode !== '' ? $regionCode : null);

            $data = [
                'status' => ['code' => 200, 'message' => 'User found'],
                'data'   => [
                    'user_id'  => $user_id,
                    'username' => $nickname,
                ],
            ];

            if (! empty($zone_id)) {
                $data['data']['zone_id'] = $zone_id;
            }

            // Sinyal region akun (best-effort, hanya kalau provider melaporkan).
            // Dipakai storefront untuk memblokir order ID-only (mis. Digiflazz)
            // saat akun terdeteksi di luar Indonesia.
            if ($accountRegion !== null) {
                $data['data']['account_region'] = $accountRegion;
            }

            return $data;
        }

        $errorMessage = $result['message'] ?? 'User not found or invalid.';

        Log::error('ApiCheckController:check - Failed to find user.', [
            'error'        => $errorMessage,
            'api_response' => $result,
        ]);

        Cache::forget($cacheKey);

        return [
            'status'     => ['code' => 404, 'message' => $errorMessage],
            'unavailable' => ($result['unavailable'] ?? false) === true,
            'message'     => $errorMessage,
        ];
    }

    /**
     * Cek persistent DB cache untuk user ID yang sudah pernah diverifikasi.
     */
    private function lookupDbCache(string $game, string $userId, ?string $zoneId): ?VerifiedGameAccount
    {
        // Guard: skip DB lookup jika tabel belum ada (misal saat unit test tanpa migrate)
        if (! Schema::hasTable('verified_game_accounts')) {
            return null;
        }

        return VerifiedGameAccount::where('game', $game)
            ->where('user_id', $userId)
            ->where('zone_id', $zoneId ?? '')
            ->first();
    }

    /**
     * Simpan hasil validasi yang sukses ke tabel DB persistent cache.
     * Menggunakan updateOrInsert agar idempotent.
     */
    private function saveToDbCache(string $game, string $userId, ?string $zoneId, ?string $nickname, string $source, ?string $countryOrigin = null): void
    {
        if (! $nickname || ! Schema::hasTable('verified_game_accounts')) {
            return;
        }

        try {
            $values = [
                'nickname' => $nickname,
                'source'   => $source,
            ];

            // Simpan region hanya bila provider melaporkannya. Jangan menimpa
            // region tersimpan dengan null saat provider lain (tanpa region) menang.
            if ($countryOrigin !== null && $countryOrigin !== '' && Schema::hasColumn('verified_game_accounts', 'country_origin')) {
                $values['country_origin'] = $countryOrigin;
            }

            VerifiedGameAccount::updateOrCreate(
                [
                    'game'    => $game,
                    'user_id' => $userId,
                    'zone_id' => $zoneId ?? '',
                ],
                $values
            );
        } catch (\Throwable $e) {
            Log::warning('ApiCheckController:saveToDbCache - Failed to save.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function selfHostedCircuitKey(string $game): string
    {
        return 'check_id_circuit:selfhosted:' . md5($game);
    }

    private function selfHostedCircuitOpen(string $game): bool
    {
        return Cache::has($this->selfHostedCircuitKey($game) . ':open');
    }

    private function recordSelfHostedCircuit(string $game, array $result): void
    {
        $key = $this->selfHostedCircuitKey($game);
        $hardFailure = ($result['unavailable'] ?? false) === true;

        if (! $hardFailure) {
            Cache::forget($key . ':failures');
            Cache::forget($key . ':open');
            return;
        }

        $failures = (int) Cache::increment($key . ':failures');
        Cache::put($key . ':failures', $failures, now()->addMinutes(2));

        if ($failures >= 2) {
            Cache::put($key . ':open', true, now()->addSeconds(30));
        }
    }

    /**
     * GoPay Games user-account checker. Mengembalikan nickname + region
     * (countryOrigin). Opt-in via config; endpoint tidak resmi, jadi semua
     * kegagalan dikembalikan sebagai failedResult (biar chain lanjut).
     */
    private function connectGoPay(string $parsedGame, string $userId, ?string $zoneId): array
    {
        if (! config('providers.check_id.gopay.enabled', false)) {
            return $this->failedResult('GoPay check ID disabled.');
        }

        $codes = (array) config('providers.check_id.gopay.game_codes', []);
        $gameCode = $codes[$parsedGame] ?? null;

        if (! is_string($gameCode) || $gameCode === '') {
            return $this->failedResult('GoPay does not map game: ' . $parsedGame . '.');
        }

        $url = trim((string) config('providers.check_id.gopay.base_url', 'https://gopay.co.id/games/v1/order/user-account'));

        if ($url === '') {
            return $this->failedResult('GoPay base URL is not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('providers.check_id.gopay.connect_timeout', 3))
                ->timeout((int) config('providers.check_id.gopay.timeout', 6))
                ->withHeaders([
                    'X-Client'        => 'web-desktop',
                    'X-Timestamp'     => (string) (int) round(microtime(true) * 1000),
                    'x-captcha-token' => '',
                    'Origin'          => 'https://gopay.co.id',
                    'Referer'         => 'https://gopay.co.id/games',
                ])
                ->post($url, [
                    'code' => $gameCode,
                    'data' => [
                        'userId' => $userId,
                        'zoneId' => $zoneId ?? '',
                    ],
                ]);

            if (! $response->successful()) {
                return $this->failedResult('GoPay returned a non-200 response.');
            }

            return $this->normalizeGoPayResponse($response->json());
        } catch (\Throwable $exception) {
            Log::error('ApiCheckController:connectGoPay - Request failed.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->failedResult('GoPay request failed.');
        }
    }

    private function normalizeGoPayResponse(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return $this->failedResult('GoPay returned an invalid payload.');
        }

        $nickname = trim((string) ($decoded['data']['username'] ?? ''));

        if ($nickname === '') {
            return $this->failedResult((string) ($decoded['message'] ?? 'User not found on GoPay.'));
        }

        $country = strtoupper(trim((string) ($decoded['data']['countryOrigin'] ?? '')));
        $region = null;

        if ($country !== '') {
            $region = $this->normalizeAccountRegion([
                'code'     => $country,
                'reported' => self::GOPAY_COUNTRY_NAMES[$country] ?? $country,
                'provider' => 'gopay',
            ]);
        }

        return $this->successfulResult($nickname, 'gopay', $region);
    }

    /**
     * VCGamers product check-id. Mengembalikan nickname (data.result) + region
     * (data.region). Opt-in via config; semua kegagalan -> failedResult.
     */
    private function connectVcgamers(string $parsedGame, string $userId, ?string $zoneId): array
    {
        if (! config('providers.check_id.vcgamers.enabled', false)) {
            return $this->failedResult('VCGamers check ID disabled.');
        }

        $games = (array) config('providers.check_id.vcgamers.games', []);
        $game = $games[$parsedGame] ?? null;

        if (! is_array($game) || empty($game['provider_code'])) {
            return $this->failedResult('VCGamers does not map game: ' . $parsedGame . '.');
        }

        $url = trim((string) config('providers.check_id.vcgamers.base_url', 'https://apis.vcgamers.com/api/product/check-id'));

        if ($url === '') {
            return $this->failedResult('VCGamers base URL is not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('providers.check_id.vcgamers.connect_timeout', 3))
                ->timeout((int) config('providers.check_id.vcgamers.timeout', 6))
                ->post($url, [
                    'check_provider' => 'rapidapi',
                    'provider_code'  => (string) $game['provider_code'],
                    'brand_id'       => (string) ($game['brand_id'] ?? ''),
                    'category_id'    => (string) ($game['category_id'] ?? ''),
                    'values'         => [
                        ['key' => 'user_id', 'value' => $userId],
                        ['key' => 'zone_id', 'value' => $zoneId ?? ''],
                    ],
                ]);

            if (! $response->successful()) {
                return $this->failedResult('VCGamers returned a non-200 response.');
            }

            return $this->normalizeVcgamersRegionResponse($response->json());
        } catch (\Throwable $exception) {
            Log::error('ApiCheckController:connectVcgamers - Request failed.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->failedResult('VCGamers request failed.');
        }
    }

    private function normalizeVcgamersRegionResponse(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return $this->failedResult('VCGamers returned an invalid payload.');
        }

        $success = ($decoded['code'] ?? null) === 200
            && ($decoded['status'] ?? null) === 'success'
            && ($decoded['data']['status'] ?? null) === true;

        $nickname = trim((string) ($decoded['data']['result'] ?? ''));

        if (! $success || $nickname === '') {
            return $this->failedResult((string) ($decoded['message'] ?? 'User not found on VCGamers.'));
        }

        $country = strtoupper(trim((string) ($decoded['data']['region'] ?? '')));
        $region = null;

        if ($country !== '') {
            $region = $this->normalizeAccountRegion([
                'code'     => $country,
                'reported' => self::GOPAY_COUNTRY_NAMES[$country] ?? $country,
                'provider' => 'vcgamers',
            ]);
        }

        return $this->successfulResult($nickname, 'vcgamers', $region);
    }

    /**
     * Ambil region saja (best-effort) dari provider yang melaporkannya, berurut.
     * Dipakai untuk backfill akun yang sudah tercache tanpa region.
     */
    private function lookupRegionOnly(string $parsedGame, string $userId, ?string $zoneId): ?array
    {
        foreach (['connectGoPay', 'connectVcgamers'] as $method) {
            $result = $this->{$method}($parsedGame, $userId, $zoneId);
            if ($this->isSuccessfulResult($result) && is_array($result['account_region'] ?? null)) {
                return $result['account_region'];
            }
        }

        return null;
    }

    private function connectPrimary(array $params): array
    {
        $url = trim((string) config('providers.check_id.primary_url', 'https://api-cek-id-game-ten.vercel.app/api/check-id-game'));

        if ($url === '') {
            $url = 'https://api-cek-id-game-ten.vercel.app/api/check-id-game';
        }

        $payload = [
            'type_name' => $params['game'],
            'userId'    => $params['user_id'],
            'zoneId'    => $params['zone_id'] ?? '',
        ];

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->post($url, $payload);

            if (! $response->successful()) {
                return $this->failedResult('Primary API returned a non-200 response.');
            }

            return $this->normalizePrimaryResponse($response->json());
        } catch (\Throwable $exception) {
            Log::error('ApiCheckController:connectPrimary - Request failed.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->failedResult('Primary API request failed.');
        }
    }

    private function connectVelixs(string $parsedGame, string $userId): array
    {
        $apiKey = trim((string) env('VELIXS_API_KEY', ''));

        if (! $apiKey) {
            Log::warning('ApiCheckController:connectVelixs - VELIXS_API_KEY is not set in .env');

            return $this->failedResult('Velixs API key is not configured.');
        }

        $velixsGame = self::VELIXS_GAME_CODES[$parsedGame] ?? $parsedGame;
        $url = 'https://api.velixs.com/idgames-checker';

        try {
            $response = Http::acceptJson()
                ->connectTimeout(4)
                ->timeout(8)
                ->post($url, [
                    'game'   => $velixsGame,
                    'id'     => $userId,
                    'apikey' => $apiKey,
                ]);

            if (! $response->successful()) {
                return $this->failedResult('Velixs returned a non-200 response.');
            }

            return $this->normalizeVelixsResponse($response->json());
        } catch (\Throwable $exception) {
            Log::error('ApiCheckController:connectVelixs - Request failed.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->failedResult('Velixs request failed.');
        }
    }

    private function connectApiGames(string $parsedGame, string $userId, ?string $zoneId = null): array
    {
        $gameCode = self::APIGAMES_GAME_CODES[$parsedGame] ?? null;

        $settings = SettingWeb::query()
            ->select('apigames_merchant', 'apigames_secret')
            ->find(1);

        $merchantId = (string) ($settings->apigames_merchant ?? '');
        $secretKey  = (string) ($settings->apigames_secret ?? '');

        if ($merchantId === '' || $secretKey === '') {
            Log::warning('ApiCheckController:connectApiGames - ApiGames credentials are missing.');

            return $this->failedResult('ApiGames credentials are not configured.');
        }

        $signature     = md5($merchantId . $secretKey);
        $url           = "https://v1.apigames.id/merchant/{$merchantId}/cek-username/{$gameCode}";
        $apiGamesUserId = $this->buildApiGamesUserId($gameCode, $userId, $zoneId);

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->get($url, [
                    'user_id'   => $apiGamesUserId,
                    'signature' => $signature,
                ]);

            if (! $response->successful()) {
                return $this->failedResult('ApiGames returned a non-200 response.');
            }

            return $this->normalizeApiGamesResponse($response->json());
        } catch (\Throwable $exception) {
            Log::error('ApiCheckController:connectApiGames - Request failed.', [
                'message'   => $exception->getMessage(),
                'game_code' => $gameCode,
            ]);

            return $this->failedResult('ApiGames request failed.');
        }
    }

    private function connectSelfHosted(string $parsedGame, string $userId, ?string $zoneId = null): array
    {
        if (! (bool) config('providers.check_id.selfhosted.enabled', false)) {
            return $this->failedResult('Self-hosted check ID API is disabled.');
        }

        $baseUrl = $this->resolveSelfHostedBaseUrl();
        $apiKey = trim((string) config('providers.check_id.selfhosted.api_key', ''));

        if ($baseUrl === null) {
            return $this->failedResult('Self-hosted check ID API URL is not configured.');
        }

        if ($apiKey === '') {
            return $this->failedResult('Self-hosted check ID API key is not configured.');
        }

        // Sesuai OpenAPI Spec: GET /api/check dengan parameter query: slug, id, zone, fallback, cache
        $slug = str_replace('_', '-', $parsedGame);
        $url = $baseUrl . '/api/check';

        try {
            $response = Http::acceptJson()
                ->withHeaders(['x-api-key' => $apiKey])
                ->connectTimeout((int) config('providers.check_id.selfhosted.connect_timeout', 3))
                ->timeout((int) config('providers.check_id.selfhosted.timeout', 5))
                ->get($url, [
                    'slug' => $slug,
                    'id' => $userId,
                    'zone' => $zoneId ?? '',
                    'fallback' => '1',
                    'cache' => '1',
                ]);

            if (! $response->successful()) {
                $status = $response->status();
                $payload = $response->json();
                $providerMessage = is_array($payload)
                    ? trim((string) ($payload['message'] ?? ''))
                    : '';

                // Only a structured 400/404 from cekid is a completed validation
                // result (for example an invalid ID or a missing zone). A malformed
                // response, auth/rate-limit result, or server error is an outage.
                $completedValidation = in_array($status, [400, 404], true)
                    && $providerMessage !== '';

                return $this->failedResult(
                    $completedValidation
                        ? $providerMessage
                        : 'Self-hosted check ID API returned a non-200 response.',
                    ! $completedValidation,
                );
            }

            return $this->normalizeSelfHostedResponse($response->json());
        } catch (\Throwable $exception) {
            Log::error('ApiCheckController:connectSelfHosted - Request failed.', [
                'message' => $exception->getMessage(),
                'host' => parse_url($baseUrl, PHP_URL_HOST),
                'game' => $slug,
            ]);

            return $this->failedResult('Self-hosted check ID API request failed.', true);
        }
    }

    private function resolveSelfHostedBaseUrl(): ?string
    {
        $baseUrl = trim((string) config('providers.check_id.selfhosted.base_url', ''));

        if ($baseUrl === '') {
            return null;
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $host = parse_url($baseUrl, PHP_URL_HOST);

        if ($scheme !== 'https' || ! is_string($host) || trim($host) === '') {
            Log::warning('ApiCheckController:resolveSelfHostedBaseUrl - Self-hosted check ID API must use HTTPS production URL.', [
                'scheme' => $scheme,
                'host' => $host,
            ]);

            return null;
        }

        $normalizedHost = strtolower(trim($host, '[]'));
        if (in_array($normalizedHost, ['localhost', '127.0.0.1', '::1'], true)) {
            Log::warning('ApiCheckController:resolveSelfHostedBaseUrl - Local self-hosted check ID URL is not allowed.', [
                'host' => $host,
            ]);

            return null;
        }

        return rtrim($baseUrl, '/');
    }

    private function normalizePrimaryResponse(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return $this->failedResult('Primary API returned an invalid payload.');
        }

        $nickname = trim((string) (
            $decoded['nickname']
            ?? $decoded['username']
            ?? $decoded['data']['username']
            ?? ''
        ));

        $isSuccess = ($decoded['status'] ?? null) === true
            || (($decoded['status']['code'] ?? null) === 200 && $nickname !== '');

        if ($isSuccess && $nickname !== '') {
            return $this->successfulResult($nickname, 'primary');
        }

        return $this->failedResult($decoded['message'] ?? 'User not found on primary API.');
    }

    private function normalizeVelixsResponse(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return $this->failedResult('Velixs returned an invalid payload.');
        }

        $nickname = trim((string) ($decoded['data']['username'] ?? ''));

        if (($decoded['status'] ?? null) === true && $nickname !== '') {
            return $this->successfulResult($nickname, 'velixs');
        }

        return $this->failedResult($decoded['message'] ?? 'User not found on Velixs.');
    }

    private function normalizeApiGamesResponse(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return $this->failedResult('ApiGames returned an invalid payload.');
        }

        $isValid  = (bool) ($decoded['data']['is_valid'] ?? false);
        $nickname = trim((string) ($decoded['data']['username'] ?? ''));

        if ($isValid && $nickname !== '') {
            return $this->successfulResult($nickname, 'apigames');
        }

        return $this->failedResult($decoded['message'] ?? 'User not found on ApiGames.');
    }

    private function normalizeSelfHostedResponse(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return $this->failedResult('Self-hosted check ID API returned an invalid payload.');
        }

        $nickname = trim((string) (
            $decoded['data']['username']
            ?? $decoded['data']['nickname']
            ?? $decoded['username']
            ?? $decoded['nickname']
            ?? ''
        ));

        $isSuccess = ($decoded['status'] ?? null) === true
            || (($decoded['status']['code'] ?? null) === 200 && $nickname !== '')
            || (($decoded['code'] ?? null) === 200 && $nickname !== '');

        if ($isSuccess && $nickname !== '') {
            $provider = trim((string) ($decoded['provider'] ?? ''));
            $source = $provider !== '' ? 'selfhosted:' . $provider : 'selfhosted';

            return $this->successfulResult(
                $nickname,
                $source,
                $this->normalizeAccountRegion($decoded['data']['account_region'] ?? null),
            );
        }

        return $this->failedResult($decoded['message'] ?? 'User not found on self-hosted check ID API.');
    }

    private function successfulResult(string $nickname, string $source, ?array $accountRegion = null): array
    {
        $result = [
            'status'   => true,
            'nickname' => $nickname,
            'source'   => $source,
        ];

        if (is_array($accountRegion) && ! empty($accountRegion['code'])) {
            $result['account_region'] = $accountRegion;
        }

        return $result;
    }

    /**
     * Normalisasi sinyal region akun dari cekid selfhosted.
     * Bentuk: { code: "MY", reported: "Malaysia", provider: "codashop" }.
     * Provider yang tidak melaporkan → null (jangan mengarang region).
     */
    private function normalizeAccountRegion(mixed $region): ?array
    {
        if (! is_array($region)) {
            return null;
        }

        $code = strtoupper(trim((string) ($region['code'] ?? '')));
        if ($code === '') {
            return null;
        }

        $reported = trim((string) ($region['reported'] ?? ''));
        $provider = trim((string) ($region['provider'] ?? ''));

        return [
            'code'     => $code,
            'reported' => $reported !== '' ? $reported : null,
            'provider' => $provider !== '' ? $provider : null,
        ];
    }

    private function failedResult(string $message, bool $unavailable = false): array
    {
        return [
            'status'  => false,
            'message' => $message,
            'unavailable' => $unavailable,
        ];
    }

    private function isSuccessfulResult(?array $result): bool
    {
        return is_array($result)
            && ($result['status'] ?? false) === true
            && trim((string) ($result['nickname'] ?? '')) !== '';
    }

    private function normalizeGameKey(string $game): string
    {
        return strtolower(str_replace([' ', '-'], '_', trim($game)));
    }

    private function supportsApiGamesGame(string $parsedGame): bool
    {
        return array_key_exists($parsedGame, self::APIGAMES_GAME_CODES);
    }

    private function buildApiGamesUserId(string $gameCode, string $userId, ?string $zoneId): string
    {
        if ($gameCode === 'mobilelegend' && $zoneId !== null && $zoneId !== '') {
            return $userId . $zoneId;
        }

        return $userId;
    }
}

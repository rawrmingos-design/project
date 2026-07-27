<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\VerifiedGameAccount;
use App\Models\SettingWeb;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ApiCheckController extends Controller
{
    public function __construct(private ?Layanan $layananContext = null)
    {
    }

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

    /**
     * Mapping from normalized game key to a Digiflazz product SKU
     * that supports inquiry (cek-tagihan / check via Digiflazz API).
     *
     * The values here are INQUIRY/CHECK SKUs from Digiflazz — these must be
     * set to valid inquiry SKUs in the Digiflazz product catalog.
     * Configure this in config/digiflazz_inquiry.php or directly here.
     * Leave null to skip fallback for that game.
     */
    protected const DIGIFLAZZ_INQUIRY_SKUS = [
        'mobilelegend' => null, // e.g. 'CML5' if Digiflazz provides MLBB inquiry SKU
        'freefire'     => null, // e.g. 'CFF1'
        'pubgm'        => null, // e.g. 'CPUBG1'
    ];

    public function check($user_id = null, $zone_id = null, $game = null): array
    {
        if (! $user_id || ! $game) {
            Log::warning('ApiCheckController:check - Validation failed: User ID or Game is missing.');

            return ['status' => ['code' => 400, 'message' => 'User ID and Game are required.']];
        }

        $parsedGame = $this->normalizeGameKey($game);
        $digiflazzInquirySku = $this->resolveDigiflazzInquirySku($parsedGame);

        // 1. Cek DB persistent cache terlebih dahulu — tidak perlu hit API sama sekali.
        // Dynamic inquiry SKU skip persistent cache agar hasil tidak tercampur antar SKU.
        $dbCached = $digiflazzInquirySku === null
            ? $this->lookupDbCache($parsedGame, (string) $user_id, $zone_id)
            : null;

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

            return $data;
        }

        // 2. Short-term Laravel cache (10 menit) untuk deduplicate request bersamaan
        $routeHash = $digiflazzInquirySku !== null ? md5('digiflazz:' . $digiflazzInquirySku) : 'legacy';
        $cacheKey = "check_id_{$parsedGame}_{$user_id}_{$zone_id}_{$routeHash}";

        $result = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($parsedGame, $user_id, $zone_id, $digiflazzInquirySku) {
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

            // Self-hosted API fallback — setelah ApiGames, sebelum Digiflazz
            $selfHostedResult = $this->connectSelfHosted($parsedGame, (string) $user_id, $zone_id ? (string) $zone_id : null);

            if ($this->isSuccessfulResult($selfHostedResult)) {
                return $selfHostedResult;
            }

            // 3. Digiflazz fallback — kalo semua API gratisan gagal
            $digiflazzResult = $this->connectDigiflazz($parsedGame, (string) $user_id, $zone_id ? (string) $zone_id : null, $digiflazzInquirySku);

            if ($this->isSuccessfulResult($digiflazzResult)) {
                return $digiflazzResult;
            }

            Log::warning('ApiCheckController:check - All providers failed.', [
                'primary_response'     => $primaryResult,
                'velixs_response'      => $velixsResult,
                'apigames_response'    => $apiGamesResult,
                'selfhosted_response'  => $selfHostedResult,
                'digiflazz_response'   => $digiflazzResult,
            ]);

            return $this->failedResult(
                $digiflazzResult['message']
                    ?? $selfHostedResult['message']
                    ?? $apiGamesResult['message']
                    ?? $velixsResult['message']
                    ?? $primaryResult['message']
                    ?? 'User not found.'
            );
        });

        if ($this->isSuccessfulResult($result)) {
            $nickname = $result['nickname'] ?? null;
            $source   = $result['source'] ?? 'unknown';

            // 4. Simpan ke DB persistent cache hanya untuk route legacy agar SKU inquiry dinamis tidak tercampur.
            if ($digiflazzInquirySku === null) {
                $this->saveToDbCache($parsedGame, (string) $user_id, $zone_id, $nickname, $source);
            }

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

            return $data;
        }

        $errorMessage = $result['message'] ?? 'User not found or invalid.';

        Log::error('ApiCheckController:check - Failed to find user.', [
            'error'        => $errorMessage,
            'api_response' => $result,
        ]);

        Cache::forget($cacheKey);

        return ['status' => ['code' => 404, 'message' => $errorMessage]];
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
    private function saveToDbCache(string $game, string $userId, ?string $zoneId, ?string $nickname, string $source): void
    {
        if (! $nickname || ! Schema::hasTable('verified_game_accounts')) {
            return;
        }

        try {
            VerifiedGameAccount::updateOrCreate(
                [
                    'game'    => $game,
                    'user_id' => $userId,
                    'zone_id' => $zoneId ?? '',
                ],
                [
                    'nickname' => $nickname,
                    'source'   => $source,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('ApiCheckController:saveToDbCache - Failed to save.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resolveDigiflazzInquirySku(string $parsedGame): ?string
    {
        if (
            $this->layananContext
            && (bool) ($this->layananContext->check_id_enabled ?? false)
            && strtolower(trim((string) ($this->layananContext->check_id_provider ?? ''))) === 'digiflazz'
        ) {
            $sku = trim((string) ($this->layananContext->check_id_provider_sku ?? ''));

            if ($sku !== '') {
                return $sku;
            }
        }

        $legacySku = static::DIGIFLAZZ_INQUIRY_SKUS[$parsedGame] ?? null;
        $legacySku = is_string($legacySku) ? trim($legacySku) : null;

        return $legacySku !== '' ? $legacySku : null;
    }

    /**
     * Fallback ke Digiflazz inquiry.
     * Digiflazz mendukung inquiry via endpoint /v1/transaction dengan command inquiry-pasca
     * atau via SKU khusus yang memberikan response customer_name.
     *
     * SKU untuk setiap game harus dikonfigurasi di DIGIFLAZZ_INQUIRY_SKUS.
     * Jika SKU tidak tersedia, fallback ini akan skip dengan gracefully.
     */
    private function connectDigiflazz(string $parsedGame, string $userId, ?string $zoneId = null, ?string $configuredSku = null): array
    {
        $sku = $configuredSku ?: (static::DIGIFLAZZ_INQUIRY_SKUS[$parsedGame] ?? null);

        if ($sku === null) {
            return $this->failedResult("No Digiflazz inquiry SKU configured for game: {$parsedGame}.");
        }

        $api = DB::table('setting_webs')->where('id', 1)->first();

        if (! $api) {
            return $this->failedResult('Digiflazz credentials not found.');
        }

        $username = trim((string) ($api->username_digi ?? ''));
        $apiKey   = trim((string) ($api->api_key_digi ?? ''));

        if ($username === '' || $apiKey === '') {
            return $this->failedResult('Digiflazz credentials are not configured.');
        }

        // Digiflazz pakai ref_id unik — gunakan kombinasi game+uid+zone agar deduplicate
        $refId    = 'CHK-' . md5("{$parsedGame}_{$userId}_{$zoneId}");
        $target   = $userId . ($zoneId ?? '');
        $sign     = md5($username . $apiKey . $refId);

        $payload = [
            'username'        => $username,
            'buyer_sku_code'  => $sku,
            'customer_no'     => $target,
            'ref_id'          => $refId,
            'sign'            => $sign,
            'testing'         => app()->environment('local'),
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->connectTimeout(5)
                ->timeout(10)
                ->post('https://api.digiflazz.com/v1/transaction', $payload);

            if (! $response->successful()) {
                return $this->failedResult('Digiflazz returned a non-200 response.');
            }

            return $this->normalizeDigiflazzResponse($response->json());
        } catch (\Throwable $exception) {
            Log::error('ApiCheckController:connectDigiflazz - Request failed.', [
                'message'    => $exception->getMessage(),
                'game'       => $parsedGame,
                'parsed_sku' => $sku,
            ]);

            return $this->failedResult('Digiflazz inquiry request failed.');
        }
    }

    /**
     * Normalisasi respons Digiflazz inquiry ke format internal.
     * Digiflazz mengembalikan customer_name sebagai nickname game.
     */
    private function normalizeDigiflazzResponse(mixed $decoded): array
    {
        if (! is_array($decoded)) {
            return $this->failedResult('Digiflazz returned an invalid payload.');
        }

        $data     = $decoded['data'] ?? [];
        $status   = strtolower((string) ($data['status'] ?? ''));
        $nickname = trim((string) ($data['customer_name'] ?? ''));

        // Digiflazz inquiry success: status "Sukses" atau "Pending" dengan customer_name terisi
        if (in_array($status, ['sukses', 'pending'], true) && $nickname !== '') {
            return $this->successfulResult($nickname, 'digiflazz');
        }

        return $this->failedResult($data['message'] ?? $decoded['message'] ?? 'User not found on Digiflazz.');
    }

    private function connectPrimary(array $params): array
    {
        $defaultUrl = 'https://api-cek-id-game-ten.vercel.app/api/check-id-game';
        $url = trim((string) config('providers.check_id.primary_url', $defaultUrl));

        if ($url === '') {
            $url = $defaultUrl;
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
        $apiKey = trim((string) (env('VELIXS_API_KEY') ?: 'b3f0bdc7ce3c73ab6404d59bc6a0324f94690d8bfa430bd8cd'));

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
                return $this->failedResult('Self-hosted check ID API returned a non-200 response.');
            }

            return $this->normalizeSelfHostedResponse($response->json());
        } catch (\Throwable $exception) {
            Log::error('ApiCheckController:connectSelfHosted - Request failed.', [
                'message' => $exception->getMessage(),
                'host' => parse_url($baseUrl, PHP_URL_HOST),
                'game' => $slug,
            ]);

            return $this->failedResult('Self-hosted check ID API request failed.');
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

            return $this->successfulResult($nickname, $source);
        }

        return $this->failedResult($decoded['message'] ?? 'User not found on self-hosted check ID API.');
    }

    private function successfulResult(string $nickname, string $source): array
    {
        return [
            'status'   => true,
            'nickname' => $nickname,
            'source'   => $source,
        ];
    }

    private function failedResult(string $message): array
    {
        return [
            'status'  => false,
            'message' => $message,
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

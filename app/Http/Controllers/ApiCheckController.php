<?php

namespace App\Http\Controllers;

use App\Models\SettingWeb;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function check($user_id = null, $zone_id = null, $game = null): array
    {
        if (! $user_id || ! $game) {
            Log::warning('ApiCheckController:check - Validation failed: User ID or Game is missing.');

            return ['status' => ['code' => 400, 'message' => 'User ID and Game are required.']];
        }

        $parsedGame = $this->normalizeGameKey($game);
        $cacheKey = "check_id_{$parsedGame}_{$user_id}_{$zone_id}";

        $result = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($parsedGame, $user_id, $zone_id) {
            $primaryResult = $this->connectPrimary([
                'game' => $parsedGame,
                'user_id' => (string) $user_id,
                'zone_id' => $zone_id,
            ]);

            if ($this->isSuccessfulResult($primaryResult)) {
                return $primaryResult;
            }

            $velixsResult = $this->connectVelixs($parsedGame, (string) $user_id);

            if ($this->isSuccessfulResult($velixsResult)) {
                return $velixsResult;
            }

            if (! $this->supportsApiGamesGame($parsedGame)) {
                return $this->failedResult(
                    $velixsResult['message']
                        ?? $primaryResult['message']
                        ?? 'User not found.'
                );
            }

            $apiGamesResult = $this->connectApiGames($parsedGame, (string) $user_id, $zone_id ? (string) $zone_id : null);

            if ($this->isSuccessfulResult($apiGamesResult)) {
                return $apiGamesResult;
            }

            Log::warning('ApiCheckController:check - All providers failed.', [
                'primary_response' => $primaryResult,
                'velixs_response' => $velixsResult,
                'apigames_response' => $apiGamesResult,
            ]);

            return $this->failedResult(
                $apiGamesResult['message']
                    ?? $velixsResult['message']
                    ?? $primaryResult['message']
                    ?? 'User not found.'
            );
        });

        if ($this->isSuccessfulResult($result)) {
            $nickname = $result['nickname'] ?? null;

            $data = [
                'status' => ['code' => 200, 'message' => 'User found'],
                'data' => [
                    'user_id' => $user_id,
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
            'error' => $errorMessage,
            'api_response' => $result,
        ]);

        Cache::forget($cacheKey);

        return ['status' => ['code' => 404, 'message' => $errorMessage]];
    }

    private function connectPrimary(array $params): array
    {
        $url = 'https://api-cek-id-game-ten.vercel.app/api/check-id-game';
        $payload = [
            'type_name' => $params['game'],
            'userId' => $params['user_id'],
            'zoneId' => $params['zone_id'] ?? '',
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
                    'game' => $velixsGame,
                    'id' => $userId,
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
        $secretKey = (string) ($settings->apigames_secret ?? '');

        if ($merchantId === '' || $secretKey === '') {
            Log::warning('ApiCheckController:connectApiGames - ApiGames credentials are missing.');

            return $this->failedResult('ApiGames credentials are not configured.');
        }

        $signature = md5($merchantId . $secretKey);
        $url = "https://v1.apigames.id/merchant/{$merchantId}/cek-username/{$gameCode}";
        $apiGamesUserId = $this->buildApiGamesUserId($gameCode, $userId, $zoneId);

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->get($url, [
                    'user_id' => $apiGamesUserId,
                    'signature' => $signature,
                ]);

            if (! $response->successful()) {
                return $this->failedResult('ApiGames returned a non-200 response.');
            }

            return $this->normalizeApiGamesResponse($response->json());
        } catch (\Throwable $exception) {
            Log::error('ApiCheckController:connectApiGames - Request failed.', [
                'message' => $exception->getMessage(),
                'game_code' => $gameCode,
            ]);

            return $this->failedResult('ApiGames request failed.');
        }
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

        $isValid = (bool) ($decoded['data']['is_valid'] ?? false);
        $nickname = trim((string) ($decoded['data']['username'] ?? ''));

        if ($isValid && $nickname !== '') {
            return $this->successfulResult($nickname, 'apigames');
        }

        return $this->failedResult($decoded['message'] ?? 'User not found on ApiGames.');
    }

    private function successfulResult(string $nickname, string $source): array
    {
        return [
            'status' => true,
            'nickname' => $nickname,
            'source' => $source,
        ];
    }

    private function failedResult(string $message): array
    {
        return [
            'status' => false,
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

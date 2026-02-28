<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiCheckController extends Controller
{
    /**
     * Memeriksa ID game melalui API eksternal dengan Caching.
     * Jika API utama gagal/tidak ditemukan, akan fallback ke API velixs.com.
     *
     * @param string|null $user_id
     * @param string|null $zone_id
     * @param string|null $game
     * @return array
     */
    public function check($user_id = null, $zone_id = null, $game = null): array
    {
        Log::info('ApiCheckController:check - Start processing request.', ['user_id' => $user_id, 'zone_id' => $zone_id, 'game' => $game]);

        if (!$user_id || !$game) {
            Log::warning('ApiCheckController:check - Validation failed: User ID or Game is missing.');
            return ['status' => ['code' => 400, 'message' => 'User ID and Game are required.']];
        }

        $parsedGame = strtolower(str_replace([' ', '-'], '_', $game));
        Log::info('ApiCheckController:check - Parsed game name.', ['original' => $game, 'parsed' => $parsedGame]);

        // Buat Kunci Cache yang Unik
        $cacheKey = "check_id_{$parsedGame}_{$user_id}_{$zone_id}";

        // Gunakan Cache::remember - data tersimpan 10 menit
        $result = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($parsedGame, $user_id, $zone_id) {

            // --- API Utama ---
            $params = [
                'game'    => $parsedGame,
                'user_id' => $user_id,
                'zone_id' => $zone_id
            ];
            $primaryResult = $this->connectPrimary($params);

            // Jika API utama berhasil menemukan nickname, gunakan hasilnya
            if ($primaryResult && isset($primaryResult['status']) && $primaryResult['status'] === true) {
                Log::info('ApiCheckController:check - Primary API success.');
                return $primaryResult;
            }

            // --- Fallback ke Velixs API ---
            Log::info('ApiCheckController:check - Primary API failed or not found, trying Velixs fallback...', ['primary_response' => $primaryResult]);
            $velixsResult = $this->connectVelixs($parsedGame, $user_id);

            if ($velixsResult && isset($velixsResult['status']) && $velixsResult['status'] === true) {
                Log::info('ApiCheckController:check - Velixs Fallback API success.');
                return $velixsResult;
            }

            // Kedua API gagal
            Log::warning('ApiCheckController:check - Both APIs failed.', ['velixs_response' => $velixsResult]);
            return ['status' => false, 'message' => 'User not found.'];
        });


        if ($result && isset($result['status']) && $result['status'] === true) {
            $nickname = $result['nickname'] ?? null;
            Log::info('ApiCheckController:check - User found successfully.', ['nickname' => $nickname, 'from_cache' => Cache::has($cacheKey)]);

            $data = [
                'status' => ['code' => 200, 'message' => 'User found'],
                'data' => [
                    'user_id'  => $user_id,
                    'username' => $nickname,
                ]
            ];
            if (!empty($zone_id)) {
                $data['data']['zone_id'] = $zone_id;
            }
            return $data;

        } else {
            $errorMessage = $result['message'] ?? 'User not found or invalid.';
            Log::error('ApiCheckController:check - Failed to find user.', ['error' => $errorMessage, 'api_response' => $result]);
            // Jika hasil dari API adalah error, hapus dari cache agar bisa dicoba lagi nanti
            Cache::forget($cacheKey);
            return ['status' => ['code' => 404, 'message' => $errorMessage]];
        }
    }

    /**
     * Menghubungkan ke API utama: api-cek-id-game-ten.vercel.app
     */
    private function connectPrimary(array $params): ?array
    {
        $url = 'https://api-cek-id-game-ten.vercel.app/api/check-id-game';
        $postData = [
            'type_name' => $params['game'],
            'userId'    => $params['user_id'],
            'zoneId'    => $params['zone_id'] ?? ''
        ];

        $jsonData = json_encode($postData);
        Log::info('ApiCheckController:connectPrimary - Sending request.', ['url' => $url, 'data' => $jsonData]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // 3 detik timeout koneksi
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 detik timeout total
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        Log::info('ApiCheckController:connectPrimary - Response received.', ['http_code' => $httpcode, 'response_body' => $response]);
        
        if ($response === false || $httpcode !== 200) {
            Log::error('ApiCheckController:connectPrimary - cURL error or non-200 response.', ['http_code' => $httpcode, 'curl_error' => $curlError]);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Menghubungkan ke API Fallback: velixs.com
     * 
     * Pemetaan nama game dari format internal ke format velixs:
     * - Velixs menggunakan nama game seperti: "freefire", "mobilelegend", "pubgm", dll.
     */
    private function connectVelixs(string $parsedGame, string $userId): ?array
    {
        // Peta nama game internal -> nama game velixs
        $gameMap = [
            'free_fire'         => 'freefire',
            'garena_free_fire'  => 'freefire',
            'freefire'          => 'freefire',
            'mobile_legends'    => 'mobilelegend',
            'mobile_legends_bang_bang' => 'mobilelegend',
            'mobilelegend'      => 'mobilelegend',
            'mlbb'              => 'mobilelegend',
            'pubg_mobile'       => 'pubgm',
            'pubgm'             => 'pubgm',
            'genshin_impact'    => 'genshin',
            'genshin'           => 'genshin',
            'valorant'          => 'valorant',
            'honkai_star_rail'  => 'honkai_star_rail',
            'honkaistarrail'    => 'honkai_star_rail',
            'clash_of_clans'    => 'coc',
            'coc'               => 'coc',
            'call_of_duty'      => 'codm',
            'call_of_duty_mobile' => 'codm',
            'codm'              => 'codm',
        ];

        // Cek apakah game ada di peta; jika tidak ada, coba kirim nama gamenya langsung
        $velixsGame = $gameMap[$parsedGame] ?? $parsedGame;

        $url = 'https://api.velixs.com/idgames-checker';
        $apiKey = env('VELIXS_API_KEY') ?? 'b3f0bdc7ce3c73ab6404d59bc6a0324f94690d8bfa430bd8cd';

        if (!$apiKey) {
            Log::warning('ApiCheckController:connectVelixs - VELIXS_API_KEY is not set in .env');
            return null;
        }

        $postData = [
            'game'   => $velixsGame,
            'id'     => $userId,
            'apikey' => $apiKey,
        ];

        $jsonData = json_encode($postData);
        Log::info('ApiCheckController:connectVelixs - Sending request to Velixs.', ['url' => $url, 'game' => $velixsGame, 'id' => $userId]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4); // 4 detik timeout koneksi
        curl_setopt($ch, CURLOPT_TIMEOUT, 8); // 8 detik timeout total
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        Log::info('ApiCheckController:connectVelixs - Response received.', ['http_code' => $httpcode, 'response_body' => $response]);
        
        if ($response === false || $httpcode !== 200) {
            Log::error('ApiCheckController:connectVelixs - cURL error or non-200 response.', ['http_code' => $httpcode, 'curl_error' => $curlError]);
            return null;
        }

        $decoded = json_decode($response, true);

        // Sesuaikan format response velixs ke format yang di-expect oleh check()
        // Response velixs: { "status": true, "message": "...", "data": { "username": "..." } }
        // Format internal: { "status": true, "nickname": "..." }
        if (isset($decoded['status']) && $decoded['status'] === true && isset($decoded['data']['username'])) {
            return [
                'status'   => true,
                'nickname' => $decoded['data']['username'],
            ];
        }

        return ['status' => false, 'message' => $decoded['message'] ?? 'User not found on Velixs.'];
    }
}

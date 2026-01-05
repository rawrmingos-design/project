<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiCheckController extends Controller
{
    /**
     * Memeriksa ID game melalui API eksternal dengan Caching.
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

        // 2. Buat Kunci Cache yang Unik
        $cacheKey = "check_id_{$parsedGame}_{$user_id}_{$zone_id}";

        // 3. Gunakan Cache::remember
        // Data akan disimpan selama 10 menit.
        // Jika data ada di cache, langsung kembalikan.
        // Jika tidak, jalankan fungsi, simpan hasilnya, lalu kembalikan.
        $result = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($parsedGame, $user_id, $zone_id) {

            $params = [
                'game'    => $parsedGame,
                'user_id' => $user_id,
                'zone_id' => $zone_id
            ];
            return $this->connect($params);
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
    
    // ... method connect() tetap sama ...

    private function connect(array $params): ?array
    {
        $url = 'https://api-cek-id-game-ten.vercel.app/api/check-id-game';
        $postData = [
            'type_name' => $params['game'],
            'userId'    => $params['user_id'],
            'zoneId'    => $params['zone_id'] ?? ''
        ];

        $jsonData = json_encode($postData);
        Log::info('ApiCheckController:connect - Sending data to external API.', ['url' => $url, 'data' => $jsonData]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // Tambahkan timeout agar tidak menunggu terlalu lama
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 3 ); // 3 detik timeout koneksi
        curl_setopt( $ch, CURLOPT_TIMEOUT, 5 ); // 5 detik timeout total
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        Log::info('ApiCheckController:connect - Received response from external API.', ['http_code' => $httpcode, 'response_body' => $response]);
        
        if ($response === false || $httpcode !== 200) {
            Log::error('ApiCheckController:connect - cURL error or non-200 response.', ['http_code' => $httpcode, 'curl_error' => $curlError]);
            return null;
        }

        return json_decode($response, true);
    }
}
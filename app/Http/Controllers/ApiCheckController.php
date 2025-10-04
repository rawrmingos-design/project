<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiCheckController extends Controller
{
    public function check($user_id = null, $zone_id = null, $game = null)
    {
        $params = [
            'game'    => $game,
            'user_id' => $user_id,
            'zone_id' => $zone_id
        ];

        $result = $this->connect($params);

        if ($result && isset($result['status']) && $result['status'] == true) {
            $username = isset($result['data']['username']) ? $result['data']['username'] : null;

            $data = [
                'status' => ['code' => 200],
                'data' => ['user_id' => $params['user_id']]
            ];

            if (isset($params['zone_id'])) {
                $data['data']['zone_id'] = $params['zone_id'];
            }

            if ($username) {
                $data['data']['username'] = $username;
            }

            return $data;
        } else {
            return ['status' => ['code' => 1]];
        }
    }

    private function connect($params)
    {
        $game = $params['game'];
        $endpoint = "/api/game/$game";

        $query = '?id=' . urlencode($params['user_id']);
        if (isset($params['zone_id'])) {
            $query .= '&zone=' . urlencode($params['zone_id']);
        }

        $url = 'https://api.wesintopup.my.id' . $endpoint . $query;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return ['code' => 1];
        }

        $result = json_decode($response, true);

        return $result;
    }
}

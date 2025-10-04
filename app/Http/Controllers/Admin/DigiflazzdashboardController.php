<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DigiflazzdashboardController extends Controller
{
    private $api;
    private $url;
    private $username_digi;

    public function __construct()
    {
        $setting = \DB::table('setting_webs')->where('id', 1)->first();
        if ($setting) {
            $this->api = $setting->apikey_digi ?? null;
            $this->username_digi = $setting->username_digi ?? null;
        } else {
            $this->api = null;
            $this->username_digi = null;
        }
        $this->url = 'https://api.digiflazz.com';
    }

    public function harga()
    {
        try {
            $data = $this->connect('/v1/price-list', []);
            if(isset($data['data'])) {
                return view('components.admin.digiflazz.harga', ['data' => $data['data']]);
            } else {
                return view('components.admin.digiflazz.harga', ['error' => 'Data structure is invalid']);
            }
        } catch (\Exception $e) {
            return view('components.admin.digiflazz.harga', ['error' => $e->getMessage()]);
        }
    }

    private function connect($endpoint, $data)
    {
        $response = Http::post($this->url . $endpoint, [
            'username' => $this->username_digi,
            'apikey' => $this->api,
        ]);

        if ($response->successful()) {
            return $response->json();
        } else {
            return response()->json(['error' => $response->status()], $response->status());
        }
    }
}

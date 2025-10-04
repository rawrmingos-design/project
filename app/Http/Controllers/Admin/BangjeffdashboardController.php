<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BangjeffdashboardController extends Controller
{
    private $api;
    private $url;

    public function __construct()
    {
        $this->api = \DB::table('setting_webs')->where('id',1)->first()->apikey_bangjeff;
        $this->url = 'https://api.bangjeff.com';
    }

    public function balance()
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->api,
                'Content-Type' => 'application/json',
            ];

            $response = Http::withHeaders($headers)->post($this->url . '/api/v3/balance');

            if ($response->successful()) {
                $data = $response->json();
                
                $balance = $data['data']['balance'] ?? null;

                if ($balance !== null) {
                    return view('components.admin.bangjeff.ceksaldobj', ['saldo' => $balance]);
                } else {
                    $error = $response->json();
            return response()->json($error, $response->status());
                }
            } else {
                 $error = $response->json();
            return response()->json($error, $response->status());
            }
        } catch (\Exception $e) {
            return view('components.admin.bangjeff.ceksaldobj', ['error' => $e->getMessage()]);
        }
    }
    
    public function getProduct()
{
    try {
        $headers = [
            'Authorization' => 'Bearer ' . $this->api,
            'Content-Type' => 'application/json',
        ];

        $response = Http::withHeaders($headers)->post($this->url . '/api/v3/product');

        if ($response->successful()) {
            $data = $response->json();
            return view('components.admin.bangjeff.products', ['products' => $data]);
        } else {
            $error = $response->json();
            return view('components.admin.bangjeff.products', ['error' => $error]);
        }
    } catch (\Exception $e) {
        return view('components.admin.bangjeff.products', ['error' => $e->getMessage()]);
    }
}

}

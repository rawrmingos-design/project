<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IPAddressController extends Controller
{
    public function getIPAddress(Request $request)
    {
        $ipAddress = $request->ip();
        
        // Gunakan ipinfo API untuk mendapatkan informasi geolokasi berdasarkan IP
        $response = Http::get("https://ipinfo.io/{$ipAddress}/json?token=e879d202101b78");
    
        if ($response->successful()) {
            $locationData = $response->json();

            $responseData = [
                'ip' => $locationData['ip'] ?? '',
                'city' => $locationData['city'] ?? '',
                'region' => $locationData['region'] ?? '',
                'country' => $locationData['country'] ?? '',
                'loc' => $locationData['loc'] ?? '',
                'org' => $locationData['org'] ?? '',
                'timezone' => $locationData['timezone'] ?? '',
            ];

            return response()->json($responseData);
        } else {
            return response()->json(['error' => 'Data tidak ditemukan']);
        }
    }
}

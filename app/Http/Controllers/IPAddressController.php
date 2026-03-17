<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IPAddressController extends Controller
{
    /**
     * Resolve a value safe to store in `pembelians.ip_address`.
     *
     * Important: Order flow must never fail just because ipinfo is unreachable.
     */
    public function getIPAddress(Request $request): string
    {
        $ipAddress = (string) $request->ip();

        try {
            // Best-effort enrichment (do not block checkout on failure).
            $response = Http::timeout(2)->get("https://ipinfo.io/{$ipAddress}/json?token=e879d202101b78");

            if ($response->successful()) {
                $locationData = $response->json();
                $resolvedIp = (string) ($locationData['ip'] ?? '');

                if ($resolvedIp !== '') {
                    return $resolvedIp;
                }
            }
        } catch (\Throwable) {
            // Ignore network issues.
        }

        return $ipAddress;
    }
}

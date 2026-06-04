<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ResellerIntegration;
use App\Notifications\ResellerSecurityNotification;

class IpWhitelistController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'ip' => ['required', 'string', function ($attribute, $value, $fail) {
                $value = trim($value);

                if (str_contains($value, '/')) {
                    // CIDR validation
                    $parts = explode('/', $value, 2);

                    if (count($parts) !== 2) {
                        return $fail('Format CIDR tidak valid.');
                    }

                    [$ip, $prefix] = $parts;

                    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        return $fail('IP pada CIDR tidak valid. Gunakan format IPv4 (misal 192.168.1.0/24).');
                    }

                    if (!ctype_digit($prefix) || (int) $prefix < 1 || (int) $prefix > 32) {
                        return $fail('Prefix CIDR harus antara /1 dan /32. Nilai /0 (allow all) tidak diizinkan.');
                    }
                } else {
                    // Single IPv4 validation
                    if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        return $fail('Format IP tidak valid. Gunakan IPv4 (misal 192.168.1.1) atau CIDR (misal 192.168.1.0/24).');
                    }
                }
            }],
        ]);

        $user = $request->user();
        $integration = ResellerIntegration::where('user_id', $user->id)->first();

        if (!$integration) {
            return response()->json(['message' => 'Integrasi reseller tidak ditemukan.'], 404);
        }

        $allowedIps = is_array($integration->allowed_ips) ? $integration->allowed_ips : json_decode($integration->allowed_ips, true) ?? [];

        if (count($allowedIps) >= 20) {
            return response()->json(['message' => 'Maksimal 20 IP address yang diizinkan.'], 422);
        }

        if (in_array($request->ip, $allowedIps)) {
            return response()->json(['message' => 'IP address sudah ada di whitelist.'], 422);
        }

        $allowedIps[] = $request->ip;
        
        $integration->allowed_ips = $allowedIps;
        $integration->save();

        $user->notify(new ResellerSecurityNotification('IP Whitelist Updated', 'IP address ' . $request->ip . ' has been added to your whitelist.'));

        return response()->json([
            'message' => 'IP address berhasil ditambahkan.',
            'allowed_ips' => $allowedIps
        ]);
    }

    public function destroy(Request $request, $ip)
    {
        $user = $request->user();
        $integration = ResellerIntegration::where('user_id', $user->id)->first();

        if (!$integration) {
            return response()->json(['message' => 'Integrasi reseller tidak ditemukan.'], 404);
        }

        $allowedIps = is_array($integration->allowed_ips) ? $integration->allowed_ips : json_decode($integration->allowed_ips, true) ?? [];
        
        // Decode IP that might be URL encoded (e.g. CIDR with slash)
        $ipToRemove = urldecode($ip);
        
        $allowedIps = array_values(array_filter($allowedIps, function ($existingIp) use ($ipToRemove) {
            return $existingIp !== $ipToRemove;
        }));

        $integration->allowed_ips = $allowedIps;
        $integration->save();

        $user->notify(new ResellerSecurityNotification('IP Whitelist Updated', 'IP address ' . $ipToRemove . ' has been removed from your whitelist.'));

        return response()->json([
            'message' => 'IP address berhasil dihapus.',
            'allowed_ips' => $allowedIps
        ]);
    }
}

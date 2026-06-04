<?php

namespace App\Http\Middleware;

use App\Models\ResellerIntegration;
use App\Support\IpAddressMatcher;
use App\Support\ResellerApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Enforce IP whitelist for H2H API requests.
 *
 * Reads the resolved live reseller integration from request attributes
 * (set by ResolveLiveResellerIntegration middleware) and checks whether
 * the caller's IP is in the integration's allowed_ips list.
 *
 * Policy (confirmed by product owner):
 *   - If allowed_ips is empty → DENY (whitelist is mandatory)
 *   - If allowed_ips has entries → only matching IPs/CIDRs are allowed
 *   - Supports IPv4 exact match and CIDR ranges via IpAddressMatcher
 */
class EnforceResellerIpWhitelist
{
    public function handle(Request $request, Closure $next)
    {
        $integration = $request->attributes->get('live_reseller_integration');

        if (!$integration instanceof ResellerIntegration) {
            // Integration not resolved — this middleware must come after
            // resolve.live.reseller.integration in the middleware stack
            Log::warning('EnforceResellerIpWhitelist: no integration in request attributes', [
                'route' => $request->route()?->uri(),
                'ip'    => $request->ip(),
            ]);

            return ResellerApiResponse::error(
                'Integration context missing. Ensure X-Reseller-Integration-Code header is sent.',
                ResellerApiResponse::INTEGRATION_CODE_REQUIRED,
                422,
            );
        }

        $allowedIps = $integration->allowed_ips ?? [];

        // Normalize: handle both array and JSON string (legacy storage)
        if (is_string($allowedIps)) {
            $allowedIps = json_decode($allowedIps, true) ?? [];
        }

        if (empty($allowedIps)) {
            Log::warning('EnforceResellerIpWhitelist: IP whitelist empty — request denied', [
                'integration_id'   => $integration->getKey(),
                'integration_code' => $integration->integration_code,
                'client_ip'        => $request->ip(),
            ]);

            return ResellerApiResponse::error(
                'IP whitelist belum dikonfigurasi. Tambahkan IP server Anda di panel Credentials sebelum menggunakan Live API.',
                ResellerApiResponse::IP_WHITELIST_EMPTY,
                403,
            );
        }

        $clientIp        = $request->ip();
        $normalizedClient = IpAddressMatcher::normalize($clientIp);

        foreach ($allowedIps as $allowedEntry) {
            if (IpAddressMatcher::matches($normalizedClient, (string) $allowedEntry)) {
                // IP matched — allow through
                return $next($request);
            }
        }

        Log::warning('EnforceResellerIpWhitelist: IP not in whitelist — request denied', [
            'integration_id'   => $integration->getKey(),
            'integration_code' => $integration->integration_code,
            'client_ip'        => $clientIp,
            'normalized_ip'    => $normalizedClient,
            'allowed_count'    => count($allowedIps),
        ]);

        return ResellerApiResponse::error(
            'IP address ' . $clientIp . ' tidak diizinkan. Tambahkan IP ini di panel Credentials.',
            ResellerApiResponse::IP_NOT_WHITELISTED,
            403,
        );
    }
}

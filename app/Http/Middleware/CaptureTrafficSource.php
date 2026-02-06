<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureTrafficSource
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedSources = [
            'facebook' => 'Facebook', 'fb' => 'Facebook',
            'instagram' => 'Instagram', 'ig' => 'Instagram',
            'tiktok' => 'TikTok', 'tt' => 'TikTok',
            'youtube' => 'YouTube', 'yt' => 'YouTube',
            'google' => 'Google', 'gl' => 'Google',
            'whatsapp' => 'WhatsApp', 'wa' => 'WhatsApp',
        ];

        // 1. Check URL Parameters (Highest Priority)
        $rawSource = $request->query('utm_source') ?? $request->query('ref');
        $detectedSource = null;

        // Validation: Check against whitelist
        if ($rawSource && isset($allowedSources[strtolower($rawSource)])) {
            $detectedSource = $allowedSources[strtolower($rawSource)];
        }

        // 2. Fallback: HTTP Referer (If no valid URL param)
        if (!$detectedSource) {
            $referer = $request->header('referer');
            $currentHost = $request->getHost();

            if ($referer) {
                $refererHost = parse_url($referer, PHP_URL_HOST);
                if ($refererHost && $refererHost !== $currentHost) {
                    $detectedSource = match (true) {
                        str_contains($referer, 'facebook.com') => 'Facebook',
                        str_contains($referer, 'instagram.com') => 'Instagram',
                        str_contains($referer, 'tiktok.com') => 'TikTok',
                        str_contains($referer, 'google.com') => 'Google',
                        str_contains($referer, 'youtube.com') => 'YouTube',
                        // Note: For referer, we accept unknown domains as 'Other' or domain name
                        // But per user request "only whitelisted", we might want to strict check here too?
                        // User said: "refernya selain dari yang kita inginkan maka tidak akan disimpan"
                        // So we STRICTLY check domain match.
                        default => null 
                    };
                }
            }
        }

        // 3. Logic: Update Session
        // - If we detect a NEW VALID source, overwrite session.
        // - If we detect NO source (Direct) or INVALID source:
        //    - If session is empty, set 'Direct'.
        //    - If session exists, KEEP IT (Persistence).

        if ($detectedSource) {
            $request->session()->put('traffic_source', $detectedSource);
        } else {
            // No valid source detected (Direct or Invalid)
            if (!$request->session()->has('traffic_source')) {
                $request->session()->put('traffic_source', 'Direct');
            }
            // Else: Keep existing session (Sticky)
        }

        return $next($request);
    }
}

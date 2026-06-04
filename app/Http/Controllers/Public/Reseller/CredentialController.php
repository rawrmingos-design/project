<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CredentialController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Eager load integrations and callback profiles
        $user->load('resellerIntegrations.callbackProfile');

        $liveIntegration = $user->resellerIntegrations->where('mode', 'live')->first();
        $sandboxIntegration = $user->resellerIntegrations->where('mode', 'sandbox')->first();

        $liveData = null;
        if ($liveIntegration) {
            $webhook = null;
            if ($liveIntegration->callbackProfile) {
                // EXPLICITLY mask the webhook secret
                $webhook = [
                    'is_enabled' => $liveIntegration->callbackProfile->is_enabled,
                    'url' => $liveIntegration->callbackProfile->callback_url,
                    'algorithm' => $liveIntegration->callbackProfile->signing_algorithm,
                    'signature_header' => $liveIntegration->callbackProfile->signature_header,
                    'version' => $liveIntegration->callbackProfile->version,
                ];
            }

            // IP Whitelist
            $allowedIps = [];
            if (!empty($liveIntegration->allowed_ips)) {
                $allowedIps = is_array($liveIntegration->allowed_ips) 
                    ? $liveIntegration->allowed_ips 
                    : json_decode($liveIntegration->allowed_ips, true) ?? [];
            }

            $liveData = [
                'is_active' => $liveIntegration->is_active,
                'integration_code' => $liveIntegration->integration_code,
                'api_key_hint' => $user->api_key_hint,
                'webhook' => $webhook,
                'allowed_ips' => $allowedIps,
            ];
        }

        $sandboxData = null;
        if ($sandboxIntegration) {
            $sandboxData = [
                'is_active' => $sandboxIntegration->is_active,
                'integration_code' => $sandboxIntegration->integration_code,
                'api_key_hint' => $user->sandbox_api_key_hint,
                'rotated_at' => $user->sandbox_api_key_rotated_at ? $user->sandbox_api_key_rotated_at->toIso8601String() : null,
                'last_used_at' => $user->sandbox_api_key_last_used_at ? $user->sandbox_api_key_last_used_at->toIso8601String() : null,
            ];
        }

        return Inertia::render('Reseller/Credentials', [
            'live' => $liveData,
            'sandbox' => $sandboxData,
        ]);
    }
}

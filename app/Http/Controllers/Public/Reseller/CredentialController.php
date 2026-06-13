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
                'api_key_hint' => $liveIntegration->api_key_hint,
                'api_key_full' => session('new_live_api_key'), // Full key if just generated/rotated
                'is_new_key' => session()->has('new_live_api_key'), // Flag for first view
                'rotated_at' => $liveIntegration->api_key_rotated_at ? $liveIntegration->api_key_rotated_at->toIso8601String() : null,
                'last_used_at' => $liveIntegration->api_key_last_used_at ? $liveIntegration->api_key_last_used_at->toIso8601String() : null,
                'webhook' => $webhook,
                'allowed_ips' => $allowedIps,
            ];
        }

        $sandboxData = null;
        if ($sandboxIntegration) {
            $sandboxWebhook = null;
            if ($sandboxIntegration->callbackProfile) {
                $sandboxWebhook = [
                    'is_enabled' => $sandboxIntegration->callbackProfile->is_enabled,
                    'url' => $sandboxIntegration->callbackProfile->callback_url,
                    'has_secret' => filled($sandboxIntegration->callbackProfile->decryptedWebhookSecret()),
                ];
            }

            $sandboxData = [
                'is_active' => $sandboxIntegration->is_active,
                'integration_code' => $sandboxIntegration->integration_code,
                'api_key_hint' => $sandboxIntegration->api_key_hint,
                'api_key_full' => session('new_sandbox_api_key'), // Full key if just generated/rotated
                'is_new_key' => session()->has('new_sandbox_api_key'), // Flag for first view
                'rotated_at' => $sandboxIntegration->api_key_rotated_at ? $sandboxIntegration->api_key_rotated_at->toIso8601String() : null,
                'last_used_at' => $sandboxIntegration->api_key_last_used_at ? $sandboxIntegration->api_key_last_used_at->toIso8601String() : null,
                'webhook' => $sandboxWebhook,
            ];
        }

        // Update live webhook to include has_secret
        if ($liveData && isset($liveData['webhook']) && $liveIntegration->callbackProfile) {
            $liveData['webhook']['has_secret'] = filled($liveIntegration->callbackProfile->decryptedWebhookSecret());
        }

        return Inertia::render('Reseller/Credentials', [
            'live' => $liveData,
            'sandbox' => $sandboxData,
        ]);
    }

    public function updateWebhook(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:live,sandbox',
            'url' => 'required|url',
            'generate_secret' => 'boolean',
        ]);

        $user = $request->user();
        $mode = $request->input('mode');
        
        $integration = $user->resellerIntegrations()->where('mode', $mode)->first();
        if (!$integration) {
            return redirect()->back()->with('flash_error', "Integrasi {$mode} tidak ditemukan.");
        }

        $profile = $integration->callbackProfile()->firstOrCreate(
            ['reseller_integration_id' => $integration->id],
            [
                'is_enabled' => true,
                'signing_algorithm' => 'HMAC-SHA256',
                'signature_header' => 'X-Callback-Signature',
            ]
        );

        $updates = [
            'callback_url' => $request->input('url'),
            'is_enabled' => true,
        ];

        $newSecret = null;
        // Generate new secret if requested, OR if there's no secret currently set
        if ($request->boolean('generate_secret') || blank($profile->decryptedWebhookSecret())) {
            $newSecret = 'whsec_' . \Illuminate\Support\Str::random(32);
            $updates['webhook_secret'] = $newSecret;
        }

        $profile->update($updates);

        if ($newSecret) {
            // Flash the new secret so it can be shown to the user once
            return redirect()->back()->with([
                'flash_success' => "Webhook {$mode} berhasil diperbarui. Secret baru telah dibuat.",
                'new_webhook_secret' => $newSecret,
                'webhook_mode' => $mode,
            ]);
        }

        return redirect()->back()->with('flash_success', "Webhook URL {$mode} berhasil diperbarui.");
    }
}

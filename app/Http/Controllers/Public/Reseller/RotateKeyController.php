<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\SandboxApiKeyService;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Notifications\ResellerSecurityNotification;

class RotateKeyController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    protected function verify2FA(Request $request)
    {
        $request->validate([
            'totp_code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            return false;
        }

        // Gunakan window = 2 agar toleransi waktu sama dengan LoginController
        return $this->google2fa->verifyKey((string) $user->two_factor_secret, $request->totp_code, 2);
    }

    public function rotateLive(Request $request)
    {
        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            return response()->json(['message' => 'Anda harus mengaktifkan 2FA terlebih dahulu di Pengaturan.'], 422);
        }

        if (!$this->verify2FA($request)) {
            return response()->json(['message' => 'Kode 2FA tidak valid.'], 422);
        }

        $integration = $user->resellerIntegrations()->where('mode', 'live')->first();

        if (!$integration) {
            return response()->json(['message' => 'Integrasi Live tidak ditemukan.'], 404);
        }

        $rawKey = 'egylive_' . Str::random(40);

        $integration->api_key = $rawKey; // This triggers the mutator
        $integration->api_key_rotated_at = now();
        $integration->save();

        Log::info('Reseller Live API Key rotated', ['integration_id' => $integration->id, 'user_id' => $user->id]);

        $user->notify(new ResellerSecurityNotification(
            'Security Alert',
            'Your Live API Key has been rotated. If this was not you, please contact support immediately.'
        ));

        return response()->json([
            'message' => 'Live API Key berhasil dirotasi.',
            'raw_key' => $rawKey,
            'hint'    => $integration->api_key_hint,
        ]);
    }

    public function rotateSandbox(Request $request)
    {
        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            return response()->json(['message' => 'Anda harus mengaktifkan 2FA terlebih dahulu di Pengaturan.'], 422);
        }

        if (!$this->verify2FA($request)) {
            return response()->json(['message' => 'Kode 2FA tidak valid.'], 422);
        }

        $integration = $user->resellerIntegrations()->where('mode', 'sandbox')->first();

        if (!$integration) {
            return response()->json(['message' => 'Integrasi Sandbox tidak ditemukan.'], 404);
        }

        $rawKey = 'egysbx_' . Str::random(40);

        $integration->api_key = $rawKey; // This triggers the mutator
        $integration->api_key_rotated_at = now();
        $integration->save();

        Log::info('Reseller Sandbox API Key rotated', ['integration_id' => $integration->id, 'user_id' => $user->id]);

        $user->notify(new ResellerSecurityNotification('Security Alert', 'Your Sandbox API Key has been rotated.'));

        return response()->json([
            'message' => 'Sandbox API Key berhasil dirotasi.',
            'raw_key' => $rawKey,
            'hint' => $integration->api_key_hint
        ]);
    }
}

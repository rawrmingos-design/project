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

        $rawKey = 'live_' . Str::random(40);

        // Derive hint and prefix from the RAW key before hashing
        $hint   = '...' . substr($rawKey, -6);
        $prefix = substr($rawKey, 0, 8);

        // Store the bcrypt HASH in api_key — raw key is never persisted
        // Bypass the Eloquent mutator to avoid it overwriting hint/prefix
        // with values derived from the hash string
        $user->forceFill([
            'api_key'        => Hash::make($rawKey),
            'api_key_hint'   => $hint,
            'api_key_prefix' => $prefix,
        ])->save();

        Log::info('Reseller Live API Key rotated', ['user_id' => $user->id]);

        $user->notify(new ResellerSecurityNotification(
            'Security Alert',
            'Your Live API Key has been rotated. If this was not you, please contact support immediately.'
        ));

        return response()->json([
            'message' => 'Live API Key berhasil dirotasi.',
            'raw_key' => $rawKey,   // shown ONCE — user must copy this immediately
            'hint'    => $hint,
        ]);
    }

    public function rotateSandbox(Request $request, SandboxApiKeyService $sandboxService)
    {
        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            return response()->json(['message' => 'Anda harus mengaktifkan 2FA terlebih dahulu di Pengaturan.'], 422);
        }

        if (!$this->verify2FA($request)) {
            return response()->json(['message' => 'Kode 2FA tidak valid.'], 422);
        }

        $rawKey = $sandboxService->rotateForUser($user);
        $user->refresh();
        $hint = $user->sandbox_api_key_hint;

        Log::info('Reseller Sandbox API Key rotated', ['user_id' => $user->id]);

        $user->notify(new ResellerSecurityNotification('Security Alert', 'Your Sandbox API Key has been rotated.'));

        return response()->json([
            'message' => 'Sandbox API Key berhasil dirotasi.',
            'raw_key' => $rawKey,
            'hint' => $hint
        ]);
    }
}

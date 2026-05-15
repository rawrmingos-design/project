<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DsController as LegacySettingsController;
use App\Services\PublicSiteConfigService;
use App\Support\PublicThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQRCode;

class SettingsPageController extends Controller
{
    private const TWO_FACTOR_SESSION_KEY = 'settings_2fa_pending_secret';

    public function index(
        PublicSiteConfigService $siteConfigService,
        LegacySettingsController $legacySettingsController,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacySettingsController->editProfile();
        }

        $user = Auth::user();
        $recoveryCodes = json_decode((string) ($user->two_factor_recovery_codes ?? '[]'), true);
        $recoveryCodes = is_array($recoveryCodes) ? array_values(array_filter($recoveryCodes, fn ($code) => filled($code))) : [];

        return Inertia::render('Public/Settings', [
            'settingsPage' => [
                'profile' => [
                    'name' => (string) ($user->name ?? ''),
                    'username' => (string) ($user->username ?? ''),
                    'email' => (string) ($user->email ?? ''),
                    'phone' => (string) ($user->no_wa ?? ''),
                    'apiKey' => (string) ($user->api_key ?? ''),
                ],
                'twoFactor' => [
                    'enabled' => filled($user->two_factor_secret),
                    'recoveryCodesCount' => count($recoveryCodes),
                ],
                'oauth' => [
                    'googleConnected' => filled($user->google_id ?? null),
                    'googleEmail' => (string) ($user->email ?? ''),
                ],
                'links' => [
                    'dashboard' => route('dashboard'),
                    'transactions' => route('riwayat'),
                    'mutation' => route('reload'),
                    'affiliate' => route('affiliate'),
                    'canShowAffiliate' => ! in_array(strtolower((string) ($user->affiliate_status ?? '')), ['', 'inactive'], true),
                ],
                'flash' => [
                    'success' => session('success'),
                    'error' => session('error'),
                ],
            ],
            'meta' => [
                'title' => "Pengaturan - {$settings->judul_web}",
                'description' => 'Atur profil akun, keamanan password, dan Two Factor Authentication untuk akun kamu.',
                'keywords' => "pengaturan akun, profile, two factor authentication, {$settings->judul_web}",
                'canonical' => url('/id/settings'),
                'image' => url($siteConfigService->normalizeAssetPath($settings->logo_favicon)),
            ],
        ]);
    }

    public function updateProfile(
        Request $request,
        LegacySettingsController $legacySettingsController,
        PublicSiteConfigService $siteConfigService,
    ): RedirectResponse {
        $settings = $siteConfigService->getSettings();
        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacySettingsController->saveEditProfile($request);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users,username,' . Auth::id()],
            'no_wa' => ['required', 'regex:/^[0-9]{10,18}$/'],
        ], [
            'name.required' => 'Harap isi kolom nama.',
            'username.required' => 'Harap isi kolom username.',
            'username.unique' => 'Username telah digunakan.',
            'no_wa.required' => 'Harap isi nomor WhatsApp.',
            'no_wa.regex' => 'Nomor WhatsApp tidak valid.',
        ]);

        $user = Auth::user();
        $user->name = trim((string) $request->input('name'));
        $user->username = trim((string) $request->input('username'));
        $user->no_wa = trim((string) $request->input('no_wa'));
        $user->save();

        return redirect()->back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'new_password.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
        ]);

        $user = Auth::user();
        if (! Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Kata sandi saat ini tidak cocok.'],
            ]);
        }

        $user->password = Hash::make((string) $request->input('new_password'));
        $user->save();

        return redirect()->back()->with('success', 'Kata sandi berhasil diperbarui.');
    }

    public function regenerateApiKey(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->api_key = Str::random(32);
        $user->save();

        return redirect()->back()->with('success', 'API Key berhasil dibuat ulang.');
    }

    public function setupTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();

        $google2fa = new Google2FA();
        $secret = strtoupper(trim($google2fa->generateSecretKey()));
        $holder = trim((string) ($user->email ?: $user->username));
        $issuer = trim((string) config('app.name', 'Topup App'));
        $otpAuthUrl = $google2fa->getQRCodeUrl($issuer, $holder, $secret);

        try {
            $google2faQr = new Google2FAQRCode();
            $qrImageUrl = $google2faQr->getQRCodeInline($issuer, $holder, $secret, 240);
        } catch (\Throwable) {
            $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($otpAuthUrl);
        }

        $request->session()->put(self::TWO_FACTOR_SESSION_KEY, $secret);

        return response()->json([
            'status' => 'success',
            'message' => 'Secret 2FA berhasil dibuat. Scan QR dan masukkan kode verifikasi.',
            'data' => [
                'secret' => $secret,
                'otp_auth_url' => $otpAuthUrl,
                'qr_image_url' => $qrImageUrl,
            ],
        ]);
    }

    public function enableTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'regex:/^[0-9]{6}$/'],
        ], [
            'code.required' => 'Kode autentikator wajib diisi.',
            'code.regex' => 'Kode autentikator harus 6 digit angka.',
        ]);

        $secret = trim((string) $request->session()->get(self::TWO_FACTOR_SESSION_KEY, ''));
        if ($secret === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Session setup 2FA sudah kedaluwarsa. Silakan setup ulang.',
                'error_code' => '2FA_PENDING_NOT_FOUND',
            ], 422);
        }

        $code = preg_replace('/\D+/', '', (string) $request->input('code'));
        $google2fa = new Google2FA();
        $isValid = $google2fa->verifyKey($secret, $code, 2);

        if (! $isValid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode autentikator tidak valid.',
                'error_code' => '2FA_INVALID_CODE',
            ], 422);
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn (): string => Str::upper(Str::random(10)))
            ->values()
            ->all();

        $user = $request->user();
        $user->two_factor_secret = strtoupper($secret);
        $user->two_factor_recovery_codes = json_encode($recoveryCodes);
        $user->save();

        $request->session()->forget(self::TWO_FACTOR_SESSION_KEY);

        return response()->json([
            'status' => 'success',
            'message' => 'Two Factor Authentication berhasil diaktifkan.',
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'code' => ['required', 'regex:/^[0-9]{6}$/'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'code.required' => 'Kode autentikator wajib diisi.',
            'code.regex' => 'Kode autentikator harus 6 digit angka.',
        ]);

        $user = $request->user();
        if (! filled($user->two_factor_secret)) {
            return response()->json([
                'status' => 'error',
                'message' => '2FA belum aktif di akun ini.',
                'error_code' => '2FA_NOT_ENABLED',
            ], 422);
        }

        if (! Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kata sandi saat ini tidak cocok.',
                'error_code' => 'INVALID_PASSWORD',
            ], 422);
        }

        $code = preg_replace('/\D+/', '', (string) $request->input('code'));
        $google2fa = new Google2FA();
        $isValid = $google2fa->verifyKey((string) $user->two_factor_secret, $code, 2);

        if (! $isValid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode autentikator tidak valid.',
                'error_code' => '2FA_INVALID_CODE',
            ], 422);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->save();
        $request->session()->forget(self::TWO_FACTOR_SESSION_KEY);

        return response()->json([
            'status' => 'success',
            'message' => 'Two Factor Authentication berhasil dinonaktifkan.',
        ]);
    }
}

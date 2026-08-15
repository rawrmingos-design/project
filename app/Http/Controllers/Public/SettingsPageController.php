<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DsController as LegacySettingsController;
use App\Models\TelegramIdentity;
use App\Models\TelegramLinkChallenge;
use App\Models\WhatsappLinkChallenge;
use App\Services\PublicSiteConfigService;
use App\Services\Telegram\TelegramLinkService;
use App\Services\Whatsapp\WhatsappLinkService;
use App\Services\WhatsappNotificationService;
use App\Support\PublicThemeRegistry;
use App\Support\WhatsappNumberNormalizer;
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
        TelegramLinkService $telegramLinkService,
    ): Response|\Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application {
        $settings = $siteConfigService->getSettings();

        if (($settings->public_theme ?? PublicThemeRegistry::DEFAULT) === PublicThemeRegistry::DEFAULT) {
            return $legacySettingsController->editProfile();
        }

        $user = Auth::user();
        $pendingWhatsappChallenge = WhatsappLinkChallenge::query()
            ->where('user_id', $user->getKey())
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
        $pendingTelegramChallenge = TelegramLinkChallenge::query()
            ->where('user_id', $user->getKey())
            ->where('bot_scope', (string) config('services.telegram-bot-api.bot_scope', 'default'))
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
        $telegramIdentity = $telegramLinkService->activeIdentityForUser($user);
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
                'whatsappLink' => [
                    'number' => $user->no_wa,
                    'verified' => $user->whatsapp_verified_at !== null,
                    'verifiedAt' => $user->whatsapp_verified_at?->toIso8601String(),
                    'pendingChallenge' => $pendingWhatsappChallenge ? [
                        'expiresAt' => $pendingWhatsappChallenge->expires_at?->toIso8601String(),
                        'expiresInSeconds' => max(0, now()->diffInSeconds($pendingWhatsappChallenge->expires_at)),
                    ] : null,
                ],
                'telegramLink' => [
                    'botScope' => (string) config('services.telegram-bot-api.bot_scope', 'default'),
                    'botConfigured' => filled(config('services.telegram-bot-api.bot_username')),
                    'verified' => $telegramIdentity !== null,
                    'username' => $telegramIdentity?->username,
                    'firstName' => $telegramIdentity?->first_name,
                    'lastName' => $telegramIdentity?->last_name,
                    'verifiedAt' => $telegramIdentity?->verified_at?->toIso8601String(),
                    'pendingChallenge' => $pendingTelegramChallenge ? [
                        'expiresAt' => $pendingTelegramChallenge->expires_at?->toIso8601String(),
                        'expiresInSeconds' => max(0, now()->diffInSeconds($pendingTelegramChallenge->expires_at)),
                    ] : null,
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

    public function whatsappLinkStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $pendingChallenge = WhatsappLinkChallenge::query()
            ->where('user_id', $user->getKey())
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'number' => $user->no_wa,
                'verified' => $user->whatsapp_verified_at !== null,
                'verified_at' => $user->whatsapp_verified_at?->toIso8601String(),
                'pending_challenge' => $pendingChallenge ? [
                    'expires_at' => $pendingChallenge->expires_at?->toIso8601String(),
                    'expires_in_seconds' => max(0, now()->diffInSeconds($pendingChallenge->expires_at)),
                ] : null,
            ],
        ]);
    }

    public function createWhatsappLinkChallenge(
        Request $request,
        WhatsappLinkService $linkService,
        WhatsappNotificationService $whatsappNotificationService,
    ): JsonResponse {
        $request->validate([
            'no_wa' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                if (WhatsappNumberNormalizer::normalize((string) $value) === null) {
                    $fail($attribute . ' harus berupa nomor Indonesia yang valid.');
                }
            }],
        ]);

        $user = $request->user();
        $normalizedNumber = WhatsappNumberNormalizer::normalize((string) $request->input('no_wa'));

        if ($normalizedNumber === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor WhatsApp tidak valid.',
                'error_code' => 'WHATSAPP_INVALID_NUMBER',
            ], 422);
        }

        try {
            $result = $linkService->createChallenge($user, $normalizedNumber);
        } catch (ValidationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nomor WhatsApp tidak dapat digunakan.',
                'error_code' => 'WHATSAPP_NUMBER_UNAVAILABLE',
            ], 422);
        }

        $message = sprintf(
            "Permintaan linking akun diterima. Kirim LINK %s dari nomor ini melalui WhatsApp. Kode berlaku %d menit dan hanya dapat digunakan satu kali. Jangan bagikan kode ini.",
            $result['code'],
            $result['expires_in_minutes'],
        );
        $notification = $whatsappNotificationService->sendMessage($normalizedNumber, $message);

        return response()->json([
            'status' => 'success',
            'message' => ($notification['success'] ?? false)
                ? 'Kode linking telah dikirim ke WhatsApp.'
                : 'Kode linking dibuat. Kirim kode tersebut dari nomor WhatsApp yang didaftarkan.',
            'data' => [
                'number' => $normalizedNumber,
                'code' => $result['code'],
                'instruction' => 'Kirim LINK <kode> dari nomor WhatsApp ini.',
                'expires_at' => $result['expires_at']->toIso8601String(),
                'expires_in_minutes' => $result['expires_in_minutes'],
                'notification_sent' => ($notification['success'] ?? false) === true,
            ],
        ]);
    }

    public function revokeWhatsappLinkChallenge(Request $request, WhatsappLinkService $linkService): JsonResponse
    {
        $linkService->revokeForUser($request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Kode linking dibatalkan.',
        ]);
    }

    public function unlinkWhatsapp(Request $request, WhatsappLinkService $linkService): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kata sandi saat ini tidak cocok.',
                'error_code' => 'INVALID_PASSWORD',
            ], 422);
        }

        $linkService->unlink($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Nomor WhatsApp berhasil dilepas dari akun.',
        ]);
    }

    public function telegramLinkStatus(Request $request, TelegramLinkService $linkService): JsonResponse
    {
        $user = $request->user();
        $pendingChallenge = TelegramLinkChallenge::query()
            ->where('user_id', $user->getKey())
            ->where('bot_scope', (string) config('services.telegram-bot-api.bot_scope', 'default'))
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
        $identity = $linkService->activeIdentityForUser($user);

        return response()->json([
            'status' => 'success',
            'data' => [
                'verified' => $identity !== null,
                'username' => $identity?->username,
                'first_name' => $identity?->first_name,
                'last_name' => $identity?->last_name,
                'verified_at' => $identity?->verified_at?->toIso8601String(),
                'pending_challenge' => $pendingChallenge ? [
                    'expires_at' => $pendingChallenge->expires_at?->toIso8601String(),
                    'expires_in_seconds' => max(0, now()->diffInSeconds($pendingChallenge->expires_at)),
                    'launch_url' => null,
                ] : null,
                'bot_configured' => filled(config('services.telegram-bot-api.bot_username')),
            ],
        ]);
    }

    public function createTelegramLinkChallenge(Request $request, TelegramLinkService $linkService): JsonResponse
    {
        $result = $linkService->createChallenge($request->user());

        return response()->json([
            'status' => 'success',
            'message' => $result['launch_url'] !== null
                ? 'Link Telegram berhasil dibuat. Buka link tersebut lalu tekan Start.'
                : 'Challenge Telegram berhasil dibuat, tetapi username bot belum dikonfigurasi.',
            'data' => [
                'launch_url' => $result['launch_url'],
                'expires_at' => $result['expires_at']->toIso8601String(),
                'expires_in_minutes' => $result['expires_in_minutes'],
                'bot_scope' => $result['bot_scope'],
                'pending_challenge' => [
                    'expires_at' => $result['expires_at']->toIso8601String(),
                    'expires_in_seconds' => max(0, now()->diffInSeconds($result['expires_at'])),
                ],
            ],
        ]);
    }

    public function revokeTelegramLinkChallenge(Request $request, TelegramLinkService $linkService): JsonResponse
    {
        $linkService->revokeForUser($request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Link Telegram yang tertunda dibatalkan.',
        ]);
    }

    public function unlinkTelegram(Request $request, TelegramLinkService $linkService): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kata sandi saat ini tidak cocok.',
                'error_code' => 'INVALID_PASSWORD',
            ], 422);
        }

        $linkService->unlink($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Telegram berhasil dilepas dari akun.',
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
            'no_wa' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                $normalized = WhatsappNumberNormalizer::normalize((string) $value);
                if ($normalized === null) {
                    $fail($attribute . ' harus berupa nomor Indonesia yang valid.');
                    return;
                }

                if (\App\Models\User::query()
                    ->where('no_wa', $normalized)
                    ->where('id', '<>', Auth::id())
                    ->exists()) {
                    $fail($attribute . ' telah digunakan.');
                }
            }],
        ], [
            'name.required' => 'Harap isi kolom nama.',
            'username.required' => 'Harap isi kolom username.',
            'username.unique' => 'Username telah digunakan.',
            'no_wa.required' => 'Harap isi nomor WhatsApp.',
            'no_wa.regex' => 'Nomor WhatsApp tidak valid.',
        ]);

        $user = Auth::user();
        $normalizedWhatsapp = WhatsappNumberNormalizer::normalize((string) $request->input('no_wa'));
        if ($normalizedWhatsapp === null) {
            return redirect()->back()->withErrors([
                'no_wa' => 'Nomor WhatsApp harus berupa nomor Indonesia yang valid.',
            ]);
        }

        $user->name = trim((string) $request->input('name'));
        $user->username = trim((string) $request->input('username'));
        $user->no_wa = $normalizedWhatsapp;
        $user->whatsapp_verified_at = null;
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

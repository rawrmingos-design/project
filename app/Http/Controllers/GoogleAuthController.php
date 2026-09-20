<?php

namespace App\Http\Controllers;

use App\Models\SettingWeb;
use App\Models\User;
use App\Http\Controllers\Concerns\HandlesLoginRedirect;
use App\Support\PendingGoogleSignup;
use App\Support\WhatsappNumberNormalizer;
use App\Support\PublicThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class GoogleAuthController extends Controller
{
    use HandlesLoginRedirect;

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'credential' => ['required', 'string'],
        ]);

        $googleClientId = $this->resolveGoogleClientId();
        if ($googleClientId === '') {
            throw ValidationException::withMessages([
                'error' => ['Google login belum dikonfigurasi.'],
            ]);
        }

        try {
            $tokenInfoResponse = Http::timeout(10)
                ->acceptJson()
                ->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $request->input('credential'),
                ]);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'error' => ['Layanan verifikasi Google sedang bermasalah. Coba beberapa saat lagi.'],
            ]);
        }

        if (! $tokenInfoResponse->ok()) {
            throw ValidationException::withMessages([
                'error' => ['Verifikasi akun Google gagal. Silakan coba lagi.'],
            ]);
        }

        $tokenInfo = $tokenInfoResponse->json();
        $audience = (string) ($tokenInfo['aud'] ?? '');
        $issuer = (string) ($tokenInfo['iss'] ?? '');
        $sub = trim((string) ($tokenInfo['sub'] ?? ''));
        $email = strtolower(trim((string) ($tokenInfo['email'] ?? '')));
        $emailVerified = filter_var((string) ($tokenInfo['email_verified'] ?? 'false'), FILTER_VALIDATE_BOOL);

        if (
            $audience !== $googleClientId
            || ! in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
            || $sub === ''
            || $email === ''
            || $emailVerified !== true
        ) {
            throw ValidationException::withMessages([
                'error' => ['Data akun Google tidak valid.'],
            ]);
        }

        $hasGoogleColumn = Schema::hasColumn('users', 'google_id');
        $hasGoogleAvatarColumn = Schema::hasColumn('users', 'google_avatar');

        $user = null;

        if ($hasGoogleColumn) {
            $user = User::query()->where('google_id', $sub)->first();
        }

        if (! $user) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        }

        if (! $user) {
            // Two-step sign-up: Google never returns a phone number, but `users.no_wa`
            // is NOT NULL and is the buyer identity used by the order API + WhatsApp
            // flows. Park the verified profile in the session and collect the number
            // before creating the row (same requirement as the normal sign-up form).
            PendingGoogleSignup::put($request, [
                'sub' => $sub,
                'email' => $email,
                'name' => (string) ($tokenInfo['name'] ?? ''),
                'picture' => (string) ($tokenInfo['picture'] ?? ''),
                'google_id_column' => $hasGoogleColumn,
                'google_avatar_column' => $hasGoogleAvatarColumn,
                'redirect' => $request->session()->get(self::LOGIN_REDIRECT_SESSION_KEY),
            ]);

            return redirect()->to(route('auth.google.complete'));
        } else {
            $updates = [];

            if ($hasGoogleColumn && blank($user->google_id)) {
                $updates['google_id'] = $sub;
            }

            if ($hasGoogleAvatarColumn && filled($tokenInfo['picture'] ?? null)) {
                $updates['google_avatar'] = (string) $tokenInfo['picture'];
            }

            if ($updates !== []) {
                $user->fill($updates);
                $user->save();
            }
        }

        if ($user->role === 'Admin') {
            throw ValidationException::withMessages([
                'error' => ['Akun admin tidak bisa login melalui halaman ini.'],
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectAfterLogin($request);
    }

    /**
     * Step 2 of the Google sign-up: the visitor supplies the WhatsApp number that
     * Google cannot provide, then the account is created and activated.
     */
    public function showComplete(Request $request)
    {
        $pending = PendingGoogleSignup::get($request);

        if ($pending === null) {
            return redirect()->to(route('login'));
        }

        $props = [
            'name' => $pending['name'],
            'email' => $pending['email'],
            'avatar' => $pending['picture'],
            'meta' => [
                'title' => 'Lengkapi Akun - ' . (string) SettingWeb::query()->value('judul_web'),
            ],
        ];

        if (SettingWeb::query()->value('public_theme') !== PublicThemeRegistry::DEFAULT) {
            return Inertia::render('Public/Auth/CompleteGoogleSignup', $props);
        }

        return view('template.complete-google-signup', $props);
    }

    public function complete(Request $request): RedirectResponse
    {
        $pending = PendingGoogleSignup::get($request);

        if ($pending === null) {
            return redirect()->to(route('login'));
        }

        $validator = Validator::make($request->all(), [
            'no_wa' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                $normalized = WhatsappNumberNormalizer::normalize((string) $value);

                if ($normalized === null) {
                    $fail('Nomor WhatsApp harus berupa nomor Indonesia yang valid.');
                    return;
                }

                if (User::query()->where('no_wa', $normalized)->exists()) {
                    $fail('Nomor WhatsApp telah digunakan.');
                }
            }],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $normalizedWhatsapp = WhatsappNumberNormalizer::normalize((string) $request->input('no_wa'));

        if ($normalizedWhatsapp === null) {
            throw ValidationException::withMessages([
                'no_wa' => 'Nomor WhatsApp harus berupa nomor Indonesia yang valid.',
            ]);
        }

        // The Google identity may have been registered (or linked) while this visitor
        // was completing the form — reuse that account instead of creating a duplicate.
        $user = User::query()->where('google_id', $pending['sub'])->first();

        if (! $user) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$pending['email']])->first();
        }

        if ($user) {
            $updates = [];

            if (($pending['google_id_column'] ?? false) && blank($user->google_id)) {
                $updates['google_id'] = $pending['sub'];
            }

            if (($pending['google_avatar_column'] ?? false) && filled($pending['picture'])) {
                $updates['google_avatar'] = $pending['picture'];
            }

            if (blank($user->no_wa)) {
                $updates['no_wa'] = $normalizedWhatsapp;
            }

            if ($updates !== []) {
                $user->fill($updates);
                $user->save();
            }
        } else {
            $user = $this->createGoogleUser(
                sub: $pending['sub'],
                email: $pending['email'],
                name: $pending['name'],
                picture: $pending['picture'],
                noWa: $normalizedWhatsapp,
                uplink: $this->resolveUplink($request),
                hasGoogleColumn: (bool) ($pending['google_id_column'] ?? false),
                hasGoogleAvatarColumn: (bool) ($pending['google_avatar_column'] ?? false),
            );
        }

        PendingGoogleSignup::forget($request);

        if ($user->role === 'Admin') {
            throw ValidationException::withMessages([
                'error' => ['Akun admin tidak bisa login melalui halaman ini.'],
            ]);
        }

        if (filled($pending['redirect'] ?? null)) {
            $request->session()->put(self::LOGIN_REDIRECT_SESSION_KEY, $pending['redirect']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectAfterLogin($request);
    }

    private function createGoogleUser(
        string $sub,
        string $email,
        string $name,
        string $picture,
        string $noWa,
        ?string $uplink,
        bool $hasGoogleColumn,
        bool $hasGoogleAvatarColumn,
    ): User {
        $baseName = trim($name) !== '' ? trim($name) : Str::before($email, '@');
        $username = $this->generateUniqueUsername($baseName);

        $payload = [
            'name' => Str::limit($baseName, 255, ''),
            'username' => $username,
            'password' => Hash::make(Str::random(40)),
            'email' => $email,
            'balance' => 0,
            // Required by the schema (NOT NULL) and used as the buyer identity on orders.
            'no_wa' => $noWa,
            'role' => 'Member',
            'referral_code' => $this->generateUniqueReferralCode(),
            'uplink' => $uplink,
        ];

        if ($hasGoogleColumn) {
            $payload['google_id'] = $sub;
        }

        if ($hasGoogleAvatarColumn && $picture !== '') {
            $payload['google_avatar'] = $picture;
        }

        return User::query()->create($payload);
    }

    /**
     * Resolve the referrer the same way the normal sign-up form does: an explicit
     * `kode_referral` input, else the `referral_code` cookie set by TrackReferral.
     * Dropping this would silently cost the affiliate the signup they drove.
     */
    private function resolveUplink(Request $request): ?string
    {
        $referralCode = $request->input('kode_referral') ?? $request->cookie('referral_code');

        if (blank($referralCode)) {
            return null;
        }

        return User::query()
            ->where('referral_code', (string) $referralCode)
            ->value('username');
    }

    private function generateUniqueUsername(string $name): string
    {
        $sanitized = Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->value();

        $base = trim($sanitized) !== '' ? $sanitized : 'user';
        if (strlen($base) < 3) {
            $base = str_pad($base, 3, '0');
        }

        $candidate = $base;
        $sequence = 1;

        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = Str::limit($base, 240, '') . $sequence;
            $sequence++;
        }

        return $candidate;
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $code = 'REF-' . Str::upper(Str::random(6));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }

    private function resolveGoogleClientId(): string
    {
        try {
            if (Schema::hasTable('setting_webs') && Schema::hasColumn('setting_webs', 'google_client_id')) {
                $fromDatabase = trim((string) (SettingWeb::query()->value('google_client_id') ?? ''));
                if ($fromDatabase !== '') {
                    return $fromDatabase;
                }
            }
        } catch (\Throwable) {
            // Fallback to env/config when schema is not ready.
        }

        return trim((string) config('services.google.client_id', ''));
    }
}

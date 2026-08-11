<?php

namespace App\Http\Controllers;

use App\Models\SettingWeb;
use App\Models\User;
use App\Http\Controllers\Concerns\HandlesLoginRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            $user = $this->createGoogleUser(
                sub: $sub,
                email: $email,
                name: (string) ($tokenInfo['name'] ?? ''),
                picture: (string) ($tokenInfo['picture'] ?? ''),
                hasGoogleColumn: $hasGoogleColumn,
                hasGoogleAvatarColumn: $hasGoogleAvatarColumn,
            );
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

    private function createGoogleUser(
        string $sub,
        string $email,
        string $name,
        string $picture,
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
            'api_key' => Str::random(32),
            'balance' => 0,
            'no_wa' => null,
            'role' => 'Member',
            'referral_code' => $this->generateUniqueReferralCode(),
            'uplink' => null,
        ];

        if ($hasGoogleColumn) {
            $payload['google_id'] = $sub;
        }

        if ($hasGoogleAvatarColumn && $picture !== '') {
            $payload['google_avatar'] = $picture;
        }

        return User::query()->create($payload);
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

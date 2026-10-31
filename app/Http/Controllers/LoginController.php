<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAuthCaptchaRuntime;
use App\Http\Controllers\Concerns\HandlesLoginRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\SeoMetadataService;
use App\Models\Berita;
use App\Models\SettingWeb;
use App\Support\PublicThemeRegistry;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;

class LoginController extends Controller
{
    use ResolvesAuthCaptchaRuntime;
    use HandlesLoginRedirect;

    /**
     * Role yang boleh masuk lewat halaman login publik.
     *
     * `tenant_owner` WAJIB ada di sini. R1 menambahkan role itu ke enum `users`
     * supaya pemilik toko tidak lagi diberi role `Gold` (harga jual tenant
     * dihitung dari `harga_gold`), tapi allowlist ini tidak ikut diperbarui —
     * akibatnya pemilik toko tidak bisa login sama sekali: password benar,
     * tetap di-logout dengan pesan "Username / password mismatch".
     *
     * `Admin` sengaja TIDAK ada di daftar: admin ditolak eksplisit di atas dan
     * diarahkan ke panel Filament, bukan ke situs publik.
     */
    private const PUBLIC_LOGIN_ROLES = ['Member', 'Platinum', 'Gold', 'tenant_owner'];


    public function create(Request $request, SeoMetadataService $seoMetadataService)
    {
        $this->rememberLoginRedirect($request);
        $captchaRuntime = $this->getAuthCaptchaRuntime();

        $props = [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'captchaRuntime' => array_intersect_key($captchaRuntime, array_flip(['enabled', 'bypass', 'sitekey', 'is_active'])),
            'googleClientId' => $this->resolveGoogleClientId(),
            'meta' => $seoMetadataService->privatePage([
                'title' => 'Masuk - ' . (string) SettingWeb::query()->value('judul_web'),
            ], $request),
        ];

        if (! PublicThemeRegistry::rendersLegacyBlade(PublicThemeRegistry::activeForEnvironment())) {
            return Inertia::render('Public/Auth/Login', $props);
        }

        return view('template.login', $props);
    }

    public function store(Request $request)
    {
        $rules = [
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'two_factor_code' => 'nullable|regex:/^[0-9]{6}$/',
        ];

        if ($this->isAuthCaptchaEnabled()) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }

        $request->validate($rules, [
            'two_factor_code.regex' => 'Kode autentikator harus 6 digit angka.',
            'g-recaptcha-response.required' => 'Captcha wajib diverifikasi.',
            'g-recaptcha-response.captcha' => 'Verifikasi captcha gagal. Silakan coba lagi.',
        ]);

        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Cek apakah role pengguna adalah Admin
            if ($user->role === 'Admin') {
                Auth::logout();
                return redirect()->route('login')->withErrors(['error' => 'Username / password mismatch']);
            }

            // Pemilik toko punya dashboard sendiri di subdomain tokonya.
            // `route('dashboard')` di host platform menunjuk dashboard pembeli
            // (PublicDashboardPageController), bukan dashboard tenant — jadi
            // redirectAfterLogin() akan melempar owner ke halaman yang salah.
            if ($user->role === 'tenant_owner') {
                $this->verifyTwoFactorIfEnabled($user, $request);

                $request->session()->regenerate();

                $storeUrl = $this->storeDashboardUrl($user);

                if ($storeUrl === null) {
                    // Toko belum ada / subdomain kosong: tetap login, jangan
                    // gagalkan autentikasi hanya karena data tenant belum lengkap.
                    return $this->redirectAfterLogin($request);
                }

                return redirect()->away($storeUrl);
            }

            // Cek apakah role pengguna termasuk yang boleh login di situs publik.
            if (in_array($user->role, self::PUBLIC_LOGIN_ROLES, true)) {
                $this->verifyTwoFactorIfEnabled($user, $request);

                $request->session()->regenerate();
                return $this->redirectAfterLogin($request);
            } else {
                Auth::logout();
                return redirect()->route('login')->withErrors(['error' => 'Username / password mismatch']);
            }
        }

        throw ValidationException::withMessages([
            'error' => ['Username / password mismatch'],
        ]);
    }

    /**
     * Verifikasi kode autentikator kalau akun memakai 2FA.
     *
     * Dipakai dua jalur (tenant_owner dan pembeli) supaya aturan 2FA tidak
     * punya dua salinan yang bisa menyimpang — pemilik toko juga wajib 2FA
     * kalau akunnya mengaktifkannya.
     */
    private function verifyTwoFactorIfEnabled($user, Request $request): void
    {
        if (! filled($user->two_factor_secret)) {
            return;
        }

        $code = preg_replace('/\D+/', '', (string) $request->input('two_factor_code', ''));

        if (strlen($code) !== 6) {
            Auth::logout();

            throw ValidationException::withMessages([
                'two_factor_code' => ['Kode autentikator wajib diisi untuk akun ini.'],
            ]);
        }

        $google2fa = new Google2FA();

        if (! $google2fa->verifyKey((string) $user->two_factor_secret, $code, 2)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'two_factor_code' => ['Kode autentikator tidak valid.'],
            ]);
        }
    }

    /**
     * URL dashboard toko milik user, atau null kalau tokonya belum ada.
     *
     * Skema diambil dari `config('app.url')` — bukan diasumsikan `https` —
     * supaya redirect tidak menunjuk skema yang tidak dipakai situs ini.
     * `SESSION_DOMAIN` harus mencakup domain ini, kalau tidak owner akan
     * mendarat di dashboard toko dalam keadaan belum login.
     */
    private function storeDashboardUrl($user): ?string
    {
        $tenant = \App\Models\Tenant::query()
            ->where('owner_user_id', $user->id)
            ->first();

        $subdomain = trim((string) ($tenant?->subdomain ?? ''));

        if ($subdomain === '') {
            return null;
        }

        $appUrl = (string) config('app.url');
        $baseHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));

        if ($baseHost === '') {
            return null;
        }

        $scheme = (string) parse_url($appUrl, PHP_URL_SCHEME);
        $scheme = $scheme !== '' ? $scheme : 'https';

        // `$baseHost` bisa punya port (mis. `localhost:8000`) saat pengembangan;
        // port harus ikut di belakang host tenant, bukan di tengah subdomain.
        $port = parse_url($appUrl, PHP_URL_PORT);
        $authority = $subdomain . '.' . $baseHost . ($port !== null ? ':' . $port : '');

        return $scheme . '://' . $authority . '/dashboard';
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Gunakan Inertia::location() agar browser melakukan full page reload
        // ke homepage. Jika hanya pakai redirect() biasa, Inertia SPA akan
        // mencoba merender halaman non-Inertia sebagai komponen, menyebabkan
        // login modal ter-overlay di atas layout Reseller Hub yang masih hidup.
        return Inertia::location(url('/'));
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

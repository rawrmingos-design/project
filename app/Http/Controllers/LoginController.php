<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAuthCaptchaRuntime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Berita;
use App\Models\SettingWeb;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;

class LoginController extends Controller
{
    use ResolvesAuthCaptchaRuntime;

    public function create()
    {
        $captchaRuntime = $this->getAuthCaptchaRuntime();

        return view('template.login', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'captchaRuntime' => $captchaRuntime,
            'googleClientId' => $this->resolveGoogleClientId(),
        ]);
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

            // Cek apakah role pengguna adalah Member, Platinum, atau Gold
            if (in_array($user->role, ['Member', 'Platinum', 'Gold'])) {
                if (filled($user->two_factor_secret)) {
                    $code = preg_replace('/\D+/', '', (string) $request->input('two_factor_code', ''));
                    if (strlen($code) !== 6) {
                        Auth::logout();
                        throw ValidationException::withMessages([
                            'two_factor_code' => ['Kode autentikator wajib diisi untuk akun ini.'],
                        ]);
                    }

                    $google2fa = new Google2FA();
                    $isValidCode = $google2fa->verifyKey((string) $user->two_factor_secret, $code, 2);

                    if (! $isValidCode) {
                        Auth::logout();
                        throw ValidationException::withMessages([
                            'two_factor_code' => ['Kode autentikator tidak valid.'],
                        ]);
                    }
                }

                $request->session()->regenerate();
                return redirect()->route('dashboard');
            } else {
                Auth::logout();
                return redirect()->route('login')->withErrors(['error' => 'Username / password mismatch']);
            }
        }

        throw ValidationException::withMessages([
            'error' => ['Username / password mismatch'],
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
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

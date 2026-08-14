<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResolvesAuthCaptchaRuntime;
use App\Models\User;
use App\Models\SettingWeb;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Berita;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use App\Support\WhatsappNumberNormalizer;

class RegisterController extends Controller
{
    use ResolvesAuthCaptchaRuntime;

    public function create()
    {
        $captchaRuntime = $this->getAuthCaptchaRuntime();

        return view('template.register', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'captchaRuntime' => $captchaRuntime,
            'googleClientId' => $this->resolveGoogleClientId(),
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $rules = [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|min:3|unique:users,username|max:255',
            'password' => 'required|string|min:6|max:255',
            'passwordd' => 'required|string|min:6|same:password',
            'email' => 'required|email|max:255|unique:users,email',
            'no_wa' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                $normalized = WhatsappNumberNormalizer::normalize((string) $value);
                if ($normalized === null) {
                    $fail($attribute . ' harus berupa nomor Indonesia yang valid.');
                    return;
                }

                if (User::query()->where('no_wa', $normalized)->exists()) {
                    $fail($attribute . ' telah digunakan.');
                }
            }],
        ];

        if ($this->isAuthCaptchaEnabled()) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }

        $validator = Validator::make($request->all(), $rules, [
            'g-recaptcha-response.required' => 'Captcha wajib diverifikasi.',
            'g-recaptcha-response.captcha' => 'Verifikasi captcha gagal. Silakan coba lagi.',
            'passwordd.same' => 'Konfirmasi kata sandi harus sama dengan kata sandi.',
            'email.email' => 'Format email tidak valid.',
            'no_wa.regex' => 'Nomor WhatsApp harus berupa angka 10-20 digit.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Hash password
        $hashedPassword = Hash::make($request->password);

        // Simpan nomor WhatsApp dalam format canonical Indonesia.
        $no_wa = WhatsappNumberNormalizer::normalize((string) $request->no_wa);
        if ($no_wa === null) {
            return redirect()->back()->withErrors([
                'no_wa' => 'Nomor WhatsApp harus berupa nomor Indonesia yang valid.',
            ])->withInput();
        }

        // Generate Referral Code
        do {
            $referralCode = 'REF-' . Str::upper(Str::random(6));
        } while (User::where('referral_code', $referralCode)->exists());

        // Check Uplink (Referral)
        $uplink = null;
        $kodeReferral = $request->kode_referral ?? Cookie::get('referral_code');

        if ($kodeReferral) {
            $uplinkUser = User::where('referral_code', $kodeReferral)->first();
            if ($uplinkUser) {
                $uplink = $uplinkUser->username; 
            }
        }

        // Simpan data pengguna
        $user = new User();
        $user->name = htmlspecialchars($request->nama, ENT_QUOTES, 'UTF-8');
        $user->username = htmlspecialchars($request->username, ENT_QUOTES, 'UTF-8');
        $user->password = $hashedPassword;
        $user->email = htmlspecialchars($request->email, ENT_QUOTES, 'UTF-8');
        // Removed: $user->api_key = Str::random(32); 
        // API keys now generated only for approved resellers in reseller_integrations table
        $user->balance = 0;
        $user->no_wa = htmlspecialchars($no_wa, ENT_QUOTES, 'UTF-8');
        $user->role = 'Member';
        $user->referral_code = $referralCode;
        $user->uplink = $uplink;
        $user->save();

        return redirect(route('login'))->with('success', 'Berhasil mendaftar silahkan login menggunakan akun anda.');
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

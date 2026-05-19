@extends('template.template')

@section('custom_style')
<style>
    .auth-register-page {
        background: #18181b;
    }

    .auth-register-form-column {
        width: 100%;
    }

    @media (min-width: 768px) {
        .auth-register-form-column {
            width: 550px;
        }
    }

    .auth-register-close {
        background: #232324;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .auth-register-close:hover {
        background: #2f2f31;
    }

    .auth-register-input {
        height: 2.25rem;
        border-radius: 0.55rem;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: #383838;
        color: #fafaf9;
        font-size: 0.78rem;
    }

    .auth-register-input::placeholder {
        color: rgba(250, 250, 249, 0.48);
    }

    .auth-register-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 1px #f97316;
        outline: none;
    }

    .auth-register-input[readonly] {
        background: #2d2d30;
        color: #9ca3af;
        border-color: #3f3f46;
        pointer-events: none;
    }

    .auth-register-submit {
        background: linear-gradient(to top, #351b08 0%, #c2570c 50%, #f97316 100%);
        background-size: 200% 200%;
        background-position: 0% 0%;
        transition: background-position .35s ease, opacity .2s ease;
    }

    .auth-register-submit:hover {
        background-position: 100% 100%;
    }

    .auth-register-alert-error {
        background-color: #f43f5e;
    }

    .auth-register-copy {
        color: #e5e7eb;
    }

    .auth-register-link {
        color: #f97316;
    }

    .auth-register-link:hover {
        color: #fb923c;
    }

    .auth-register-outline-btn {
        border: 1px solid rgba(255, 255, 255, 0.16);
        color: #fafaf9;
        transition: background-color .2s ease;
    }

    .auth-register-outline-btn:hover {
        background-color: rgba(255, 255, 255, 0.08);
    }

    .auth-register-separator {
        background: rgba(255, 255, 255, 0.15);
    }

    .auth-register-separator-text {
        color: rgba(255, 255, 255, 0.75);
    }

    .auth-register-google-slot {
        display: flex;
        justify-content: center;
        width: 100%;
        min-height: 40px;
    }

    .auth-register-google-slot > div {
        margin: 0 auto;
    }

    .auth-register-captcha {
        margin-top: 0.25rem;
    }

    .auth-register-captcha > div {
        margin-inline: auto;
    }

    .btn-loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }

    .btn-loading::after {
        content: "";
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-top: -10px;
        margin-left: -10px;
        border: 2px solid transparent;
        border-top-color: #ffffff;
        border-right-color: #ffffff;
        border-radius: 50%;
        animation: auth-register-spin 0.8s linear infinite;
    }

    @keyframes auth-register-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
@endsection

@section('content')
<div class="auth-register-page relative flex min-h-screen text-white">
    <div class="absolute left-4 top-4 z-40">
        <a class="auth-register-close inline-flex h-9 w-9 items-center justify-center rounded-lg transition-colors" href="{{ route('home') }}" style="outline: none;" aria-label="Kembali ke beranda">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <div class="auth-register-form-column flex min-h-screen w-full flex-col items-center justify-start gap-5 px-4 pb-8 pt-20 sm:pb-10 sm:pt-24 md:justify-center md:gap-7 md:px-12 md:py-14 lg:gap-8 lg:px-20 lg:py-20">
        <div class="mx-auto w-full max-w-md space-y-4 sm:space-y-5 md:space-y-6 lg:mx-0">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-white">Daftar</h1>
                <p class="auth-register-copy mt-2 text-sm">Masukkan informasi pendaftaran yang valid.</p>
            </div>

            @if ($errors->has('error'))
                <div class="auth-register-alert-error rounded-md px-4 py-3 text-sm text-white">
                    <div>{{ $errors->first('error') }}</div>
                </div>
            @endif

            @if ($errors->any() && ! $errors->has('error'))
                <div class="auth-register-alert-error rounded-md px-4 py-3 text-sm text-white">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $isRegisterCaptchaEnabled = (bool) (($captchaRuntime['is_active'] ?? false) === true);
            @endphp

            <form id="registerForm" action="{{ route('post.register') }}" method="POST" class="space-y-4 md:space-y-5">
                @csrf

                <div class="space-y-2.5 sm:space-y-3">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="nama" class="block pb-2 text-xs font-medium text-white">Nama lengkap</label>
                            <input class="auth-register-input block w-full appearance-none px-3 disabled:cursor-not-allowed disabled:opacity-75" type="text" id="nama" autocomplete="name" placeholder="Nama lengkap" name="nama" required value="{{ old('nama') }}" />
                        </div>
                        <div>
                            <label for="username" class="block pb-2 text-xs font-medium text-white">Username</label>
                            <input class="auth-register-input block w-full appearance-none px-3 disabled:cursor-not-allowed disabled:opacity-75" type="text" id="username" autocomplete="username" placeholder="Username" name="username" required value="{{ old('username') }}" />
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block pb-2 text-xs font-medium text-white">Email</label>
                        <input class="auth-register-input block w-full appearance-none px-3 disabled:cursor-not-allowed disabled:opacity-75" type="email" id="email" autocomplete="email" placeholder="Email" name="email" required value="{{ old('email') }}" />
                    </div>

                    <div>
                        <label for="wa" class="block pb-2 text-xs font-medium text-white">No. WhatsApp</label>
                        <input class="auth-register-input block w-full appearance-none px-3 disabled:cursor-not-allowed disabled:opacity-75" type="text" inputmode="numeric" id="wa" autocomplete="tel" placeholder="No. WhatsApp" name="no_wa" required value="{{ old('no_wa') }}" />
                    </div>

                    <div>
                        <label for="kode_referral" class="block pb-2 text-xs font-medium text-white">Kode Referral (Opsional)</label>
                        <input
                            class="auth-register-input block w-full appearance-none px-3 disabled:cursor-not-allowed disabled:opacity-75"
                            type="text"
                            id="kode_referral"
                            placeholder="Kode Referral (Opsional)"
                            {{ request('ref') || \Illuminate\Support\Facades\Cookie::get('referral_code') ? 'readonly' : '' }}
                            name="kode_referral"
                            value="{{ old('kode_referral', request('kode_referral') ?? request('ref') ?? \Illuminate\Support\Facades\Cookie::get('referral_code')) }}"
                        />
                        @if(request('ref') || \Illuminate\Support\Facades\Cookie::get('referral_code'))
                            <span class="mt-1 block text-[10px] italic text-green-400">Kode referral otomatis diterapkan.</span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div x-data="{ isPassword: true }">
                            <label for="password" class="block pb-2 text-xs font-medium text-white">Kata sandi</label>
                            <div class="relative">
                                <input class="auth-register-input block w-full appearance-none px-3 pr-10 disabled:cursor-not-allowed disabled:opacity-75" :type="isPassword ? 'password' : 'text'" id="password" placeholder="Kata sandi" name="password" required />
                                <button type="button" class="absolute right-4 top-0 z-20 h-full text-white" @click="isPassword = !isPassword" aria-label="Toggle password">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4" :class="{ 'hidden': !isPassword, 'block': isPassword }">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"></path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4" :class="{ 'hidden': isPassword, 'block': !isPassword }">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div x-data="{ isPassword: true }">
                            <label for="passwordd" class="block pb-2 text-xs font-medium text-white">Konfirmasi kata sandi</label>
                            <div class="relative">
                                <input class="auth-register-input block w-full appearance-none px-3 pr-10 disabled:cursor-not-allowed disabled:opacity-75" :type="isPassword ? 'password' : 'text'" id="passwordd" placeholder="Konfirmasi kata sandi" name="passwordd" required />
                                <button type="button" class="absolute right-4 top-0 z-20 h-full text-white" @click="isPassword = !isPassword" aria-label="Toggle konfirmasi password">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4" :class="{ 'hidden': !isPassword, 'block': isPassword }">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"></path>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4" :class="{ 'hidden': isPassword, 'block': !isPassword }">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-2">
                    <input type="checkbox" class="mt-0.5 h-4 w-4 cursor-pointer rounded border-murky-600 bg-murky-700 text-primary-500 focus:ring-primary-500" id="tac" name="tac" />
                    <label for="tac" class="auth-register-copy select-none text-xs leading-5">
                        Saya setuju dengan
                        <a class="auth-register-link font-medium" href="/id/privacy-policy">Kebijakan Pribadi</a>
                        dan
                        <a class="auth-register-link font-medium" href="/id/terms-and-condition">Syarat dan Ketentuan</a>.
                    </label>
                </div>

                @if($isRegisterCaptchaEnabled)
                    <div class="auth-register-captcha">
                        {!! \Anhskohbo\NoCaptcha\Facades\NoCaptcha::display([
                            'data-theme' => 'dark',
                            'data-callback' => 'onRegisterCaptchaSuccess',
                            'data-expired-callback' => 'onRegisterCaptchaExpired',
                            'data-error-callback' => 'onRegisterCaptchaExpired',
                        ]) !!}
                    </div>
                    @error('g-recaptcha-response')
                        <p class="text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                @endif

                <div>
                    <button class="auth-register-submit relative inline-flex h-9 w-full items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50" id="btnRegister" disabled type="submit">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3" id="iconRegister" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-white">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"></path>
                            </svg>
                        </span>
                        <span id="textRegister">Daftar</span>
                    </button>
                </div>

                <div class="relative flex justify-center text-sm">
                    <span class="auth-register-copy text-sm">Sudah memiliki akun?</span>
                </div>

                <a class="auth-register-outline-btn inline-flex h-9 w-full items-center justify-center rounded-lg bg-transparent px-4 py-2 text-sm font-medium" href="{{ route('login') }}" style="outline: none;">
                    Masuk
                </a>
            </form>

            <div class="mx-auto flex w-full max-w-md flex-col gap-3 md:gap-4 lg:mx-0">
                <span class="flex w-auto items-center">
                    <span class="auth-register-separator h-px w-full"></span>
                    <span class="auth-register-separator-text whitespace-nowrap px-3 text-xs">Atau lanjutkan dengan</span>
                    <span class="auth-register-separator h-px w-full"></span>
                </span>
                <div class="auth-register-google-slot" id="googleRegisterButton"></div>
                <p id="googleRegisterHint" class="hidden text-center text-xs text-amber-300/90"></p>
                @if(blank($googleClientId))
                    <p class="text-center text-xs text-amber-300/90">Google login belum aktif. Isi <code>GOOGLE_CLIENT_ID</code> di environment.</p>
                @endif
            </div>

            <form id="googleRegisterForm" action="{{ route('auth.google') }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="credential" id="googleRegisterCredential" />
            </form>
        </div>
    </div>

    <div class="hidden flex-1 md:flex">
        <img alt="{{ $config->judul_web }}" src="{{ URL::asset('assets/image/register.jpg') }}" loading="lazy" decoding="async" class="h-screen w-full object-cover object-center" />
    </div>
</div>

@push('custom_script')
@if(filled($googleClientId))
<script src="https://accounts.google.com/gsi/client" async defer></script>
@endif
@if($isRegisterCaptchaEnabled)
    {!! \Anhskohbo\NoCaptcha\Facades\NoCaptcha::renderJs('id') !!}
@endif
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('registerForm');
        const btnRegister = document.getElementById('btnRegister');
        const textRegister = document.getElementById('textRegister');
        const iconRegister = document.getElementById('iconRegister');
        const googleButtonSlot = document.getElementById('googleRegisterButton');
        const googleForm = document.getElementById('googleRegisterForm');
        const googleCredentialInput = document.getElementById('googleRegisterCredential');
        const googleRegisterHint = document.getElementById('googleRegisterHint');
        const googleClientId = @json((string) ($googleClientId ?? ''));

        const showGoogleHint = function (message) {
            if (!googleRegisterHint) {
                return;
            }

            googleRegisterHint.textContent = message;
            googleRegisterHint.classList.remove('hidden');
        };

        const hideGoogleHint = function () {
            if (!googleRegisterHint) {
                return;
            }

            googleRegisterHint.textContent = '';
            googleRegisterHint.classList.add('hidden');
        };

        const initGoogleButton = function () {
            const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
            const hasSecureOrigin = window.location.protocol === 'https:' || isLocalhost;

            if (!hasSecureOrigin) {
                showGoogleHint('Google login butuh HTTPS atau localhost. Untuk dev, akses via http://localhost:8000.');
                return false;
            }

            if (
                googleClientId === '' ||
                !googleButtonSlot ||
                !googleForm ||
                !googleCredentialInput ||
                !window.google ||
                !window.google.accounts ||
                !window.google.accounts.id
            ) {
                if (googleClientId !== '' && (!window.google || !window.google.accounts || !window.google.accounts.id)) {
                    showGoogleHint('Library Google Sign-In belum siap. Coba refresh halaman ini.');
                }
                return false;
            }

            window.google.accounts.id.initialize({
                client_id: googleClientId,
                ux_mode: 'popup',
                context: 'signup',
                callback: function (response) {
                    if (!response || !response.credential) {
                        showGoogleHint('Google credential tidak diterima. Coba login ulang.');
                        return;
                    }

                    googleCredentialInput.value = response.credential;
                    googleForm.submit();
                },
            });

            window.google.accounts.id.renderButton(googleButtonSlot, {
                type: 'standard',
                theme: 'outline',
                size: 'large',
                shape: 'rectangular',
                text: 'signup_with',
                logo_alignment: 'left',
                width: 280,
            });

            hideGoogleHint();
            return true;
        };

        if (!initGoogleButton()) {
            let retries = 0;
            const timer = window.setInterval(function () {
                retries += 1;
                if (initGoogleButton() || retries >= 40) {
                    window.clearInterval(timer);
                }
            }, 125);
        }

        if (!form || !btnRegister) {
            return;
        }

        window.onRegisterCaptchaSuccess = function () {
            checkValidity();
        };

        window.onRegisterCaptchaExpired = function () {
            checkValidity();
        };

        const isCaptchaEnabled = @json($isRegisterCaptchaEnabled);

        function checkValidity() {
            const nama = (document.getElementById('nama')?.value || '').trim();
            const username = (document.getElementById('username')?.value || '').trim();
            const email = (document.getElementById('email')?.value || '').trim();
            const wa = (document.getElementById('wa')?.value || '').trim();
            const password = document.getElementById('password')?.value || '';
            const passwordd = document.getElementById('passwordd')?.value || '';
            const agreement = document.getElementById('tac')?.checked || false;
            const captchaValue = form.querySelector('[name="g-recaptcha-response"]')?.value || '';
            const isCaptchaValid = !isCaptchaEnabled || captchaValue.trim() !== '';

            const isEmailValid = email.includes('@');
            const isWaValid = wa.length >= 10 && /^[0-9]+$/.test(wa);

            const isValid =
                nama !== '' &&
                username.length >= 3 &&
                isEmailValid &&
                isWaValid &&
                password.length >= 6 &&
                password === passwordd &&
                isCaptchaValid &&
                agreement === true;

            btnRegister.disabled = !isValid;
        }

        form.addEventListener('input', checkValidity);
        form.addEventListener('change', checkValidity);
        setTimeout(checkValidity, 250);

        form.addEventListener('submit', function () {
            btnRegister.disabled = true;
            btnRegister.classList.add('btn-loading');

            if (textRegister) {
                textRegister.textContent = 'Memproses...';
            }

            if (iconRegister) {
                iconRegister.style.display = 'none';
            }
        });
    });
</script>
@endpush
@endsection

@extends('template.template')

@section('custom_style')
<style>
    .auth-login-page {
        background: #18181b;
    }

    .auth-login-form-column {
        width: 100%;
    }

    @media (min-width: 768px) {
        .auth-login-form-column {
            width: 550px;
        }
    }

    .auth-login-close {
        background: #232324;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .auth-login-close:hover {
        background: #2f2f31;
    }

    .auth-login-input {
        height: 2.25rem;
        border-radius: 0.55rem;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: #383838;
        color: #fafaf9;
        font-size: 0.78rem;
    }

    .auth-login-input::placeholder {
        color: rgba(250, 250, 249, 0.48);
    }

    .auth-login-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 1px #f97316;
        outline: none;
    }

    .auth-login-submit {
        background: linear-gradient(to top, #351b08 0%, #c2570c 50%, #f97316 100%);
        background-size: 200% 200%;
        background-position: 0% 0%;
        transition: background-position .35s ease, opacity .2s ease;
    }

    .auth-login-submit:hover {
        background-position: 100% 100%;
    }

    .auth-login-alert-success {
        background-color: #34d399;
    }

    .auth-login-alert-error {
        background-color: #f43f5e;
    }

    .auth-login-copy {
        color: #e5e7eb;
    }

    .auth-login-link {
        color: #f97316;
    }

    .auth-login-link:hover {
        color: #fb923c;
    }

    .auth-login-register {
        border: 1px solid rgba(255, 255, 255, 0.16);
        color: #fafaf9;
        transition: background-color .2s ease;
    }

    .auth-login-register:hover {
        background-color: rgba(255, 255, 255, 0.08);
    }

    .auth-login-separator {
        background: rgba(255, 255, 255, 0.15);
    }

    .auth-login-separator-text {
        color: rgba(255, 255, 255, 0.75);
    }

    .auth-login-social-btn {
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.04);
        color: #fafaf9;
        opacity: 1;
    }

    .auth-login-social-btn:disabled {
        cursor: not-allowed;
    }

    .auth-login-google-slot {
        display: flex;
        justify-content: center;
        width: 100%;
        min-height: 40px;
    }

    .auth-login-google-slot > div {
        margin: 0 auto;
    }

    .auth-login-captcha {
        margin-top: 0.25rem;
    }

    .auth-login-captcha > div {
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
        animation: auth-login-spin 0.8s linear infinite;
    }

    @keyframes auth-login-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
@endsection

@section('content')
<div class="auth-login-page relative flex min-h-screen text-white">
    <div class="absolute left-4 top-4 z-40">
        <a class="auth-login-close inline-flex h-9 w-9 items-center justify-center rounded-lg transition-colors" href="{{ route('home') }}" style="outline: none;" aria-label="Kembali ke beranda">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <div class="auth-login-form-column flex min-h-screen w-full flex-col items-center justify-start gap-5 px-4 pb-8 pt-20 sm:pb-10 sm:pt-24 md:justify-center md:gap-7 md:px-12 md:py-14 lg:gap-8 lg:px-20 lg:py-20">
        <div class="mx-auto w-full max-w-md space-y-4 sm:space-y-5 md:space-y-6 lg:mx-0">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-white">Masuk</h1>
                <p class="auth-login-copy mt-2 text-sm">Masuk dengan akun yang telah Kamu daftarkan.</p>
            </div>

            @if(session('error') || $errors->has('error'))
                <div class="auth-login-alert-error rounded-md px-4 py-3 text-sm text-white">
                    <div>{{ session('error') ?? $errors->first('error') }}</div>
                </div>
            @endif

            @if(session('success'))
                <div class="auth-login-alert-success rounded-md px-4 py-3 text-sm text-white">
                    <div>{{ session('success') }}</div>
                </div>
            @endif

            @if ($errors->any() && ! $errors->has('error'))
                <div class="auth-login-alert-error rounded-md px-4 py-3 text-sm text-white">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $isLoginCaptchaEnabled = (bool) (($captchaRuntime['is_active'] ?? false) === true);
            @endphp

            <form id="loginForm" action="{{ route('post.login') }}" method="POST" class="space-y-4 md:space-y-5">
                @csrf
                <input type="hidden" name="remember" value="true" />

                <div class="space-y-2.5 sm:space-y-3">
                    <div>
                        <label for="username" class="block py-2 text-xs font-medium text-white">Username</label>
                        <div class="flex flex-col items-start">
                            <input class="auth-login-input block w-full appearance-none px-3 disabled:cursor-not-allowed disabled:opacity-75" type="text" id="username" autocomplete="username" placeholder="Username" name="username" required value="{{ old('username') }}" />
                        </div>
                    </div>

                    <div x-data="{ isPassword: true }">
                        <label for="password" class="block py-2 text-xs font-medium text-white">Kata sandi</label>
                        <div class="relative">
                            <div class="flex flex-col items-start">
                                <input class="auth-login-input block w-full appearance-none px-3 disabled:cursor-not-allowed disabled:opacity-75" :type="isPassword ? 'password' : 'text'" id="password" autocomplete="current-password" placeholder="Kata sandi" name="password" />
                            </div>
                            <button type="button" class="absolute right-4 top-0 z-20 h-full text-white" @click="isPassword = !isPassword" aria-label="Toggle password">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4" :class="{'hidden': !isPassword, 'block': isPassword}">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"></path>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-4 w-4" :class="{'hidden': isPassword, 'block': !isPassword}">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="two_factor_code" class="block py-2 text-xs font-medium text-white">Kode Authenticator (jika aktif)</label>
                        <div class="flex flex-col items-start">
                            <input class="auth-login-input block w-full appearance-none px-3 disabled:cursor-not-allowed disabled:opacity-75" type="text" id="two_factor_code" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" placeholder="6 digit kode" name="two_factor_code" value="{{ old('two_factor_code') }}" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label for="remember-me" class="auth-login-copy flex cursor-pointer items-center gap-2 text-xs">
                        <input type="checkbox" class="h-4 w-4 rounded border border-murky-600 bg-murky-700 text-primary-500 focus:ring-primary-500" id="remember-me" name="rememberMe" />
                        Ingat akun ku
                    </label>
                    <a class="auth-login-link text-xs font-medium" href="{{ route('forgot') }}">Lupa kata sandi mu?</a>
                </div>

                @if($isLoginCaptchaEnabled)
                    <div class="auth-login-captcha">
                        {!! \Anhskohbo\NoCaptcha\Facades\NoCaptcha::display([
                            'data-theme' => 'dark',
                            'data-callback' => 'onLoginCaptchaSuccess',
                            'data-expired-callback' => 'onLoginCaptchaExpired',
                            'data-error-callback' => 'onLoginCaptchaExpired',
                        ]) !!}
                    </div>
                    @error('g-recaptcha-response')
                        <p class="text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                @endif

                <div>
                    <button class="auth-login-submit group relative inline-flex h-9 w-full items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50" id="btnMasuk" disabled type="submit" name="tombol" value="submit">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3" id="iconMasuk" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-5 w-5 text-white transition-colors">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path>
                            </svg>
                        </span>
                        <span id="textMasuk">Masuk</span>
                    </button>
                </div>

                <div class="relative flex justify-center text-sm">
                    <span class="auth-login-copy text-sm">Belum memiliki akun?</span>
                </div>

                <a class="auth-login-register inline-flex h-9 w-full items-center justify-center rounded-lg bg-transparent px-4 py-2 text-sm font-medium" href="{{ route('register') }}" style="outline: none;">
                    Daftar
                </a>
            </form>

            <div class="mx-auto flex w-full max-w-md flex-col gap-3 md:gap-4 lg:mx-0">
                <span class="flex w-auto items-center">
                    <span class="auth-login-separator h-px w-full"></span>
                    <span class="auth-login-separator-text whitespace-nowrap px-3 text-xs">Atau lanjutkan dengan</span>
                    <span class="auth-login-separator h-px w-full"></span>
                </span>
                <div class="auth-login-google-slot" id="googleLoginButton"></div>
                <p id="googleLoginHint" class="hidden text-center text-xs text-amber-300/90"></p>
                @if(blank($googleClientId))
                    <p class="text-center text-xs text-amber-300/90">Google login belum aktif. Isi <code>GOOGLE_CLIENT_ID</code> di environment.</p>
                @endif
            </div>

            <form id="googleLoginForm" action="{{ route('auth.google') }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="credential" id="googleLoginCredential" />
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
@if($isLoginCaptchaEnabled)
    {!! \Anhskohbo\NoCaptcha\Facades\NoCaptcha::renderJs('id') !!}
@endif
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('loginForm');
        const btnMasuk = document.getElementById('btnMasuk');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const googleButtonSlot = document.getElementById('googleLoginButton');
        const googleForm = document.getElementById('googleLoginForm');
        const googleCredentialInput = document.getElementById('googleLoginCredential');
        const googleLoginHint = document.getElementById('googleLoginHint');
        const googleClientId = @json((string) ($googleClientId ?? ''));

        const showGoogleHint = function (message) {
            if (!googleLoginHint) {
                return;
            }

            googleLoginHint.textContent = message;
            googleLoginHint.classList.remove('hidden');
        };

        const hideGoogleHint = function () {
            if (!googleLoginHint) {
                return;
            }

            googleLoginHint.textContent = '';
            googleLoginHint.classList.add('hidden');
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
                context: 'signin',
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
                text: 'signin_with',
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

        if (!form || !btnMasuk || !usernameInput || !passwordInput) {
            return;
        }

        window.onLoginCaptchaSuccess = function () {
            checkValidity();
        };

        window.onLoginCaptchaExpired = function () {
            checkValidity();
        };

        const isCaptchaEnabled = @json($isLoginCaptchaEnabled);

        function checkValidity() {
            const hasCredential = usernameInput.value.trim() !== '' && passwordInput.value.trim() !== '';

            const captchaValue = form.querySelector('[name="g-recaptcha-response"]')?.value || '';
            const isCaptchaValid = !isCaptchaEnabled || captchaValue.trim() !== '';

            if (hasCredential && isCaptchaValid) {
                btnMasuk.removeAttribute('disabled');
            } else {
                btnMasuk.setAttribute('disabled', 'true');
            }
        }

        usernameInput.addEventListener('input', checkValidity);
        passwordInput.addEventListener('input', checkValidity);
        checkValidity();

        form.addEventListener('submit', function() {
            btnMasuk.classList.add('btn-loading');
            btnMasuk.setAttribute('disabled', 'true');
            const iconMasuk = document.getElementById('iconMasuk');
            if (iconMasuk) {
                iconMasuk.style.display = 'none';
            }
        });
    });
</script>
@endpush
@endsection

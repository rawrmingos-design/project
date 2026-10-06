@extends('template.template')

@push('head_meta')
    <meta name="referrer" content="no-referrer">
@endpush

@section('custom_style')
<style>
    .auth-reset-page {
        min-height: 100vh;
        align-items: flex-start;
        justify-content: center;
        background: #121212;
        color: #f5f5f5;
    }

    .auth-reset-form-column {
        width: min(100%, 430px);
        min-height: 100vh;
        justify-content: center;
        padding: 44px 18px 24px;
    }

    .auth-reset-form-column > div {
        width: 100%;
        max-width: 430px;
        padding: 26px 26px 24px;
        border: 1px solid #2f2f2f;
        border-radius: 16px;
        background: #1e1e1e;
    }

    .auth-reset-form-column h1 {
        font-size: 24px;
        line-height: 1.2;
    }

    .auth-reset-copy {
        color: #9c9c9c;
    }

    .auth-reset-close {
        background: #232324;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .auth-reset-close:hover {
        background: #2f2f31;
    }

    .auth-reset-label {
        display: block;
        padding: 8px 0;
        font-size: 12px;
        font-weight: 500;
        color: #ffffff;
    }

    .auth-reset-input {
        height: 44px;
        width: 100%;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: #383838;
        color: #fafaf9;
        font-size: 13.5px;
        padding: 0 12px;
    }

    .auth-reset-input::placeholder {
        color: rgba(250, 250, 249, 0.48);
    }

    .auth-reset-input:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 1px #f97316;
        outline: none;
    }

    .auth-reset-submit {
        background: linear-gradient(to top, #351b08 0%, #c2570c 50%, #f97316 100%);
        background-size: 200% 200%;
        background-position: 0% 0%;
        transition: background-position .35s ease, opacity .2s ease;
    }

    .auth-reset-submit:hover {
        background-position: 100% 100%;
    }

    .auth-reset-alert-error {
        background-color: #f43f5e;
    }

    .auth-reset-link {
        color: #f97316;
    }

    .auth-reset-link:hover {
        color: #fb923c;
    }
</style>
@endsection

@section('content')
<div class="auth-reset-page relative flex min-h-screen text-white">
    <div class="absolute left-4 top-4 z-40">
        <a class="auth-reset-close inline-flex h-9 w-9 items-center justify-center rounded-lg transition-colors" href="{{ route('home') }}" style="outline: none;" aria-label="Kembali ke beranda">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"></path>
            </svg>
        </a>
    </div>

    <div class="auth-reset-form-column flex min-h-screen w-full flex-col items-center justify-start gap-5 px-4 pb-8 pt-20 sm:pb-10 sm:pt-24 md:justify-center md:gap-7 md:px-12 md:py-14 lg:gap-8 lg:px-20 lg:py-20">
        <div class="mx-auto w-full max-w-md space-y-4 sm:space-y-5 md:space-y-6 lg:mx-0">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-white">Buat Kata Sandi Baru</h1>
                <p class="auth-reset-copy mt-2 text-sm">Gunakan kata sandi baru dengan minimal 12 karakter.</p>
            </div>

            @if($invalidLink || $errors->any())
                <div class="auth-reset-alert-error rounded-md px-4 py-3 text-sm text-white" role="alert">
                    <div>{{ $errors->first('email', 'Tautan reset tidak valid atau telah kedaluwarsa.') }}</div>
                </div>
            @endif

            @if(! $invalidLink)
                <form action="{{ route('password.update') }}" method="POST" class="space-y-4 md:space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="space-y-2.5 sm:space-y-3">
                        <div>
                            <label for="password" class="auth-reset-label">Kata Sandi Baru</label>
                            <input class="auth-reset-input block appearance-none disabled:cursor-not-allowed disabled:opacity-75" type="password" id="password" name="password" autocomplete="new-password" placeholder="Minimal 12 karakter" minlength="12" required>
                        </div>
                        <div>
                            <label for="password_confirmation" class="auth-reset-label">Konfirmasi Kata Sandi Baru</label>
                            <input class="auth-reset-input block appearance-none disabled:cursor-not-allowed disabled:opacity-75" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi kata sandi baru" minlength="12" required>
                        </div>
                    </div>

                    <button class="auth-reset-submit group relative inline-flex h-9 w-full items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-50" type="submit">
                        Simpan Kata Sandi Baru
                    </button>
                </form>
            @else
                <div class="pt-2">
                    <a class="auth-reset-link text-sm font-medium" href="{{ route('forgot') }}">Minta tautan reset baru</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

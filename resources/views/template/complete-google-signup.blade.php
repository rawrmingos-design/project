@extends('template.template')

@section('custom_style')
<style>
    .auth-complete-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 44px 18px;
        background: #121212;
        color: #f5f5f5;
    }

    .auth-complete-card {
        width: 100%;
        max-width: 430px;
        padding: 26px 26px 24px;
        border: 1px solid #2f2f2f;
        border-radius: 16px;
        background: #1e1e1e;
    }

    .auth-complete-card h1 {
        margin: 0 0 6px;
        font-size: 22px;
        font-weight: 700;
    }

    .auth-complete-card p.auth-complete-sub {
        margin: 0 0 18px;
        font-size: 13px;
        color: #b5b5b5;
        line-height: 1.5;
    }

    .auth-complete-identity {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
        padding: 12px;
        border: 1px solid #2f2f2f;
        border-radius: 12px;
        background: #161616;
    }

    .auth-complete-identity img {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        object-fit: cover;
    }

    .auth-complete-identity strong {
        display: block;
        font-size: 14px;
    }

    .auth-complete-identity span {
        display: block;
        font-size: 12px;
        color: #9c9c9c;
        word-break: break-all;
    }

    .auth-complete-label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        color: #d5d5d5;
    }

    .auth-complete-input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #3a3a3a;
        border-radius: 10px;
        background: #141414;
        color: #f5f5f5;
        font-size: 14px;
    }

    .auth-complete-hint {
        margin: 8px 0 16px;
        font-size: 12px;
        color: #9c9c9c;
        line-height: 1.5;
    }

    .auth-complete-submit {
        width: 100%;
        padding: 12px 16px;
        border: 0;
        border-radius: 10px;
        background: #f97316;
        color: #101010;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
    }

    .auth-complete-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .auth-complete-error {
        margin-bottom: 14px;
        padding: 10px 12px;
        border: 1px solid #7f1d1d;
        border-radius: 10px;
        background: #2a1212;
        color: #fca5a5;
        font-size: 13px;
    }

    .auth-complete-back {
        margin-top: 16px;
        font-size: 13px;
        text-align: center;
    }

    .auth-complete-back a {
        color: #fb923c;
    }
</style>
@endsection

@section('content')
<div class="auth-complete-page">
    <div class="auth-complete-card">
        <h1>Satu langkah lagi</h1>
        <p class="auth-complete-sub">Akun Google kamu sudah terverifikasi. Masukkan nomor WhatsApp untuk mengaktifkan akun.</p>

        @if (($avatar ?? '') !== '' || ($name ?? '') !== '' || ($email ?? '') !== '')
            <div class="auth-complete-identity">
                @if (($avatar ?? '') !== '')
                    <img src="{{ $avatar }}" alt="" referrerpolicy="no-referrer" />
                @endif
                <div>
                    @if (($name ?? '') !== '')
                        <strong>{{ $name }}</strong>
                    @endif
                    @if (($email ?? '') !== '')
                        <span>{{ $email }}</span>
                    @endif
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="auth-complete-error">{{ $errors->first() }}</div>
        @endif

        <form id="googleCompleteForm" action="{{ route('auth.google.complete.post') }}" method="POST">
            @csrf
            <label class="auth-complete-label" for="completeWa">Nomor WhatsApp</label>
            <input
                class="auth-complete-input"
                type="tel"
                inputmode="numeric"
                id="completeWa"
                name="no_wa"
                autocomplete="tel"
                placeholder="Contoh: 0812xxxxxxx"
                value="{{ old('no_wa') }}"
                required
                autofocus
            />
            <p class="auth-complete-hint">Nomor ini dipakai untuk notifikasi transaksi dan dihubungi jika terjadi masalah.</p>
            <button class="auth-complete-submit" type="submit">Aktifkan Akun</button>
        </form>

        <p class="auth-complete-back">Salah akun? <a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
    </div>
</div>
@endsection

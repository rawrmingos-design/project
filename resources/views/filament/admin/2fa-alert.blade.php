@if(auth()->user() && !auth()->user()->two_factor_secret)
    <div class="fil-admin-2fa-alert" role="status" aria-live="polite">
        <div class="fil-admin-2fa-alert__content">
            <div class="fil-admin-2fa-alert__icon-wrap" aria-hidden="true">
                <x-heroicon-o-exclamation-triangle class="fil-admin-2fa-alert__icon" />
            </div>
            <span class="fil-admin-2fa-alert__text">
                Keamanan akun Anda belum maksimal. Aktifkan 2FA (Two-Factor Authentication) sekarang.
            </span>
        </div>

        <a href="/profile" class="fil-admin-2fa-alert__action">
            Aktifkan Sekarang &rarr;
        </a>
    </div>
@endif

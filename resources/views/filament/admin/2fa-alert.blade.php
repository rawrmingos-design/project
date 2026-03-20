@if(auth()->user() && !auth()->user()->two_factor_secret)
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 16px; font-size: 14px; font-weight: 500; color: #d97706; background-color: #fffbeb; border-radius: 6px; margin-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 24px; height: 24px;">
                <x-heroicon-o-exclamation-triangle style="width: 24px; height: 24px;" />
            </div>
            <span>
                Keamanan akun Anda belum maksimal. Aktifkan 2FA (Two-Factor Authentication) sekarang.
            </span>
        </div>
        
        <a href="/profile" style="text-decoration: underline; color: #d97706;">
            Aktifkan Sekarang &rarr;
        </a>
    </div>
@endif


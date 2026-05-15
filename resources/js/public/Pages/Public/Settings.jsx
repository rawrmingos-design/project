import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import UserDashboardSidebar from '../../Components/UserDashboardSidebar';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload || {}),
    });

    const json = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = json?.message || 'Terjadi kesalahan. Silakan coba lagi.';
        throw new Error(message);
    }

    return json;
}

export default function Settings({ meta, settingsPage }) {
    const profile = settingsPage?.profile || {};
    const oauth = settingsPage?.oauth || {};
    const links = settingsPage?.links || {};

    const profileForm = useForm({
        name: profile.name || '',
        username: profile.username || '',
        no_wa: profile.phone || '',
    });

    const passwordForm = useForm({
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
    });
    const apiKeyForm = useForm({});

    const [notice, setNotice] = useState(settingsPage?.flash?.success || settingsPage?.flash?.error || '');
    const [noticeTone, setNoticeTone] = useState(settingsPage?.flash?.success ? 'success' : 'error');
    const [twoFactorEnabled, setTwoFactorEnabled] = useState(Boolean(settingsPage?.twoFactor?.enabled));
    const [twoFactorDraft, setTwoFactorDraft] = useState(null);
    const [twoFactorCode, setTwoFactorCode] = useState('');
    const [disablePassword, setDisablePassword] = useState('');
    const [disableCode, setDisableCode] = useState('');
    const [recoveryCodes, setRecoveryCodes] = useState([]);
    const [twoFactorBusy, setTwoFactorBusy] = useState(false);

    const showNotice = (message, tone = 'success') => {
        setNotice(message);
        setNoticeTone(tone);
    };

    const submitProfile = (event) => {
        event.preventDefault();
        profileForm.post('/id/settings', {
            preserveScroll: true,
            onSuccess: () => showNotice('Profil berhasil diperbarui.', 'success'),
            onError: () => showNotice('Gagal memperbarui profil. Cek data yang kamu isi.', 'error'),
        });
    };

    const submitPassword = (event) => {
        event.preventDefault();
        passwordForm.post('/id/settings/change-password', {
            preserveScroll: true,
            onSuccess: () => {
                passwordForm.reset();
                showNotice('Kata sandi berhasil diperbarui.', 'success');
            },
            onError: () => showNotice('Gagal memperbarui kata sandi.', 'error'),
        });
    };

    const regenerateApiKey = () => {
        apiKeyForm.post('/id/settings/api-key/regenerate', {
            preserveScroll: true,
            onSuccess: () => showNotice('API Key berhasil dibuat ulang.', 'success'),
            onError: () => showNotice('Gagal membuat ulang API Key.', 'error'),
        });
    };

    const copyApiKey = async () => {
        if (!profile.apiKey) {
            showNotice('API Key belum tersedia.', 'error');
            return;
        }

        try {
            await navigator.clipboard.writeText(profile.apiKey);
            showNotice('API Key berhasil disalin.', 'success');
        } catch (error) {
            showNotice('Clipboard tidak tersedia di browser ini.', 'error');
        }
    };

    const setupTwoFactor = async () => {
        setTwoFactorBusy(true);
        try {
            const payload = await postJson('/id/settings/2fa/setup', {});
            setTwoFactorDraft(payload?.data || null);
            setTwoFactorCode('');
            setRecoveryCodes([]);
            showNotice(payload?.message || 'Setup 2FA siap, silakan verifikasi kode.', 'success');
        } catch (error) {
            showNotice(error.message, 'error');
        } finally {
            setTwoFactorBusy(false);
        }
    };

    const enableTwoFactor = async () => {
        setTwoFactorBusy(true);
        try {
            const payload = await postJson('/id/settings/2fa/enable', { code: twoFactorCode });
            setTwoFactorEnabled(true);
            setTwoFactorDraft(null);
            setTwoFactorCode('');
            setRecoveryCodes(Array.isArray(payload?.data?.recovery_codes) ? payload.data.recovery_codes : []);
            showNotice(payload?.message || '2FA berhasil diaktifkan.', 'success');
        } catch (error) {
            showNotice(error.message, 'error');
        } finally {
            setTwoFactorBusy(false);
        }
    };

    const disableTwoFactor = async () => {
        setTwoFactorBusy(true);
        try {
            const payload = await postJson('/id/settings/2fa/disable', {
                current_password: disablePassword,
                code: disableCode,
            });
            setTwoFactorEnabled(false);
            setTwoFactorDraft(null);
            setRecoveryCodes([]);
            setDisablePassword('');
            setDisableCode('');
            showNotice(payload?.message || '2FA berhasil dinonaktifkan.', 'success');
        } catch (error) {
            showNotice(error.message, 'error');
        } finally {
            setTwoFactorBusy(false);
        }
    };

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-dashboard-page public-settings-page">
                <div className="public-shell">
                    <div className="public-dashboard public-settings-shell">
                        <UserDashboardSidebar links={links} />

                        <main className="public-dashboard-main public-settings-main">
                            <header className="public-settings-heading">
                                <h1>Pengaturan</h1>
                                <p>Kelola profil akun, keamanan kata sandi, dan Two Factor Authentication.</p>
                            </header>

                            {notice ? (
                                <div className={`public-settings-notice ${noticeTone === 'success' ? 'is-success' : 'is-error'}`}>
                                    {notice}
                                </div>
                            ) : null}

                            <section className="public-settings-card">
                                <h2>Profil</h2>
                                <p>Informasi ini bersifat rahasia, jadi berhati-hatilah dengan apa yang kamu bagikan.</p>

                                <form className="public-settings-form" onSubmit={submitProfile}>
                                    <label>
                                        <span>Nama kamu</span>
                                        <input
                                            type="text"
                                            value={profileForm.data.name}
                                            onChange={(event) => profileForm.setData('name', event.target.value)}
                                            autoComplete="name"
                                        />
                                        {profileForm.errors.name ? <small>{profileForm.errors.name}</small> : null}
                                    </label>

                                    <label>
                                        <span>Username</span>
                                        <input
                                            type="text"
                                            value={profileForm.data.username}
                                            onChange={(event) => profileForm.setData('username', event.target.value)}
                                            autoComplete="username"
                                        />
                                        {profileForm.errors.username ? <small>{profileForm.errors.username}</small> : null}
                                    </label>

                                    <label>
                                        <span>Alamat Email</span>
                                        <input type="email" value={profile.email || ''} readOnly disabled />
                                    </label>

                                    <label>
                                        <span>No. Handphone</span>
                                        <input
                                            type="text"
                                            value={profileForm.data.no_wa}
                                            onChange={(event) => profileForm.setData('no_wa', event.target.value)}
                                            inputMode="numeric"
                                            autoComplete="tel"
                                        />
                                        {profileForm.errors.no_wa ? <small>{profileForm.errors.no_wa}</small> : null}
                                    </label>

                                    <button type="submit" disabled={profileForm.processing}>
                                        {profileForm.processing ? 'Menyimpan...' : 'Ubah Profil'}
                                    </button>
                                </form>
                            </section>

                            <section className="public-settings-card">
                                <h2>API Key</h2>
                                <p>
                                    Informasi ini bersifat rahasia. Simpan API Key dengan aman, lalu perbarui integrasi kamu jika API Key diganti.
                                </p>

                                <div className="public-settings-api">
                                    <div className="public-settings-api__value">{profile.apiKey || '-'}</div>
                                    <div className="public-settings-api__actions">
                                        <button type="button" className="is-secondary" onClick={copyApiKey}>
                                            Salin API Key
                                        </button>
                                        <button type="button" onClick={regenerateApiKey} disabled={apiKeyForm.processing}>
                                            {apiKeyForm.processing ? 'Memproses...' : 'Buat Ulang API Key'}
                                        </button>
                                    </div>
                                </div>
                            </section>

                            <section className="public-settings-card">
                                <h2>Ubah Kata Sandi</h2>
                                <p>Pastikan kamu mengingat kata sandi baru kamu sebelum mengubahnya.</p>

                                <form className="public-settings-form public-settings-form--password" onSubmit={submitPassword}>
                                    <label>
                                        <span>Kata Sandi Saat Ini</span>
                                        <input
                                            type="password"
                                            value={passwordForm.data.current_password}
                                            onChange={(event) => passwordForm.setData('current_password', event.target.value)}
                                            autoComplete="current-password"
                                        />
                                        {passwordForm.errors.current_password ? <small>{passwordForm.errors.current_password}</small> : null}
                                    </label>

                                    <label>
                                        <span>Kata Sandi Baru</span>
                                        <input
                                            type="password"
                                            value={passwordForm.data.new_password}
                                            onChange={(event) => passwordForm.setData('new_password', event.target.value)}
                                            autoComplete="new-password"
                                        />
                                        {passwordForm.errors.new_password ? <small>{passwordForm.errors.new_password}</small> : null}
                                    </label>

                                    <label>
                                        <span>Konfirmasi Kata Sandi Baru</span>
                                        <input
                                            type="password"
                                            value={passwordForm.data.new_password_confirmation}
                                            onChange={(event) => passwordForm.setData('new_password_confirmation', event.target.value)}
                                            autoComplete="new-password"
                                        />
                                    </label>

                                    <button type="submit" disabled={passwordForm.processing}>
                                        {passwordForm.processing ? 'Memproses...' : 'Ubah Kata Sandi'}
                                    </button>
                                </form>
                            </section>

                            <section className="public-settings-card">
                                <h2>Two Factor Authentication</h2>
                                <p>
                                    {twoFactorEnabled
                                        ? '2FA sudah aktif. Kamu akan diminta kode authenticator saat login dengan username/password.'
                                        : 'Aktifkan 2FA untuk menambah keamanan akun kamu.'}
                                </p>

                                {!twoFactorEnabled ? (
                                    <div className="public-settings-2fa">
                                        {!twoFactorDraft ? (
                                            <button type="button" onClick={setupTwoFactor} disabled={twoFactorBusy}>
                                                {twoFactorBusy ? 'Menyiapkan...' : 'Setup Two Factor Authentication'}
                                            </button>
                                        ) : (
                                            <div className="public-settings-2fa__setup">
                                                <img src={twoFactorDraft.qr_image_url} alt="QR Code 2FA" loading="lazy" decoding="async" />
                                                <div className="public-settings-2fa__meta">
                                                    <strong>Secret Key</strong>
                                                    <code>{twoFactorDraft.secret}</code>
                                                    <p>Scan QR code di Google Authenticator/Authy, lalu masukkan 6 digit kode.</p>
                                                    <input
                                                        type="text"
                                                        value={twoFactorCode}
                                                        onChange={(event) => setTwoFactorCode(event.target.value)}
                                                        placeholder="Masukkan 6 digit kode"
                                                        inputMode="numeric"
                                                    />
                                                    <div className="public-settings-2fa__actions">
                                                        <button type="button" onClick={enableTwoFactor} disabled={twoFactorBusy || twoFactorCode.trim().length < 6}>
                                                            {twoFactorBusy ? 'Memverifikasi...' : 'Aktifkan 2FA'}
                                                        </button>
                                                        <button type="button" className="is-secondary" onClick={() => setTwoFactorDraft(null)} disabled={twoFactorBusy}>
                                                            Batal
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="public-settings-2fa">
                                        <div className="public-settings-2fa__disable">
                                            <label>
                                                <span>Kata Sandi Saat Ini</span>
                                                <input
                                                    type="password"
                                                    value={disablePassword}
                                                    onChange={(event) => setDisablePassword(event.target.value)}
                                                    autoComplete="current-password"
                                                    placeholder="Masukkan kata sandi"
                                                />
                                            </label>
                                            <label>
                                                <span>Kode Authenticator</span>
                                                <input
                                                    type="text"
                                                    value={disableCode}
                                                    onChange={(event) => setDisableCode(event.target.value)}
                                                    inputMode="numeric"
                                                    placeholder="Masukkan 6 digit kode"
                                                />
                                            </label>
                                            <button type="button" className="is-danger" onClick={disableTwoFactor} disabled={twoFactorBusy || disableCode.trim().length < 6 || disablePassword.trim() === ''}>
                                                {twoFactorBusy ? 'Memproses...' : 'Nonaktifkan 2FA'}
                                            </button>
                                        </div>
                                    </div>
                                )}

                                {recoveryCodes.length ? (
                                    <div className="public-settings-2fa__recovery">
                                        <h3>Kode Recovery (simpan baik-baik)</h3>
                                        <div className="public-settings-2fa__recovery-grid">
                                            {recoveryCodes.map((code) => (
                                                <code key={code}>{code}</code>
                                            ))}
                                        </div>
                                    </div>
                                ) : null}
                            </section>

                            <section className="public-settings-card">
                                <h2>Hubungkan akun</h2>
                                <p>Hubungkan akun dengan provider autentikasi untuk memudahkan login.</p>
                                <div className="public-settings-oauth">
                                    <div className="public-settings-oauth__item">
                                        <div>
                                            <strong>Google</strong>
                                            <small>{oauth.googleEmail || '-'}</small>
                                        </div>
                                        <span className={`public-settings-oauth__badge ${oauth.googleConnected ? 'is-connected' : 'is-not-connected'}`}>
                                            {oauth.googleConnected ? 'Terhubung' : 'Belum terhubung'}
                                        </span>
                                    </div>
                                </div>
                            </section>
                        </main>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

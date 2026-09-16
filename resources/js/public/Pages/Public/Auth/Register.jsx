import React, { useEffect, useRef } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';

export default function Register({ captchaRuntime = {}, googleClientId = '' }) {
    const { errors } = usePage().props;
    const captchaRef = useRef(null);
    const form = useForm({ nama: '', username: '', email: '', no_wa: '', kode_referral: '', password: '', passwordd: '', 'g-recaptcha-response': '' });

    useEffect(() => {
        if (!captchaRuntime?.is_active || !captchaRuntime?.sitekey || !captchaRef.current) return undefined;
        const render = () => {
            if (!window.grecaptcha || captchaRef.current.dataset.rendered) return;
            window.grecaptcha.render(captchaRef.current, {
                sitekey: captchaRuntime.sitekey,
                callback: (token) => form.setData('g-recaptcha-response', token),
                'expired-callback': () => form.setData('g-recaptcha-response', ''),
            });
            captchaRef.current.dataset.rendered = 'true';
        };
        const existing = document.querySelector('script[data-auth-recaptcha]');
        if (existing) { render(); return undefined; }
        const script = document.createElement('script');
        script.src = 'https://www.google.com/recaptcha/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.dataset.authRecaptcha = 'true';
        script.onload = render;
        document.head.appendChild(script);
        return undefined;
    }, [captchaRuntime?.is_active, captchaRuntime?.sitekey]);

    const submit = (event) => {
        event.preventDefault();
        form.post('/id/sign-up', { preserveScroll: true });
    };

    return (
        <PublicLayout meta={{ title: 'Daftar - ISTANATOPUP', description: 'Daftar akun ISTANATOPUP.' }} mainClassName="public-main--auth">
            <Head title="Daftar - ISTANATOPUP" />
            <section className="public-auth-page public-auth-page--register">
                <div className="public-auth-card">
                    <nav className="public-auth-tabs" aria-label="Autentikasi">
                        <Link className="public-auth-tab" href="/id/sign-in">Masuk</Link>
                        <Link className="public-auth-tab is-active" href="/id/sign-up" aria-current="page">Daftar</Link>
                    </nav>
                    <header className="public-auth-header">
                        <h1>Daftar</h1>
                        <p>Daftar gratis dan dapatkan harga member untuk semua produk.</p>
                    </header>
                    {Object.keys(errors || {}).length ? <div className="public-auth-alert public-auth-alert--error">{Object.values(errors).flat().join(' ')}</div> : null}
                    <form className="public-auth-form" onSubmit={submit}>
                        <div className="public-auth-field-grid">
                            <label className="public-auth-field"><span>Nama lengkap</span><input name="nama" autoComplete="name" placeholder="Nama lengkap" value={form.data.nama} onChange={(e) => form.setData('nama', e.target.value)} required /></label>
                            <label className="public-auth-field"><span>Username</span><input name="username" autoComplete="username" placeholder="Username" value={form.data.username} onChange={(e) => form.setData('username', e.target.value)} required /></label>
                        </div>
                        <label className="public-auth-field"><span>Email</span><input type="email" name="email" autoComplete="email" placeholder="nama@email.com" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required /></label>
                        <label className="public-auth-field"><span>Nomor WhatsApp</span><input type="tel" inputMode="numeric" name="no_wa" autoComplete="tel" placeholder="Contoh: 0812xxxxxxx" value={form.data.no_wa} onChange={(e) => form.setData('no_wa', e.target.value)} required /></label>
                        <label className="public-auth-field"><span>Kode Referral (opsional)</span><input name="kode_referral" placeholder="Kode Referral" value={form.data.kode_referral} onChange={(e) => form.setData('kode_referral', e.target.value)} /></label>
                        <div className="public-auth-field-grid">
                            <label className="public-auth-field"><span>Password</span><input type="password" name="password" autoComplete="new-password" placeholder="Minimal 6 karakter" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} required /></label>
                            <label className="public-auth-field"><span>Konfirmasi password</span><input type="password" name="passwordd" autoComplete="new-password" placeholder="Ulangi password" value={form.data.passwordd} onChange={(e) => form.setData('passwordd', e.target.value)} required /></label>
                        </div>
                        <label className="public-auth-consent"><input type="checkbox" required /> <span>Saya setuju dengan <a href="/id/privacy-policy">Kebijakan Pribadi</a> dan <a href="/id/terms-and-condition">Syarat dan Ketentuan</a>.</span></label>
                        {captchaRuntime?.is_active ? <div ref={captchaRef} className="public-auth-captcha" /> : null}
                        <button className="public-auth-submit" type="submit" disabled={form.processing}>Daftar</button>
                    </form>
                    {googleClientId ? <div className="public-auth-google-note">Pendaftaran Google tersedia melalui konfigurasi akun.</div> : null}
                    <p className="public-auth-support">Sudah punya akun? <Link href="/id/sign-in">Masuk</Link></p>
                </div>
            </section>
        </PublicLayout>
    );
}

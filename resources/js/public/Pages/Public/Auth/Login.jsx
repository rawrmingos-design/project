import React, { useEffect, useRef } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';

export default function Login({ captchaRuntime = {}, googleClientId = '' }) {
    const { errors, flash = {} } = usePage().props;
    const captchaRef = useRef(null);
    const googleRef = useRef(null);
    const form = useForm({ username: '', password: '', remember: true, two_factor_code: '', 'g-recaptcha-response': '' });

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

    useEffect(() => {
        if (!googleClientId || !googleRef.current) return undefined;
        const render = () => {
            if (!window.google?.accounts?.id || googleRef.current.dataset.rendered) return;
            window.google.accounts.id.initialize({
                client_id: googleClientId,
                ux_mode: 'popup',
                callback: ({ credential }) => {
                    if (!credential) return;
                    const formElement = document.createElement('form');
                    formElement.method = 'POST';
                    formElement.action = '/id/auth/google';
                    const csrf = document.querySelector('meta[name="csrf-token"]');
                    formElement.innerHTML = `<input name="_token" value="${csrf?.content || ''}"><input name="credential" value="${credential}">`;
                    document.body.appendChild(formElement);
                    formElement.submit();
                },
            });
            window.google.accounts.id.renderButton(googleRef.current, { theme: 'outline', size: 'large', width: 280 });
            googleRef.current.dataset.rendered = 'true';
        };
        const existing = document.querySelector('script[data-google-auth]');
        if (existing) { render(); return undefined; }
        const script = document.createElement('script');
        script.src = 'https://accounts.google.com/gsi/client';
        script.async = true;
        script.defer = true;
        script.dataset.googleAuth = 'true';
        script.onload = render;
        document.head.appendChild(script);
        return undefined;
    }, [googleClientId]);

    const submit = (event) => {
        event.preventDefault();
        form.post('/id/sign-in', { preserveScroll: true });
    };

    return (
        <PublicLayout meta={{ title: 'Masuk - ISTANATOPUP', description: 'Masuk ke akun ISTANATOPUP.' }} mainClassName="public-main--auth">
            <Head title="Masuk - ISTANATOPUP" />
            <section className="public-auth-page public-auth-page--login">
                <div className="public-auth-card">
                    <nav className="public-auth-tabs" aria-label="Autentikasi">
                        <Link className="public-auth-tab is-active" href="/id/sign-in" aria-current="page">Masuk</Link>
                        <Link className="public-auth-tab" href="/id/sign-up">Daftar</Link>
                    </nav>
                    <header className="public-auth-header"><h1>Masuk</h1><p>Masuk dengan akun yang telah kamu daftarkan.</p></header>
                    {flash.success ? <div className="public-auth-alert public-auth-alert--success">{flash.success}</div> : null}
                    {flash.error ? <div className="public-auth-alert public-auth-alert--error">{flash.error}</div> : null}
                    {Object.keys(errors || {}).length ? <div className="public-auth-alert public-auth-alert--error">{Object.values(errors).flat().join(' ')}</div> : null}
                    <form className="public-auth-form" onSubmit={submit}>
                        <label className="public-auth-field"><span>Username</span><input name="username" autoComplete="username" placeholder="Username" value={form.data.username} onChange={(e) => form.setData('username', e.target.value)} required /></label>
                        <label className="public-auth-field"><span>Password</span><input type="password" name="password" autoComplete="current-password" placeholder="Masukkan password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} required /></label>
                        <label className="public-auth-field"><span>Kode Authenticator (jika aktif)</span><input inputMode="numeric" name="two_factor_code" autoComplete="one-time-code" placeholder="6 digit kode" value={form.data.two_factor_code} onChange={(e) => form.setData('two_factor_code', e.target.value)} /></label>
                        {captchaRuntime?.is_active ? <div ref={captchaRef} className="public-auth-captcha" /> : null}
                        <div className="public-auth-row"><label><input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} /> Ingat saya</label><Link href="/id/forgot-password">Lupa password?</Link></div>
                        <button className="public-auth-submit" type="submit" disabled={form.processing}>Masuk</button>
                    </form>
                    <div className="public-auth-benefit"><strong>Belum punya akun?</strong> Daftar gratis dan langsung dapat harga member untuk semua produk, tanpa minimal transaksi.</div>
                    {googleClientId ? <div className="public-auth-google"><div ref={googleRef} /></div> : null}
                    <p className="public-auth-support">Butuh bantuan? <a href="https://wa.me/6285123031674" target="_blank" rel="noreferrer">Chat CS via WhatsApp</a></p>
                </div>
            </section>
        </PublicLayout>
    );
}

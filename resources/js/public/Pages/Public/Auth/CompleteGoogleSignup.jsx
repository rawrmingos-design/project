import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';

/**
 * Step 2 of the Google sign-up.
 *
 * Google never returns a phone number, but `no_wa` is required by the schema and is
 * the buyer identity used by the order API and every WhatsApp notification, so the
 * account is only created once this form is submitted.
 */
export default function CompleteGoogleSignup({ name = '', email = '', avatar = '' }) {
    const form = useForm({ no_wa: '' });

    const submit = (event) => {
        event.preventDefault();
        form.post('/id/auth/google/complete');
    };

    return (
        <PublicLayout meta={{ title: 'Lengkapi Akun - ISTANATOPUP', description: 'Lengkapi nomor WhatsApp untuk menyelesaikan pendaftaran.' }} mainClassName="public-main--auth">
            <Head title="Lengkapi Akun - ISTANATOPUP" />
            <section className="public-auth-page public-auth-page--complete-google">
                <div className="public-auth-card">
                    <header className="public-auth-header">
                        <h1>Satu langkah lagi</h1>
                        <p>Akun Google kamu sudah terverifikasi. Masukkan nomor WhatsApp untuk mengaktifkan akun.</p>
                    </header>
                    <div className="public-auth-profile">
                        {avatar ? <img src={avatar} alt="" className="public-auth-profile__avatar" referrerPolicy="no-referrer" /> : null}
                        <div className="public-auth-profile__identity">
                            {name ? <strong>{name}</strong> : null}
                            {email ? <span>{email}</span> : null}
                        </div>
                    </div>
                    {Object.keys(form.errors || {}).length ? (
                        <div className="public-auth-alert public-auth-alert--error">{Object.values(form.errors).flat().join(' ')}</div>
                    ) : null}
                    <form className="public-auth-form" onSubmit={submit}>
                        <label className="public-auth-field">
                            <span>Nomor WhatsApp</span>
                            <input
                                type="tel"
                                inputMode="numeric"
                                autoComplete="tel"
                                name="no_wa"
                                placeholder="Contoh: 0812xxxxxxx"
                                value={form.data.no_wa}
                                onChange={(event) => form.setData('no_wa', event.target.value)}
                                required
                                autoFocus
                            />
                        </label>
                        <p className="public-auth-hint">Nomor ini dipakai untuk notifikasi transaksi dan dihubungi jika terjadi masalah.</p>
                        <button className="public-auth-submit" type="submit" disabled={form.processing}>Aktifkan Akun</button>
                    </form>
                    <p className="public-auth-support">Salah akun? <Link href="/id/sign-in">Kembali ke halaman masuk</Link></p>
                </div>
            </section>
        </PublicLayout>
    );
}

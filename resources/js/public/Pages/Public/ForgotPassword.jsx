import React from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function ForgotPassword({ meta }) {
    const { flash = {} } = usePage().props;
    const form = useForm({ username: '' });
    const notice = flash.success || form.errors.username;
    const noticeTone = flash.success ? 'is-success' : 'is-error';

    const submit = (event) => {
        event.preventDefault();
        form.post('/id/forgot-password', { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-information-page public-information-page--centered">
                <div className="public-shell">
                    <div className="public-auth-card">
                        <Link href="/id/sign-in" className="public-auth-card__back">Kembali ke masuk</Link>
                        <p className="public-information-page__eyebrow">Akun</p>
                        <h1>Lupa Kata Sandi</h1>
                        <p>Masukkan username. Jika akun dan metode pemulihan tersedia, instruksi reset akan dikirim.</p>

                        {notice ? <div className={`public-auth-card__notice ${noticeTone}`}>{notice}</div> : null}

                        <form onSubmit={submit} className="public-auth-card__form">
                            <label>
                                <span>Username</span>
                                <input
                                    type="text"
                                    autoComplete="username"
                                    value={form.data.username}
                                    onChange={(event) => form.setData('username', event.target.value)}
                                    placeholder="Masukkan username"
                                    maxLength="255"
                                    required
                                />
                            </label>
                            <button type="submit" className="public-button" disabled={form.processing}>
                                {form.processing ? 'Mengirim...' : 'Kirim Instruksi Reset'}
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

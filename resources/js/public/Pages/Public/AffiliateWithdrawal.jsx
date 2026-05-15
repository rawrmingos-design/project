import React from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import UserDashboardSidebar from '../../Components/UserDashboardSidebar';

function formatRupiah(value) {
    return `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;
}

function Notice({ message, tone = 'success' }) {
    if (!message) {
        return null;
    }

    return (
        <div className={`public-affiliate-notice ${tone === 'success' ? 'is-success' : 'is-error'}`}>
            {message}
        </div>
    );
}

export default function AffiliateWithdrawal({ meta, withdrawal }) {
    const links = withdrawal?.links || {};
    const flash = withdrawal?.flash || {};

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-dashboard-page public-affiliate-page public-affiliate-withdrawal-page">
                <div className="public-shell">
                    <div className="public-dashboard">
                        <UserDashboardSidebar links={links} />

                        <main className="public-dashboard-main">
                            <header className="public-dashboard-page-header public-dashboard-page-header--affiliate">
                                <h1>{withdrawal?.title || 'Pembayaran Afiliasi'}</h1>
                                <p>{withdrawal?.description || 'Tarik komisi affiliate kamu ke rekening atau e-wallet yang valid.'}</p>
                            </header>

                            <Notice message={flash.success} tone="success" />
                            <Notice message={flash.error} tone="error" />

                            <nav className="public-affiliate-tabs" aria-label="Tab afiliasi">
                                <Link href={links.affiliate || '/id/affiliate'}>Riwayat</Link>
                                <Link href={links.withdrawal || '/id/withdrawal'} className="is-active">Pembayaran</Link>
                            </nav>

                            <section className="public-affiliate-overview public-withdrawal-balance-grid">
                                <article className="public-affiliate-overview__card is-highlight">
                                    <p>Saldo Saat Ini</p>
                                    <strong>{formatRupiah(withdrawal?.currentBalance || 0)}</strong>
                                    <span>Nominal komisi yang sudah masuk ke akun kamu.</span>
                                </article>
                            </section>

                            <section className="public-withdrawal-form-card">
                                <header className="public-withdrawal-form-card__header">
                                    <h2>Pembayaran Afiliasi</h2>
                                    <p>Untuk sementara, tab ini hanya menampilkan saldo afiliasi aktif kamu.</p>
                                </header>
                            </section>
                        </main>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

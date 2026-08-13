import React, { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import UserDashboardSidebar from '../../Components/UserDashboardSidebar';

function formatRupiah(value) {
    return `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;
}

function formatNumber(value) {
    return new Intl.NumberFormat('id-ID').format(Number(value || 0));
}

function SecurityIcon() {
    return (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 3 5 6v6c0 4.3 2.9 8.2 7 9 4.1-.8 7-4.7 7-9V6l-7-3Z" stroke="currentColor" strokeWidth="1.8" />
            <path d="m9.5 12 1.8 1.8 3.5-3.5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function StatusBadge({ status }) {
    const toneClass = `public-dashboard-table__badge public-dashboard-table__badge--${status?.tone || 'pending'}`;
    return <span className={toneClass}>{status?.label || 'Pending'}</span>;
}

export default function Dashboard({ meta, dashboard }) {
    const profile = dashboard?.profile || {};
    const credits = dashboard?.credits || {};
    const links = dashboard?.links || {};
    const stats = dashboard?.stats || {};
    const recentTransactions = Array.isArray(dashboard?.recentTransactions) ? dashboard.recentTransactions : [];
    const periodStats = (stats.periods && typeof stats.periods === 'object') ? stats.periods : {};
    const defaultPeriod = stats.defaultPeriod && periodStats[stats.defaultPeriod]
        ? stats.defaultPeriod
        : (periodStats['30d'] ? '30d' : (periodStats['7d'] ? '7d' : (periodStats['1d'] ? '1d' : null)));
    const [selectedPeriod, setSelectedPeriod] = useState(defaultPeriod);
    const activeStats = selectedPeriod && periodStats[selectedPeriod] ? periodStats[selectedPeriod] : stats;
    const periodLabel = activeStats?.label || stats.totalPeriodLabel || '30 hari terakhir';
    const periodOptions = [
        { key: '1d', label: 'Hari ini' },
        { key: '7d', label: '7 Hari' },
        { key: '30d', label: '30 Hari' },
    ].filter((option) => Boolean(periodStats[option.key]));
    const avatarFallback = profile.avatarFallback || `https://ui-avatars.com/api/?color=FFFFFF&background=50a7ff&name=${encodeURIComponent(profile.name || profile.username || 'Member')}`;
    const [profileAvatarSrc, setProfileAvatarSrc] = useState(profile.avatar || avatarFallback);

    useEffect(() => {
        setProfileAvatarSrc(profile.avatar || avatarFallback);
    }, [profile.avatar, avatarFallback]);

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-dashboard-page">
                <div className="public-shell">
                    <div className="public-dashboard">
                        <UserDashboardSidebar links={links} />

                        <main className="public-dashboard-main">
                            <article className="public-dashboard-alert">
                                <div className="public-dashboard-alert__copy">
                                    <h2>Tingkatkan keamanan!</h2>
                                    <p>
                                        Gunakan fitur 2FA agar akun kamu lebih aman.
                                        {' '}
                                        <a href={links.settings}>Klik di sini</a>
                                        {' '}
                                        untuk melakukan pengaturan!
                                    </p>
                                </div>
                                <span className="public-dashboard-alert__icon"><SecurityIcon /></span>
                            </article>

                            <section className="public-dashboard-headcards">
                                <article className="public-dashboard-card public-dashboard-card--profile">
                                    <div className="public-dashboard-profile">
                                        <img
                                            src={profileAvatarSrc}
                                            alt={profile.username || 'Avatar'}
                                            className="public-dashboard-profile__avatar"
                                            onError={() => setProfileAvatarSrc(avatarFallback)}
                                        />
                                        <div className="public-dashboard-profile__copy">
                                            <h3>{profile.name || '-'}</h3>
                                            <span>{profile.role || 'Member'}</span>
                                        </div>
                                        <Link href={links.settings} className="public-dashboard-profile__settings" aria-label="Buka pengaturan akun">
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" strokeWidth="1.6" />
                                                <path d="m19.4 15-1.1-.6a7.7 7.7 0 0 0 0-4.8l1.1-.6a1 1 0 0 0 .4-1.4l-1-1.8a1 1 0 0 0-1.3-.4l-1.1.6a7.6 7.6 0 0 0-4.1-2.4V2.5a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v1.3a7.6 7.6 0 0 0-4.1 2.4l-1.1-.6a1 1 0 0 0-1.3.4l-1 1.8a1 1 0 0 0 .4 1.4l1.1.6a7.7 7.7 0 0 0 0 4.8l-1.1.6a1 1 0 0 0-.4 1.4l1 1.8a1 1 0 0 0 1.3.4l1.1-.6a7.6 7.6 0 0 0 4.1 2.4v1.3a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-1.3a7.6 7.6 0 0 0 4.1-2.4l1.1.6a1 1 0 0 0 1.3-.4l1-1.8a1 1 0 0 0-.4-1.4Z" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
                                            </svg>
                                        </Link>
                                    </div>
                                    <div className="public-dashboard-profile__meta">
                                        <span>{profile.phone || '---'}</span>
                                    </div>
                                </article>

                                <article className="public-dashboard-card public-dashboard-card--credits">
                                    <div className="public-dashboard-credits">
                                        <p className="public-dashboard-credits__label">{credits.coinName || 'KoinKredits'}</p>
                                        <p className="public-dashboard-credits__amount">
                                            {credits.coinSymbol === 'Rp' ? formatRupiah(credits.amount).replace(/^Rp\s*/, '') : formatNumber(credits.amount)} <strong>{credits.coinSymbol || 'Rp'}</strong>
                                        </p>
                                    </div>
                                    <div className="public-dashboard-credits__actions">
                                        {(typeof credits.showTopUp === 'boolean' ? credits.showTopUp : !credits.showRedeem) ? (
                                            <Link href={links.deposit} className="public-dashboard-button public-dashboard-button--primary">Top Up</Link>
                                        ) : null}
                                        {credits.showRedeem ? (
                                            <Link href={links.redeem} className="public-dashboard-button public-dashboard-button--primary">Redeem</Link>
                                        ) : null}
                                    </div>
                                </article>
                            </section>

                            <section className="public-dashboard-stats">
                                <h2>Ringkasan Transaksi</h2>
                                <p className="public-dashboard-stats__period">
                                    <span>Periode aktif: {periodLabel}</span>
                                </p>
                                {periodOptions.length ? (
                                    <div className="public-dashboard-stats__switch" role="tablist" aria-label="Pilih periode ringkasan transaksi">
                                        {periodOptions.map((period) => (
                                            <button
                                                key={period.key}
                                                type="button"
                                                role="tab"
                                                aria-selected={selectedPeriod === period.key}
                                                className={`public-dashboard-stats__switch-btn ${selectedPeriod === period.key ? 'is-active' : ''}`}
                                                onClick={() => setSelectedPeriod(period.key)}
                                            >
                                                {period.label}
                                            </button>
                                        ))}
                                    </div>
                                ) : null}
                                <div className="public-dashboard-stats__row public-dashboard-stats__row--main">
                                    <article className="public-dashboard-stat public-dashboard-stat--neutral">
                                        <strong>{formatNumber(activeStats.totalTransactions)}</strong>
                                        <span>Total Transaksi</span>
                                    </article>
                                    <article className="public-dashboard-stat public-dashboard-stat--neutral">
                                        <strong>{formatNumber(activeStats.totalSales)}</strong>
                                        <span>Total Penjualan</span>
                                    </article>
                                </div>
                                <div className="public-dashboard-stats__row public-dashboard-stats__row--status">
                                    <article className="public-dashboard-stat public-dashboard-stat--warning">
                                        <strong>{formatNumber(activeStats.waiting)}</strong>
                                        <span>Menunggu</span>
                                    </article>
                                    <article className="public-dashboard-stat public-dashboard-stat--info">
                                        <strong>{formatNumber(activeStats.processing)}</strong>
                                        <span>Dalam Proses</span>
                                    </article>
                                    <article className="public-dashboard-stat public-dashboard-stat--success">
                                        <strong>{formatNumber(activeStats.success)}</strong>
                                        <span>Sukses</span>
                                    </article>
                                    <article className="public-dashboard-stat public-dashboard-stat--danger">
                                        <strong>{formatNumber(activeStats.failed)}</strong>
                                        <span>Gagal</span>
                                    </article>
                                </div>
                            </section>

                            <section className="public-dashboard-table">
                                <h2>Riwayat Transaksi Terbaru</h2>
                                <div className="public-dashboard-table__shell">
                                    {recentTransactions.length ? (
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Nomor Invoice</th>
                                                    <th>ID Trx</th>
                                                    <th>Item</th>
                                                    <th>User Input</th>
                                                    <th>Harga</th>
                                                    <th>Tanggal</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {recentTransactions.map((item) => (
                                                    <tr key={`${item.invoiceId}-${item.createdAt}`}>
                                                        <td>
                                                            <a href={item.invoiceUrl} className="public-dashboard-table__invoice-link">
                                                                {item.invoiceId}
                                                            </a>
                                                        </td>
                                                        <td>{item.providerOrderId || '-'}</td>
                                                        <td>{item.item || '-'}</td>
                                                        <td>{item.userInput || '-'}</td>
                                                        <td>{formatRupiah(item.price)}</td>
                                                        <td>{item.createdAt || '-'}</td>
                                                        <td><StatusBadge status={item.status} /></td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    ) : (
                                        <div className="public-dashboard-table__empty">
                                            Belum ada data transaksi.
                                        </div>
                                    )}
                                </div>
                            </section>
                        </main>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

import React from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import UserDashboardSidebar from '../../Components/UserDashboardSidebar';

function formatRupiah(value) {
    return `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;
}

function StatusBadge({ status }) {
    return (
        <span className={`public-dashboard-table__badge public-dashboard-table__badge--${status?.tone || 'pending'}`}>
            {status?.label || 'Menunggu'}
        </span>
    );
}

export default function DepositHistory({ meta, mutation }) {
    const links = mutation?.links || {};
    const histories = Array.isArray(mutation?.histories) ? mutation.histories : [];

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-dashboard-page public-dashboard-history-page">
                <div className="public-shell">
                    <div className="public-dashboard">
                        <UserDashboardSidebar links={links} />

                        <main className="public-dashboard-main">
                            <header className="public-dashboard-page-header public-dashboard-page-header--history">
                                <h1>{mutation?.title || 'Riwayat Deposit'}</h1>
                                <p>{mutation?.description || 'Menampilkan data riwayat transaksi deposit yang telah kamu lakukan.'}</p>
                            </header>

                            <section className="public-dashboard-table public-dashboard-table--history">
                                <div className="public-dashboard-table__shell">
                                    {histories.length ? (
                                        <table className="public-dashboard-table__table">
                                            <thead>
                                                <tr>
                                                    <th>Nomor Invoice</th>
                                                    <th>ID Trx</th>
                                                    <th>Metode</th>
                                                    <th>Jumlah</th>
                                                    <th>Tanggal</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {histories.map((history) => (
                                                    <tr key={`${history.orderId}-${history.createdAt}`}>
                                                        <td>
                                                            <a href={history.invoiceUrl} className="public-dashboard-table__invoice-link">
                                                                {history.orderId}
                                                            </a>
                                                        </td>
                                                        <td>{history.transactionId || 'n/a'}</td>
                                                        <td>{history.method || '-'}</td>
                                                        <td>{formatRupiah(history.amount)}</td>
                                                        <td>{history.createdAt || '-'}</td>
                                                        <td><StatusBadge status={history.status} /></td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    ) : (
                                        <div className="public-dashboard-table__empty">Belum ada riwayat deposit.</div>
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

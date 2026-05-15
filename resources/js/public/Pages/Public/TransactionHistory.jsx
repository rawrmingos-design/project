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

export default function TransactionHistory({ meta, history }) {
    const links = history?.links || {};
    const transactions = Array.isArray(history?.transactions) ? history.transactions : [];

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-dashboard-page public-dashboard-history-page">
                <div className="public-shell">
                    <div className="public-dashboard">
                        <UserDashboardSidebar links={links} />

                        <main className="public-dashboard-main">
                            <header className="public-dashboard-page-header public-dashboard-page-header--history">
                                <h1>{history?.title || 'Riwayat Transaksi'}</h1>
                                <p>{history?.description || 'Menampilkan data riwayat transaksi yang telah kamu lakukan.'}</p>
                            </header>

                            <section className="public-dashboard-table public-dashboard-table--history">
                                <div className="public-dashboard-table__shell">
                                    {transactions.length ? (
                                        <table className="public-dashboard-table__table">
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
                                                {transactions.map((transaction) => (
                                                    <tr key={`${transaction.invoiceId}-${transaction.createdAt}`}>
                                                        <td>
                                                            <a href={transaction.invoiceUrl} className="public-dashboard-table__invoice-link">
                                                                {transaction.invoiceId}
                                                            </a>
                                                        </td>
                                                        <td>{transaction.providerOrderId || 'n/a'}</td>
                                                        <td>{transaction.item || '-'}</td>
                                                        <td>{transaction.userInput || '-'}</td>
                                                        <td>{formatRupiah(transaction.price)}</td>
                                                        <td>{transaction.createdAt || '-'}</td>
                                                        <td><StatusBadge status={transaction.status} /></td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    ) : (
                                        <div className="public-dashboard-table__empty">Belum ada data transaksi.</div>
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

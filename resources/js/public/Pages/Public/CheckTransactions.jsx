import React, { useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0)).replace(/\u00A0/g, ' ');
}

function SearchInvoiceIcon() {
    return (
        <svg viewBox="0 0 25 24" fill="none" aria-hidden="true">
            <path
                opacity="0.4"
                d="M11.1384 21L8.96382 20.1117C8.49095 19.919 7.95874 19.9346 7.49852 20.1545L6.72695 20.5232C5.91647 20.9115 4.97852 20.3199 4.97949 19.4209L4.98922 6.98335C4.98922 4.52368 6.35722 3 8.81203 3H16.2202C18.6819 3 20.0197 4.52368 20.0197 6.98335V11.2742"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M19.0011 19.4999L20.3931 20.8899M16.8328 14.9844C18.3195 14.9844 19.5241 16.1899 19.5241 17.6766C19.5241 19.1623 18.3195 20.3678 16.8328 20.3678C15.3461 20.3678 14.1406 19.1623 14.1406 17.6766C14.1406 16.1899 15.3461 14.9844 16.8328 14.9844Z"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M14.6057 9.00781H9.63672M12.1226 12.8679H9.63862"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

function InvoiceInputIcon() {
    return (
        <svg viewBox="0 0 25 24" fill="none" aria-hidden="true">
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M14.8233 6.28561H10.1766C9.42742 6.28561 8.82031 5.67849 8.82031 4.92933V4.35627C8.82031 3.60711 9.42742 3 10.1766 3H14.8233C15.5725 3 16.1796 3.60711 16.1796 4.35627V4.92933C16.1796 5.67849 15.5725 6.28561 14.8233 6.28561Z"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                opacity="0.4"
                d="M16.1793 4.59375C18.2526 4.59375 19.9338 6.27498 19.9338 8.34831V17.2458C19.9338 19.3191 18.2526 21.0004 16.1793 21.0004H8.81999C6.74666 21.0004 5.06543 19.3191 5.06543 17.2458V8.34831C5.06543 6.27498 6.74666 4.59375 8.81999 4.59375"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M14.7043 13.3918H10.2949M12.5002 15.5968V11.1875"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

export default function CheckTransactions({ meta, recentTransactions = [], recentTransactionsScope }) {
    const [invoiceId, setInvoiceId] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [lookupError, setLookupError] = useState('');
    const emptyStateDescription = recentTransactionsScope?.key === 'auth-user'
        ? 'Belum ada transaksi pada akun ini. Setelah kamu melakukan top up saat login, riwayatnya akan muncul di sini.'
        : 'Belum ada transaksi yang tersimpan di browser ini. Setelah kamu top up dari perangkat ini, riwayatnya akan muncul di sini.';

    const handleSubmit = async (event) => {
        event.preventDefault();

        const trimmedInvoiceId = invoiceId.trim();
        if (!trimmedInvoiceId || isSubmitting) {
            if (!trimmedInvoiceId) {
                setLookupError('Masukkan nomor invoice terlebih dahulu.');
            }
            return;
        }

        setIsSubmitting(true);
        setLookupError('');

        try {
            const response = await fetch('/id/cari', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ id: trimmedInvoiceId }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok || !payload?.status || !payload?.redirect_url) {
                setLookupError(payload?.message || 'Nomor invoice tidak ditemukan. Periksa kembali lalu coba lagi.');
                return;
            }

            window.location.assign(payload.redirect_url);
        } catch (error) {
            setLookupError('Terjadi kesalahan saat mencari invoice. Silakan coba lagi.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-history-page public-history-page--bangjeff">
                <div className="public-shell">
                    <div className="public-history-hero">
                        <div className="public-history-hero__inner">
                            <h1 className="public-history-hero__title">Cek Invoice Kamu dengan Mudah dan Cepat</h1>
                            <p className="public-history-hero__description">Lihat detail pembelian kamu menggunakan nomor Invoice.</p>

                            <form className="public-history-search-card" onSubmit={handleSubmit}>
                                <h2 className="public-history-search-card__title">Cari detail pembelian kamu disini</h2>

                                <div className="public-history-search-card__field">
                                    <input
                                        type="text"
                                        value={invoiceId}
                                        onChange={(event) => setInvoiceId(event.target.value)}
                                        placeholder="Masukkan nomor Invoice Kamu (Contoh: BJXXXXXXXXXXXXXX)"
                                        aria-label="Masukkan nomor invoice"
                                        autoComplete="off"
                                        maxLength={80}
                                    />
                                    <span className="public-history-search-card__field-icon">
                                        <InvoiceInputIcon />
                                    </span>
                                </div>

                                {lookupError ? <p className="public-history-search-card__error">{lookupError}</p> : null}

                                <button type="submit" className="public-history-search-card__submit" disabled={isSubmitting}>
                                    <SearchInvoiceIcon />
                                    <span>{isSubmitting ? 'Mencari...' : 'Cari Invoice'}</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div className="public-history-table-section">
                        <header className="public-history-table-section__header">
                            <h2>{recentTransactionsScope?.title || 'Transaksi Terakhir'}</h2>
                            <p>{recentTransactionsScope?.description || 'Gunakan nomor invoice untuk melihat detail transaksi kamu.'}</p>
                        </header>

                        {recentTransactions.length ? (
                            <div className="public-history-table-shell">
                                <table className="public-history-table">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nomor Invoice</th>
                                            <th>No. Handphone</th>
                                            <th>Harga</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentTransactions.map((transaction) => (
                                            <tr key={transaction.invoiceId}>
                                                <td>{transaction.createdAt || '-'}</td>
                                                <td>
                                                    <a href={transaction.invoiceUrl} className="public-history-table__invoice-link">
                                                        {transaction.invoiceId}
                                                    </a>
                                                </td>
                                                <td>{transaction.phone || '-'}</td>
                                                <td>{formatCurrency(transaction.price)}</td>
                                                <td>
                                                    <span className={`invoice-badge ${transaction.status.badge}`}>
                                                        {transaction.status.label}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="public-history-empty-card">
                                <strong>Belum ada transaksi untuk ditampilkan.</strong>
                                <span>{emptyStateDescription}</span>
                            </div>
                        )}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

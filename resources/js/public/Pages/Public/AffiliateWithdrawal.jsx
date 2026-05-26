import React, { useMemo } from 'react';
import { Link, useForm } from '@inertiajs/react';
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
    const errors = withdrawal?.errors || {};
    const bankOptions = Array.isArray(withdrawal?.bankOptions) ? withdrawal.bankOptions : [];
    const withdrawals = Array.isArray(withdrawal?.withdrawals) ? withdrawal.withdrawals : [];
    const minimumWithdrawal = Number(withdrawal?.minimumWithdrawal || 10000);
    const currentBalance = Number(withdrawal?.currentBalance || 0);
    const lockedByBusinessRule = !withdrawal?.canSubmit;
    const {
        data,
        setData,
        post,
        processing,
        errors: formErrors,
    } = useForm({
        bank_destination: '',
        account_number: '',
        account_name: '',
        amount: '',
    });
    const amountValue = Number(data.amount || 0);
    const canSubmitForm = useMemo(() => {
        if (lockedByBusinessRule || processing) {
            return false;
        }

        return Boolean(
            String(data.bank_destination || '').trim()
            && String(data.account_number || '').trim()
            && String(data.account_name || '').trim()
            && amountValue >= minimumWithdrawal
            && amountValue <= currentBalance,
        );
    }, [amountValue, currentBalance, data.account_name, data.account_number, data.bank_destination, lockedByBusinessRule, minimumWithdrawal, processing]);
    const helperMessage = withdrawal?.disabledReason
        || (canSubmitForm ? 'Pastikan data rekening benar agar proses verifikasi admin berjalan cepat.' : `Minimal penarikan ${formatRupiah(minimumWithdrawal)}.`);

    const submitWithdrawal = (event) => {
        event.preventDefault();

        if (!canSubmitForm) {
            return;
        }

        post(links.submit || '/id/withdrawal', {
            preserveScroll: true,
        });
    };

    const applyMaxBalance = () => {
        if (lockedByBusinessRule) {
            return;
        }

        setData('amount', String(currentBalance));
    };

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
                                <Link href={links.affiliate || '/id/affiliate'}>Dashboard</Link>
                                <Link href={links.withdrawal || '/id/withdrawal'} className="is-active">Pembayaran</Link>
                            </nav>

                            <section className="public-affiliate-overview public-withdrawal-balance-grid">
                                <article className="public-affiliate-overview__card is-highlight">
                                    <p>Saldo Tersedia</p>
                                    <strong>{formatRupiah(currentBalance)}</strong>
                                    <span>Nominal komisi yang sudah masuk ke akun kamu.</span>
                                </article>
                                <article className="public-affiliate-overview__card">
                                    <p>Minimal Penarikan</p>
                                    <strong>{formatRupiah(minimumWithdrawal)}</strong>
                                    <span>{withdrawal?.hasRequestedToday ? 'Kamu sudah request penarikan hari ini.' : 'Satu permintaan penarikan per hari.'}</span>
                                </article>
                            </section>

                            <section className="public-withdrawal-form-card">
                                <header className="public-withdrawal-form-card__header">
                                    <h2>Form Penarikan</h2>
                                    <p>Isi data tujuan pembayaran dengan benar. Permintaan hanya bisa 1 kali per hari.</p>
                                </header>

                                <form className="public-withdrawal-form" onSubmit={submitWithdrawal}>
                                    <div className="public-withdrawal-form__grid">
                                        <label>
                                            <span>Nama Bank / E-Wallet</span>
                                            <select
                                                value={data.bank_destination}
                                                onChange={(event) => setData('bank_destination', event.target.value)}
                                                required
                                            >
                                                <option value="">Pilih tujuan</option>
                                                {bankOptions.map((bank) => (
                                                    <option key={bank} value={bank}>{bank}</option>
                                                ))}
                                            </select>
                                            {(formErrors.bank_destination || errors.bank_destination) ? <small>{formErrors.bank_destination || errors.bank_destination}</small> : null}
                                        </label>

                                        <label>
                                            <span>Nomor Rekening / HP</span>
                                            <input
                                                type="text"
                                                inputMode="numeric"
                                                value={data.account_number}
                                                onChange={(event) => setData('account_number', event.target.value)}
                                                placeholder="Contoh: 62812xxxx / 1234567890"
                                                required
                                            />
                                            {(formErrors.account_number || errors.account_number) ? <small>{formErrors.account_number || errors.account_number}</small> : null}
                                        </label>

                                        <label className="is-full">
                                            <span>Nama Pemilik Rekening</span>
                                            <input
                                                type="text"
                                                value={data.account_name}
                                                onChange={(event) => setData('account_name', event.target.value)}
                                                placeholder="Sesuai nama pemilik rekening/e-wallet"
                                                required
                                            />
                                            {(formErrors.account_name || errors.account_name) ? <small>{formErrors.account_name || errors.account_name}</small> : null}
                                        </label>

                                        <label className="is-full">
                                            <span>Jumlah Penarikan</span>
                                            <div className="public-withdrawal-amount-row">
                                                <input
                                                    type="number"
                                                    min={minimumWithdrawal}
                                                    max={currentBalance}
                                                    value={data.amount}
                                                    onChange={(event) => setData('amount', event.target.value)}
                                                    placeholder={String(minimumWithdrawal)}
                                                    required
                                                />
                                                <button type="button" onClick={applyMaxBalance} disabled={lockedByBusinessRule}>
                                                    Max Saldo
                                                </button>
                                            </div>
                                            <small>Maksimal penarikan: {formatRupiah(currentBalance)}</small>
                                            {(formErrors.amount || errors.amount) ? <small>{formErrors.amount || errors.amount}</small> : null}
                                        </label>
                                    </div>

                                    <div className="public-withdrawal-form__actions">
                                        <p>{helperMessage}</p>
                                        <button type="submit" disabled={!canSubmitForm}>
                                            {processing ? 'Memproses...' : (lockedByBusinessRule ? 'Tidak Tersedia' : 'Kirim Permintaan')}
                                        </button>
                                    </div>
                                </form>
                            </section>

                            <section className="public-dashboard-table public-dashboard-table--history">
                                <div className="public-affiliate-history__header">
                                    <h2>Riwayat Penarikan</h2>
                                </div>
                                <div className="public-dashboard-table__shell">
                                    {withdrawals.length ? (
                                        <table className="public-dashboard-table__table">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Tujuan</th>
                                                    <th>Jumlah</th>
                                                    <th>Biaya Admin</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {withdrawals.map((row) => (
                                                    <tr key={`${row.createdAt}-${row.destination}-${row.amount}`}>
                                                        <td>{row.createdAt || '-'}</td>
                                                        <td>{row.destination || '-'}</td>
                                                        <td>{formatRupiah(row.amount || 0)}</td>
                                                        <td>{formatRupiah(row.adminFee || 0)}</td>
                                                        <td>
                                                            <span className={`public-dashboard-table__badge public-dashboard-table__badge--${row?.status?.tone || 'pending'}`}>
                                                                {row?.status?.label || 'Pending'}
                                                            </span>
                                                            {row.proofUrl ? (
                                                                <a className="public-withdrawal-proof-link" href={row.proofUrl} target="_blank" rel="noopener noreferrer">Bukti</a>
                                                            ) : null}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    ) : (
                                        <div className="public-dashboard-table__empty">Belum ada riwayat penarikan.</div>
                                    )}
                                </div>

                                {(withdrawal?.pagination?.prevPageUrl || withdrawal?.pagination?.nextPageUrl) ? (
                                    <div className="public-affiliate-pagination">
                                        {withdrawal?.pagination?.prevPageUrl ? (
                                            <a href={withdrawal.pagination.prevPageUrl}>Sebelumnya</a>
                                        ) : (
                                            <span className="is-disabled">Sebelumnya</span>
                                        )}
                                        <span>
                                            Halaman {withdrawal?.pagination?.currentPage || 1} / {withdrawal?.pagination?.lastPage || 1}
                                        </span>
                                        {withdrawal?.pagination?.nextPageUrl ? (
                                            <a href={withdrawal.pagination.nextPageUrl}>Berikutnya</a>
                                        ) : (
                                            <span className="is-disabled">Berikutnya</span>
                                        )}
                                    </div>
                                ) : null}
                            </section>
                        </main>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

import React, { useMemo } from 'react';
import { Link, useForm } from '@inertiajs/react';
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

export default function Deposit({ meta, deposit }) {
    const links = deposit?.links || {};
    const methods = Array.isArray(deposit?.methods) ? deposit.methods : [];
    const recentDeposits = Array.isArray(deposit?.recentDeposits) ? deposit.recentDeposits : [];
    const minimumAmount = Number(deposit?.minimumAmount || 10000);
    const initialMethod = methods[0]?.code || '';
    const flash = deposit?.flash || {};

    const form = useForm({
        jumlah: '',
        no_telfon: deposit?.formDefaults?.phone || '',
        no_pembayaran: deposit?.formDefaults?.phone || '',
        metode: initialMethod,
    });

    const selectedMethod = useMemo(
        () => methods.find((method) => method.code === form.data.metode) || null,
        [methods, form.data.metode],
    );

    const amount = Math.max(0, Number(form.data.jumlah || 0));
    const feeAmount = useMemo(() => {
        if (!selectedMethod || amount <= 0) {
            return 0;
        }

        const percent = Number(selectedMethod.feePercent || 0) / 100;
        const fixed = Number(selectedMethod.fixedFee || 0);
        return Math.max(0, Math.ceil((amount * percent) + fixed));
    }, [selectedMethod, amount]);
    const totalAmount = amount + feeAmount;

    const isReadyToSubmit = Boolean(
        !form.processing
        && selectedMethod
        && amount >= minimumAmount
        && String(form.data.no_telfon || '').trim().length >= 8,
    );

    const submitDeposit = (event) => {
        event.preventDefault();
        if (!isReadyToSubmit) {
            return;
        }

        form.post('/id/deposit', {
            preserveScroll: true,
        });
    };

    const formErrorMessage = form.errors.msg || form.errors.error || '';
    const activeNotice = flash.success || flash.error || formErrorMessage;
    const noticeTone = flash.success ? 'is-success' : 'is-error';

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-dashboard-page public-deposit-page">
                <div className="public-shell">
                    <div className="public-dashboard">
                        <UserDashboardSidebar links={links} />

                        <main className="public-dashboard-main public-deposit-main">
                            <header className="public-dashboard-page-header public-dashboard-page-header--deposit">
                                <h1>{deposit?.title || 'Top Up Saldo'}</h1>
                                <p>{deposit?.description || 'Isi saldo akun kamu dengan metode pembayaran yang tersedia.'}</p>
                            </header>

                            {activeNotice ? (
                                <div className={`public-affiliate-notice ${noticeTone}`}>
                                    {activeNotice}
                                </div>
                            ) : null}

                            <section className="public-deposit-overview-card">
                                <div>
                                    <p className="public-deposit-overview-card__label">Saldo Saat Ini</p>
                                    <strong className="public-deposit-overview-card__amount">
                                        {formatRupiah(deposit?.balance || 0)}
                                    </strong>
                                </div>
                                <Link href={links.history || '/id/deposit/history'} className="public-dashboard-button">
                                    Riwayat Deposit
                                </Link>
                            </section>

                            <div className="public-deposit-grid">
                                <form className="public-deposit-form-card" onSubmit={submitDeposit}>
                                    <div className="public-deposit-form-card__section">
                                        <h2>1. Nominal Deposit</h2>
                                        <label>
                                            <span>Jumlah Deposit</span>
                                            <input
                                                type="number"
                                                min={minimumAmount}
                                                value={form.data.jumlah}
                                                onChange={(event) => form.setData('jumlah', event.target.value)}
                                                placeholder={`Minimal ${formatRupiah(minimumAmount)}`}
                                            />
                                            {form.errors.jumlah ? <small>{form.errors.jumlah}</small> : null}
                                        </label>
                                        <label>
                                            <span>Nomor WhatsApp Aktif</span>
                                            <input
                                                type="text"
                                                inputMode="numeric"
                                                value={form.data.no_telfon}
                                                onChange={(event) => {
                                                    form.setData('no_telfon', event.target.value);
                                                    form.setData('no_pembayaran', event.target.value);
                                                }}
                                                placeholder="Contoh: 62812xxxx"
                                            />
                                            {form.errors.no_telfon ? <small>{form.errors.no_telfon}</small> : null}
                                            {form.errors.no_pembayaran ? <small>{form.errors.no_pembayaran}</small> : null}
                                        </label>
                                    </div>

                                    <div className="public-deposit-form-card__section">
                                        <h2>2. Pilih Metode Pembayaran</h2>
                                        <div className="public-deposit-method-grid">
                                            {methods.map((method) => {
                                                const isActive = form.data.metode === method.code;
                                                const methodTotal = amount > 0
                                                    ? amount + Math.max(0, Math.ceil((amount * (Number(method.feePercent || 0) / 100)) + Number(method.fixedFee || 0)))
                                                    : 0;

                                                return (
                                                    <button
                                                        key={method.code}
                                                        type="button"
                                                        className={`public-deposit-method-card ${isActive ? 'is-active' : ''}`}
                                                        onClick={() => form.setData('metode', method.code)}
                                                    >
                                                        <div className="public-deposit-method-card__head">
                                                            <span>{method.name}</span>
                                                            <small>{method.typeLabel || 'Metode'}</small>
                                                        </div>
                                                        {method.image ? (
                                                            <img src={method.image} alt={method.name} loading="lazy" decoding="async" />
                                                        ) : null}
                                                        {method.note ? <p className="public-deposit-method-card__note">{method.note}</p> : null}
                                                        <strong>
                                                            {methodTotal > 0 ? formatRupiah(methodTotal) : 'Isi nominal dulu'}
                                                        </strong>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                        {form.errors.metode ? <small className="public-deposit-form-card__error">{form.errors.metode}</small> : null}
                                    </div>

                                    <div className="public-deposit-summary">
                                        <div className="public-deposit-summary__row">
                                            <span>Nominal</span>
                                            <strong>{formatRupiah(amount)}</strong>
                                        </div>
                                        <div className="public-deposit-summary__row">
                                            <span>Biaya</span>
                                            <strong>{formatRupiah(feeAmount)}</strong>
                                        </div>
                                        <div className="public-deposit-summary__row is-total">
                                            <span>Total Pembayaran</span>
                                            <strong>{formatRupiah(totalAmount)}</strong>
                                        </div>
                                        <button type="submit" className="public-deposit-summary__submit" disabled={!isReadyToSubmit}>
                                            {form.processing ? 'Memproses...' : 'Top Up Sekarang'}
                                        </button>
                                    </div>
                                </form>

                                <aside className="public-deposit-history-card">
                                    <h2>Riwayat Terbaru</h2>
                                    {recentDeposits.length ? (
                                        <ul className="public-deposit-history-card__list">
                                            {recentDeposits.map((item) => (
                                                <li key={`${item.orderId}-${item.createdAt}`}>
                                                    <div>
                                                        <a href={item.invoiceUrl}>{item.orderId}</a>
                                                        <p>{item.method}</p>
                                                        <small>{item.createdAt}</small>
                                                    </div>
                                                    <div className="public-deposit-history-card__meta">
                                                        <strong>{formatRupiah(item.amount)}</strong>
                                                        <StatusBadge status={item.status} />
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    ) : (
                                        <p className="public-deposit-history-card__empty">Belum ada transaksi deposit.</p>
                                    )}
                                </aside>
                            </div>
                        </main>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

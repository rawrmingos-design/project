import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import UserDashboardSidebar from '../../Components/UserDashboardSidebar';

function formatRupiah(value) {
    return `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * Local estimate of the admin fee (method percent + fixed).
 *
 * Only used for the per-method cards before a quote arrives. The authoritative
 * total — which for Tripay includes the gateway's own customer fee — comes from the
 * server quote endpoint, because computing it in the browser is what made the form
 * promise Rp 50.450 while the customer was charged Rp 51.554.
 */
function estimateAdminFee(amount, method) {
    if (!method || amount <= 0) {
        return 0;
    }

    const percent = Number(method.feePercent || 0) / 100;
    const fixed = Number(method.fixedFee || 0);

    return Math.max(0, Math.ceil((amount * percent) + fixed));
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

    // Server quote is the source of truth for the payable total. Until it arrives the
    // summary falls back to the local admin-fee estimate, which never understates the
    // real Tripay charge for long.
    const [quote, setQuote] = useState(null);
    const [isQuoteLoading, setIsQuoteLoading] = useState(false);
    const quoteRequestRef = useRef(0);

    useEffect(() => {
        if (!selectedMethod || amount <= 0) {
            setQuote(null);
            setIsQuoteLoading(false);
            return undefined;
        }

        const requestId = ++quoteRequestRef.current;
        const controller = new AbortController();
        setIsQuoteLoading(true);

        const timer = window.setTimeout(() => {
            fetch('/id/deposit/quote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ jumlah: amount, metode: form.data.metode }),
                signal: controller.signal,
            })
                .then((response) => (response.ok ? response.json() : null))
                .then((payload) => {
                    if (requestId !== quoteRequestRef.current) {
                        return;
                    }

                    setQuote(payload?.success ? payload.data : null);
                })
                .catch(() => {
                    if (requestId === quoteRequestRef.current) {
                        setQuote(null);
                    }
                })
                .finally(() => {
                    if (requestId === quoteRequestRef.current) {
                        setIsQuoteLoading(false);
                    }
                });
        }, 250);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [amount, form.data.metode, selectedMethod]);

    const estimatedAdminFee = estimateAdminFee(amount, selectedMethod);
    const feeAmount = quote ? Number(quote.admin_fee || 0) : estimatedAdminFee;
    const gatewayFee = quote ? Number(quote.gateway_fee || 0) : 0;
    const totalAmount = quote ? Number(quote.total_amount || 0) : amount + feeAmount;

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
                                                // The selected card shows the exact payable total from the
                                                // server quote; other cards only show the nominal + admin fee
                                                // estimate, because Tripay's customer fee is per amount.
                                                const methodTotal = isActive && quote
                                                    ? Number(quote.total_amount || 0)
                                                    : amount + estimateAdminFee(amount, method);

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
                                        {gatewayFee > 0 ? (
                                            <div className="public-deposit-summary__row" data-role="gateway-fee">
                                                <span>Biaya Payment Gateway</span>
                                                <strong>{formatRupiah(gatewayFee)}</strong>
                                            </div>
                                        ) : null}
                                        <div className="public-deposit-summary__row is-total">
                                            <span>Total Pembayaran</span>
                                            <strong data-role="deposit-total">
                                                {isQuoteLoading && !quote ? 'Menghitung...' : formatRupiah(totalAmount)}
                                            </strong>
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

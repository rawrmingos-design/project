import React, { useEffect, useMemo, useRef, useState } from 'react';
import SequentialLottiePlayer from '../../Components/SequentialLottiePlayer';
import PublicLayout from '../../Layouts/PublicLayout';

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

function normalizePaymentStatus(rawStatus) {
    const normalized = String(rawStatus || '').toLowerCase().trim();

    if (['paid', 'lunas', 'success'].includes(normalized)) {
        return { code: 'paid', label: 'Paid' };
    }

    if (normalized === 'expired') {
        return { code: 'expired', label: 'Expired' };
    }

    if (['failed', 'gagal', 'cancelled', 'canceled'].includes(normalized)) {
        return { code: 'failed', label: 'Failed' };
    }

    return { code: 'unpaid', label: 'Unpaid' };
}

function normalizeDepositStatus(rawStatus) {
    const normalized = String(rawStatus || '').toLowerCase().trim();

    if (['success', 'sukses', 'berhasil', 'paid', 'lunas'].includes(normalized)) {
        return { code: 'success', label: 'Success' };
    }

    if (['failed', 'gagal', 'cancelled', 'canceled', 'reject'].includes(normalized)) {
        return { code: 'failed', label: 'Failed' };
    }

    return { code: 'pending', label: 'Pending' };
}

function paymentBadgeClass(code) {
    if (code === 'paid') {
        return 'invoice-badge--paid';
    }

    if (code === 'expired') {
        return 'invoice-badge--expired';
    }

    if (code === 'failed') {
        return 'invoice-badge--unpaid';
    }

    return 'invoice-badge--unpaid';
}

function orderBadgeClass(code) {
    if (code === 'success') {
        return 'invoice-badge--paid';
    }

    if (code === 'failed') {
        return 'invoice-badge--unpaid';
    }

    return 'invoice-badge--processing';
}

function buildCountdownLabel(expiresAt) {
    const targetTime = Date.parse(expiresAt);
    if (Number.isNaN(targetTime)) {
        return 'Batas pembayaran tidak tersedia';
    }

    const remainingMs = targetTime - Date.now();
    if (remainingMs <= 0) {
        return 'Pembayaran kedaluwarsa';
    }

    const totalSeconds = Math.floor(remainingMs / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function resolveStatusMessage(paymentCode, depositCode, orderId) {
    if (depositCode === 'success') {
        return 'Saldo berhasil ditambahkan. Sistem sedang menyelesaikan sinkronisasi transaksi.';
    }

    if (paymentCode === 'paid') {
        return 'Pembayaran deposit sudah diterima. Sistem sedang memproses penambahan saldo.';
    }

    if (paymentCode === 'expired') {
        return 'Pembayaran deposit telah kedaluwarsa.';
    }

    if (paymentCode === 'failed' || depositCode === 'failed') {
        return 'Transaksi deposit gagal diproses. Silakan hubungi admin jika butuh bantuan.';
    }

    return `Menunggu pembayaran deposit ${orderId}.`;
}

async function writeTextToClipboard(value) {
    const text = String(value ?? '');
    if (!text) {
        return false;
    }

    if (navigator?.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            // fallback below
        }
    }

    const helperInput = document.createElement('textarea');
    helperInput.value = text;
    helperInput.setAttribute('readonly', '');
    helperInput.style.position = 'fixed';
    helperInput.style.top = '-9999px';
    helperInput.style.left = '-9999px';
    helperInput.style.opacity = '0';
    document.body.appendChild(helperInput);
    helperInput.focus();
    helperInput.select();

    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    }

    document.body.removeChild(helperInput);
    return copied;
}

function parseCountdownParts(label) {
    const match = String(label || '').match(/^(\d{2}):(\d{2}):(\d{2})$/);
    if (!match) {
        return null;
    }

    return {
        hours: match[1],
        minutes: match[2],
        seconds: match[3],
    };
}

function isFinalStatus(paymentCode, depositCode) {
    return paymentCode === 'expired'
        || paymentCode === 'failed'
        || depositCode === 'success'
        || depositCode === 'failed';
}

function renderIntroIcon(iconKey) {
    const normalized = String(iconKey || '').toLowerCase().trim();

    if (normalized === 'check') {
        return (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        );
    }

    if (normalized === 'x' || normalized === 'warning') {
        return (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        );
    }

    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    );
}

export default function DepositInvoice({ invoice, meta }) {
    const [paymentStatus, setPaymentStatus] = useState(invoice?.status?.payment || { code: 'unpaid', label: 'Unpaid' });
    const [depositStatus, setDepositStatus] = useState(invoice?.status?.deposit || { code: 'pending', label: 'Pending' });
    const [statusMessage, setStatusMessage] = useState(invoice?.status?.message || '');
    const [countdown, setCountdown] = useState(() => {
        if ((invoice?.status?.payment?.code ?? 'unpaid') !== 'unpaid') {
            return invoice?.status?.payment?.code === 'paid' ? 'Pembayaran diterima' : 'Pembayaran kedaluwarsa';
        }

        return buildCountdownLabel(invoice?.expiry?.expiresAt);
    });
    const [copyToast, setCopyToast] = useState(null);
    const [isSummaryExpanded, setIsSummaryExpanded] = useState(true);
    const [isPageReady, setIsPageReady] = useState(false);
    const [isIntroOverlayMounted, setIsIntroOverlayMounted] = useState(true);
    const [isIntroOverlayExiting, setIsIntroOverlayExiting] = useState(false);
    const toastTimerRef = useRef(null);
    const introConfig = invoice?.intro ?? {};
    const introState = String(introConfig.state || 'pending');
    const introTone = introState === 'success' ? 'paid' : introState;
    const introSequence = Array.isArray(introConfig.lottieSequence) ? introConfig.lottieSequence : [];
    const introUsesLottie = Boolean(introConfig.usesLottie);
    const hasIntroLottie = introState === 'pending' && introUsesLottie && introSequence.length > 0;
    const introDurationMs = Math.max(4300, Number(introConfig.durationMs) || 4300);
    const introSequenceSwitchMs = Math.max(180, Math.round(introDurationMs / 2));
    const introIcon = useMemo(() => renderIntroIcon(introConfig.icon), [introConfig.icon]);

    const showQrImage = Boolean(
        invoice?.payment?.showQrImage
        && invoice?.payment?.qrImageUrl
        && paymentStatus.code === 'unpaid'
        && countdown !== 'Pembayaran kedaluwarsa',
    );

    const showPayButton = Boolean(
        invoice?.payment?.showPayButton
        && invoice?.payment?.paymentUrl
        && paymentStatus.code === 'unpaid'
        && countdown !== 'Pembayaran kedaluwarsa',
    );

    const countdownParts = useMemo(() => parseCountdownParts(countdown), [countdown]);

    const progressSteps = useMemo(() => ([
        {
            key: 'created',
            label: 'Transaksi Dibuat',
            description: 'Invoice deposit berhasil dibuat',
            state: 'done',
        },
        {
            key: 'payment',
            label: 'Pembayaran',
            description: paymentStatus.code === 'paid'
                ? 'Pembayaran deposit telah diterima'
                : paymentStatus.code === 'expired'
                    ? 'Pembayaran telah kedaluwarsa'
                    : 'Silakan selesaikan pembayaran',
            state: paymentStatus.code === 'paid'
                ? 'done'
                : paymentStatus.code === 'expired' || paymentStatus.code === 'failed'
                    ? 'failed'
                    : 'active',
        },
        {
            key: 'completed',
            label: 'Saldo Diperbarui',
            description: depositStatus.code === 'success'
                ? 'Saldo berhasil ditambahkan ke akun kamu'
                : depositStatus.code === 'failed'
                    ? 'Proses deposit tidak dapat diselesaikan'
                    : 'Menunggu verifikasi pembayaran',
            state: depositStatus.code === 'success'
                ? 'done'
                : depositStatus.code === 'failed'
                    ? 'failed'
                    : 'pending',
        },
    ]), [paymentStatus.code, depositStatus.code]);

    const progressIndex = useMemo(() => {
        if (depositStatus.code === 'success') {
            return 2;
        }

        if (paymentStatus.code === 'paid') {
            return 1;
        }

        return 0;
    }, [depositStatus.code, paymentStatus.code]);

    const progressPercent = useMemo(() => {
        if (progressSteps.length <= 1) {
            return 0;
        }

        return (progressIndex / (progressSteps.length - 1)) * 100;
    }, [progressIndex, progressSteps.length]);

    useEffect(() => {
        let frameId = null;
        let shellTimer = null;
        let introExitTimer = null;
        let introUnmountTimer = null;
        const introExitDurationMs = 760;

        if (typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
            setIsPageReady(true);
            setIsIntroOverlayExiting(true);
            setIsIntroOverlayMounted(false);
            return undefined;
        }

        frameId = window.requestAnimationFrame(() => {
            introExitTimer = window.setTimeout(() => {
                setIsIntroOverlayExiting(true);
            }, Math.max(320, introDurationMs - introExitDurationMs));

            shellTimer = window.setTimeout(() => {
                setIsPageReady(true);
            }, introDurationMs);

            introUnmountTimer = window.setTimeout(() => {
                setIsIntroOverlayMounted(false);
            }, introDurationMs);
        });

        return () => {
            if (frameId) {
                window.cancelAnimationFrame(frameId);
            }
            if (shellTimer) {
                window.clearTimeout(shellTimer);
            }
            if (introExitTimer) {
                window.clearTimeout(introExitTimer);
            }
            if (introUnmountTimer) {
                window.clearTimeout(introUnmountTimer);
            }
        };
    }, [introDurationMs]);

    useEffect(() => () => {
        if (toastTimerRef.current) {
            window.clearTimeout(toastTimerRef.current);
        }
    }, []);

    useEffect(() => {
        if (!invoice?.expiry?.expiresAt) {
            return undefined;
        }

        if (paymentStatus.code !== 'unpaid') {
            setCountdown(paymentStatus.code === 'paid' ? 'Pembayaran diterima' : 'Pembayaran kedaluwarsa');
            return undefined;
        }

        const tick = () => {
            setCountdown(buildCountdownLabel(invoice.expiry.expiresAt));
        };

        tick();
        const timer = window.setInterval(tick, 1000);
        return () => window.clearInterval(timer);
    }, [invoice?.expiry?.expiresAt, paymentStatus.code]);

    useEffect(() => {
        if (!invoice?.orderId || isFinalStatus(paymentStatus.code, depositStatus.code)) {
            return undefined;
        }

        let active = true;

        const pollStatus = async () => {
            try {
                const response = await fetch(`/ajax/deposit-status/${encodeURIComponent(invoice.orderId)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                if (!active || !payload?.success) {
                    return;
                }

                const nextPaymentStatus = normalizePaymentStatus(payload.status_pembayaran);
                const nextDepositStatus = normalizeDepositStatus(payload.status_deposit);

                setPaymentStatus(nextPaymentStatus);
                setDepositStatus(nextDepositStatus);
                setStatusMessage(resolveStatusMessage(nextPaymentStatus.code, nextDepositStatus.code, invoice.orderId));
            } catch {
                // silent polling failure
            }
        };

        pollStatus();
        const timer = window.setInterval(pollStatus, 5000);

        return () => {
            active = false;
            window.clearInterval(timer);
        };
    }, [invoice?.orderId, paymentStatus.code, depositStatus.code]);

    const showCopyToast = (type, text) => {
        setCopyToast({ type, text });

        if (toastTimerRef.current) {
            window.clearTimeout(toastTimerRef.current);
        }

        toastTimerRef.current = window.setTimeout(() => {
            setCopyToast(null);
            toastTimerRef.current = null;
        }, 2200);
    };

    const copyText = async (value, label) => {
        const copied = await writeTextToClipboard(value);
        if (copied) {
            showCopyToast('success', `${label} berhasil disalin.`);
        } else {
            showCopyToast('error', `Gagal menyalin ${label.toLowerCase()}.`);
        }
    };

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-deposit-invoice-page">
                {isIntroOverlayMounted ? (
                    <div className={`invoice-status-banner-react__intro-overlay invoice-status-banner-react__intro-overlay--${introTone} ${isIntroOverlayExiting ? 'is-exiting' : 'is-visible'}`} aria-hidden="true">
                        <div className="invoice-status-banner-react__intro-panel">
                            <span className={`invoice-status-banner-react__intro-icon ${hasIntroLottie ? 'invoice-status-banner-react__intro-icon--lottie' : ''}`}>
                                {hasIntroLottie ? (
                                    <SequentialLottiePlayer
                                        sequence={introSequence}
                                        switchAfterMs={introSequenceSwitchMs}
                                        loopLast
                                        className="invoice-status-banner-react__intro-lottie"
                                    />
                                ) : introIcon}
                            </span>
                            <h2 className="invoice-status-banner-react__intro-title">{introConfig.title || invoice?.hero?.title}</h2>
                            <p className="invoice-status-banner-react__intro-description">{introConfig.subtitle || invoice?.hero?.description}</p>
                        </div>
                    </div>
                ) : null}

                <div className="public-deposit-invoice-hero">
                    <div className="public-shell">
                        <div className="public-deposit-invoice-hero__inner">
                            <div className="public-deposit-invoice-hero__icon" aria-hidden="true">
                                <span />
                                <span />
                            </div>
                            <h1>{invoice?.hero?.title || 'Harap lengkapi pembayaran.'}</h1>
                            <p>{invoice?.hero?.description || 'Silakan selesaikan pembayaran deposit sesuai metode yang dipilih.'}</p>
                        </div>
                    </div>
                </div>

                <div className={`public-shell public-deposit-invoice-shell ${isPageReady ? 'is-ready' : ''}`}>
                    <div className="public-deposit-invoice-toolbar">
                        <a href={invoice?.links?.topup || '/id/deposit'}>Buat Deposit Baru</a>
                        <button type="button" onClick={() => window.print()}>Unduh Invoice</button>
                    </div>

                    <section className="invoice-progress-section">
                        <h2 className="invoice-progress-section__title">Progress Deposit</h2>
                        <div className="invoice-progress-section__track-shell">
                            <div className="invoice-progress-section__track-base" aria-hidden="true" />
                            <div className="invoice-progress-section__track-fill" aria-hidden="true" style={{ width: `${progressPercent}%` }} />
                            <ol className="invoice-progress-section__list">
                                {progressSteps.map((step) => (
                                    <li key={step.key} className={`invoice-progress-step invoice-progress-step--${step.state}`}>
                                        <span className="invoice-progress-step__dot" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75">
                                                <circle cx="12" cy="12" r="8.5" />
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M8.8 12.6l2.3 2.4 4.3-5.2" />
                                            </svg>
                                        </span>
                                        <span className="invoice-progress-step__label">{step.label}</span>
                                        <span className="invoice-progress-step__description">{step.description}</span>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </section>

                    <div className="invoice-countdown-row">
                        <div className="invoice-countdown-chip">
                            {countdownParts ? (
                                <>
                                    <span>{countdownParts.hours} Jam</span>
                                    <span>{countdownParts.minutes} Menit</span>
                                    <span>{countdownParts.seconds} Detik</span>
                                </>
                            ) : (
                                <span>{countdown}</span>
                            )}
                        </div>
                    </div>

                    <div className="invoice-page__grid invoice-page__grid--deposit">
                        <div className="invoice-page__left">
                            <button
                                type="button"
                                className={`invoice-summary-card__head ${isSummaryExpanded ? 'is-open' : ''}`}
                                aria-expanded={isSummaryExpanded}
                                aria-controls="deposit-summary-panel"
                                onClick={() => setIsSummaryExpanded((previous) => !previous)}
                            >
                                <span>Rincian Pembayaran</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" aria-hidden="true" className="invoice-summary-card__chevron">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="m18 15-6-6-6 6" />
                                </svg>
                            </button>
                            <div
                                id="deposit-summary-panel"
                                className={`invoice-summary-card__collapse ${isSummaryExpanded ? 'is-open' : ''}`}
                                aria-hidden={!isSummaryExpanded}
                            >
                                <div className="invoice-summary-card__collapse-inner">
                                    <div className="invoice-card invoice-summary-card">
                                        <dl className="invoice-summary">
                                            <div className="invoice-summary__row">
                                                <dt>Harga</dt>
                                                <dd>{formatCurrency(invoice?.amount?.subtotal)}</dd>
                                            </div>
                                            <div className="invoice-summary__row">
                                                <dt>Biaya</dt>
                                                <dd>{formatCurrency(invoice?.amount?.fee)}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>

                            <div className="invoice-total-box">
                                <div className="invoice-total-box__label">Total Pembayaran</div>
                                <div className="invoice-total-box__value">
                                    <span className="invoice-amount-pop is-visible">{formatCurrency(invoice?.amount?.total)}</span>
                                    <button
                                        type="button"
                                        className="invoice-total-box__copy"
                                        aria-label="Salin total pembayaran"
                                        onClick={() => copyText(formatCurrency(invoice?.amount?.total), 'Total pembayaran')}
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
                                            <rect x="8" y="8" width="11" height="11" rx="2" />
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 15V6a2 2 0 012-2h9" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div className="invoice-page__right">
                            <div className="invoice-card invoice-meta-card">
                                <div className="invoice-meta-card__top">
                                    <p className="invoice-meta-card__label">Metode Pembayaran</p>
                                    <p className="invoice-meta-card__method">{invoice?.method?.name || '-'}</p>
                                </div>

                                {invoice?.method?.image ? (
                                    <img src={invoice.method.image} alt={invoice.method.name} className="invoice-meta-card__method-logo" />
                                ) : null}

                                <dl className="invoice-status-grid">
                                    <div className="invoice-status-grid__row">
                                        <dt>Nomor Invoice</dt>
                                        <dd>
                                            <span>{invoice?.orderId}</span>
                                            <button
                                                type="button"
                                                className="invoice-inline-copy"
                                                onClick={() => copyText(invoice?.orderId, 'Nomor invoice')}
                                                aria-label="Salin nomor invoice"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
                                                    <rect x="8" y="8" width="11" height="11" rx="2" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 15V6a2 2 0 012-2h9" />
                                                </svg>
                                            </button>
                                        </dd>
                                    </div>

                                    {!!invoice?.payment?.showCopyNumber && invoice?.payment?.number ? (
                                        <div className="invoice-status-grid__row">
                                            <dt>Nomor Pembayaran</dt>
                                            <dd>
                                                <span>{invoice.payment.number}</span>
                                                <button
                                                    type="button"
                                                    className="invoice-inline-copy"
                                                    onClick={() => copyText(invoice.payment.number, 'Nomor pembayaran')}
                                                    aria-label="Salin nomor pembayaran"
                                                >
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
                                                        <rect x="8" y="8" width="11" height="11" rx="2" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 15V6a2 2 0 012-2h9" />
                                                    </svg>
                                                </button>
                                            </dd>
                                        </div>
                                    ) : null}

                                    <div className="invoice-status-grid__row">
                                        <dt>Status Pembayaran</dt>
                                        <dd className={`invoice-badge ${paymentBadgeClass(paymentStatus.code)}`}>
                                            {paymentStatus.label}
                                        </dd>
                                    </div>

                                    <div className="invoice-status-grid__row">
                                        <dt>Status Deposit</dt>
                                        <dd className={`invoice-badge ${orderBadgeClass(depositStatus.code)}`}>
                                            {depositStatus.label}
                                        </dd>
                                    </div>

                                    <div className="invoice-status-grid__row">
                                        <dt>Pesan</dt>
                                        <dd>{statusMessage || resolveStatusMessage(paymentStatus.code, depositStatus.code, invoice?.orderId)}</dd>
                                    </div>
                                </dl>

                                <div className="invoice-expiry">
                                    <p className="invoice-expiry__label">Batas Waktu Pembayaran</p>
                                    <p className="invoice-expiry__value">{countdown}</p>
                                    <p className="public-deposit-invoice-expiry-meta">{invoice?.expiry?.display}</p>
                                </div>

                                {showQrImage ? (
                                    <div className="invoice-qr">
                                        <img src={invoice.payment.qrImageUrl} alt="QR Pembayaran" className="invoice-qr__image" />
                                    </div>
                                ) : null}

                                {showPayButton ? (
                                    <a className="invoice-pay-button" href={invoice.payment.paymentUrl} target="_blank" rel="noopener noreferrer">
                                        <span>{invoice?.payment?.buttonLabel || 'Klik di sini untuk melakukan pembayaran'}</span>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M14 5h5v5" />
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M10 14L19 5" />
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M19 14v4a1 1 0 01-1 1H6a1 1 0 01-1-1V6a1 1 0 011-1h4" />
                                        </svg>
                                    </a>
                                ) : null}

                                {invoice?.payment?.hint ? (
                                    <div className="public-deposit-invoice-hint">
                                        {invoice.payment.hint}
                                    </div>
                                ) : null}
                            </div>
                        </div>
                    </div>
                </div>

                {copyToast ? (
                    <div className={`invoice-copy-toast invoice-copy-toast--${copyToast.type}`} role="status" aria-live="polite">
                        {copyToast.text}
                    </div>
                ) : null}
            </section>
        </PublicLayout>
    );
}

import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
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

    return { code: 'unpaid', label: 'Unpaid' };
}

function normalizeOrderStatus(rawStatus) {
    const normalized = String(rawStatus || '').toLowerCase().trim();

    if (['sukses', 'success'].includes(normalized)) {
        return { code: 'success', label: 'Success' };
    }

    if (['proses', 'processing'].includes(normalized)) {
        return { code: 'processing', label: 'Processing' };
    }

    if (['gagal', 'batal', 'failed', 'cancelled'].includes(normalized)) {
        return { code: 'failed', label: 'Failed' };
    }

    return { code: 'pending', label: 'Pending' };
}

function paymentBadgeClass(code) {
    switch (code) {
    case 'paid':
        return 'invoice-badge--paid';
    case 'expired':
        return 'invoice-badge--expired';
    default:
        return 'invoice-badge--unpaid';
    }
}

function orderBadgeClass(code) {
    switch (code) {
    case 'success':
        return 'invoice-badge--paid';
    case 'processing':
        return 'invoice-badge--processing';
    case 'failed':
        return 'invoice-badge--unpaid';
    default:
        return 'invoice-badge--expired';
    }
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

async function writeTextToClipboard(value) {
    const text = String(value ?? '');

    if (!text) {
        return false;
    }

    if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch {
            // Fallback to execCommand copy below.
        }
    }

    if (typeof document === 'undefined') {
        return false;
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

function renderProgressIcon(stepKey) {
    if (stepKey === 'created') {
        return (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75">
                <path strokeLinecap="round" strokeLinejoin="round" d="M4 7.5A2.5 2.5 0 016.5 5h11A2.5 2.5 0 0120 7.5v9A2.5 2.5 0 0117.5 19h-11A2.5 2.5 0 014 16.5v-9z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M8 9h8M8 12h5" />
            </svg>
        );
    }

    if (stepKey === 'payment') {
        return (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3.5 8.5A2.5 2.5 0 016 6h12a2.5 2.5 0 012.5 2.5v7A2.5 2.5 0 0118 18H6a2.5 2.5 0 01-2.5-2.5v-7z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M3.5 10.5h17M15.5 14h3" />
            </svg>
        );
    }

    if (stepKey === 'processing') {
        return (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 3v3M15 3v3M3 9h3M18 9h3M3 15h3M18 15h3M9 18v3M15 18v3" />
                <rect x="7" y="7" width="10" height="10" rx="2" />
            </svg>
        );
    }

    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.85">
            <circle cx="12" cy="12" r="8.5" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8.8 12.6l2.3 2.4 4.3-5.2" />
        </svg>
    );
}

function isFinalTransactionState(paymentCode, orderCode) {
    if (orderCode === 'success' || orderCode === 'failed') {
        return true;
    }

    if (paymentCode === 'expired') {
        return true;
    }

    return false;
}

export default function Invoice({ invoice, meta }) {
    const [orderStatus, setOrderStatus] = useState(invoice?.status?.order ?? { code: 'pending', label: 'Pending' });
    const [paymentStatus, setPaymentStatus] = useState(invoice?.status?.payment ?? { code: 'unpaid', label: 'Unpaid' });
    const [isPageShellReady, setIsPageShellReady] = useState(false);
    const [isInvoiceAnimated, setIsInvoiceAnimated] = useState(false);
    const [isStatusBannerRevealed, setIsStatusBannerRevealed] = useState(false);
    const [isIntroOverlayMounted, setIsIntroOverlayMounted] = useState(true);
    const [isIntroOverlayExiting, setIsIntroOverlayExiting] = useState(false);
    const [isSummaryExpanded, setIsSummaryExpanded] = useState(true);
    const [copyToast, setCopyToast] = useState(null);
    const [isMethodLogoBroken, setIsMethodLogoBroken] = useState(false);
    const [isQrImageBroken, setIsQrImageBroken] = useState(false);
    const copyToastTimerRef = useRef(null);
    const realtimeConnectedRef = useRef(false);
    const realtimeFallbackTimerRef = useRef(null);
    const [countdown, setCountdown] = useState(() => {
        if ((invoice?.status?.payment?.code ?? 'unpaid') !== 'unpaid') {
            return invoice?.status?.payment?.code === 'paid' ? 'Pembayaran diterima' : 'Pembayaran kedaluwarsa';
        }

        return buildCountdownLabel(invoice?.expiry?.expiresAt);
    });

    const accountId = useMemo(() => {
        const userId = invoice?.account?.userId || '-';
        const zone = invoice?.account?.zone ? ` (${invoice.account.zone})` : '';
        return `${userId}${zone}`;
    }, [invoice?.account?.userId, invoice?.account?.zone]);

    const introConfig = invoice?.intro ?? {};
    const introState = String(introConfig.state || 'pending');
    const introUsesLottie = Boolean(introConfig.usesLottie);
    const introLottieSequence = Array.isArray(introConfig.lottieSequence) ? introConfig.lottieSequence : [];
    const hasIntroLottie = introState === 'pending' && introUsesLottie && introLottieSequence.length > 0;
    const introDurationMs = Math.max(4300, Number(introConfig.durationMs) || 4300);
    const introSequenceSwitchMs = Math.max(180, Math.round(introDurationMs / 2));
    const countdownParts = useMemo(() => parseCountdownParts(countdown), [countdown]);
    const isCountdownExpired = countdown === 'Pembayaran kedaluwarsa';
    const hasPayButton = Boolean(invoice?.payment?.showPayButton && invoice?.payment?.paymentUrl);
    const showActivePayButton = hasPayButton && paymentStatus.code === 'unpaid' && !isCountdownExpired;
    const showDisabledPayButton = hasPayButton && (paymentStatus.code === 'expired' || isCountdownExpired);
    const showLiveQrImage = Boolean(
        invoice?.payment?.showQrImage
        && invoice?.payment?.qrImageUrl
        && paymentStatus.code === 'unpaid'
        && !isCountdownExpired,
    );

    useEffect(() => {
        const events = Array.isArray(invoice?.gtmEvents) ? invoice.gtmEvents : [];

        if (!events.length || typeof window === 'undefined' || typeof window.pushDataLayerEvent !== 'function') {
            return;
        }

        events.forEach((event) => {
            window.pushDataLayerEvent(event?.name, event?.payload, {
                dedupeKey: event?.dedupe_key,
            });
        });
    }, [invoice?.gtmEvents]);

    useEffect(() => {
        return () => {
            if (copyToastTimerRef.current) {
                window.clearTimeout(copyToastTimerRef.current);
            }
        };
    }, []);

    useEffect(() => {
        setIsMethodLogoBroken(false);
    }, [invoice?.payment?.methodImage]);

    useEffect(() => {
        setIsQrImageBroken(false);
    }, [invoice?.payment?.qrImageUrl]);

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
        let frameId = null;
        let shellTimer = null;
        let bannerTimer = null;
        let contentTimer = null;
        let introExitTimer = null;
        let introUnmountTimer = null;
        const introExitDurationMs = 760;

        if (typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
            setIsStatusBannerRevealed(true);
            setIsPageShellReady(true);
            setIsInvoiceAnimated(true);
            setIsIntroOverlayExiting(true);
            setIsIntroOverlayMounted(false);
            return undefined;
        }

        frameId = window.requestAnimationFrame(() => {
            bannerTimer = window.setTimeout(() => {
                setIsStatusBannerRevealed(true);
            }, Math.max(120, introDurationMs - 180));

            shellTimer = window.setTimeout(() => {
                setIsPageShellReady(true);
            }, introDurationMs);

            contentTimer = window.setTimeout(() => {
                setIsInvoiceAnimated(true);
            }, introDurationMs + 90);

            introExitTimer = window.setTimeout(() => {
                setIsIntroOverlayExiting(true);
            }, Math.max(320, introDurationMs - introExitDurationMs));

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
            if (bannerTimer) {
                window.clearTimeout(bannerTimer);
            }
            if (contentTimer) {
                window.clearTimeout(contentTimer);
            }
            if (introExitTimer) {
                window.clearTimeout(introExitTimer);
            }
            if (introUnmountTimer) {
                window.clearTimeout(introUnmountTimer);
            }
        };
    }, [introDurationMs]);

    const applyInvoiceStatusUpdate = useCallback((rawPaymentStatus, rawOrderStatus) => {
        const nextPaymentStatus = normalizePaymentStatus(rawPaymentStatus);
        const nextOrderStatus = normalizeOrderStatus(rawOrderStatus);

        setPaymentStatus((previous) => (
            previous.code === nextPaymentStatus.code && previous.label === nextPaymentStatus.label
                ? previous
                : nextPaymentStatus
        ));

        setOrderStatus((previous) => (
            previous.code === nextOrderStatus.code && previous.label === nextOrderStatus.label
                ? previous
                : nextOrderStatus
        ));
    }, []);

    useEffect(() => {
        const realtimeChannel = invoice?.realtime?.channel;
        const realtimeEvent = invoice?.realtime?.event || '.InvoiceStatusUpdated';

        realtimeConnectedRef.current = false;

        if (realtimeFallbackTimerRef.current) {
            window.clearTimeout(realtimeFallbackTimerRef.current);
            realtimeFallbackTimerRef.current = null;
        }

        if (!realtimeChannel || typeof window === 'undefined' || !window.Echo) {
            return undefined;
        }

        let isSubscribed = true;

        try {
            window.Echo.channel(realtimeChannel).listen(realtimeEvent, (event) => {
                if (!isSubscribed) {
                    return;
                }

                realtimeConnectedRef.current = true;

                applyInvoiceStatusUpdate(
                    event?.payment_status ?? event?.paymentStatus ?? event?.payment_status_code,
                    event?.order_status ?? event?.orderStatus ?? event?.order_status_code,
                );
            });

            realtimeFallbackTimerRef.current = window.setTimeout(() => {
                realtimeFallbackTimerRef.current = null;
            }, 3500);
        } catch {
            realtimeConnectedRef.current = false;
        }

        return () => {
            isSubscribed = false;

            if (realtimeFallbackTimerRef.current) {
                window.clearTimeout(realtimeFallbackTimerRef.current);
                realtimeFallbackTimerRef.current = null;
            }

            if (window.Echo) {
                window.Echo.leave(realtimeChannel);
            }
        };
    }, [applyInvoiceStatusUpdate, invoice?.realtime?.channel, invoice?.realtime?.event]);

    useEffect(() => {
        if (!invoice?.orderId || isFinalTransactionState(paymentStatus.code, orderStatus.code)) {
            return undefined;
        }

        let isActive = true;

        const pollStatus = async () => {
            if (realtimeConnectedRef.current) {
                return;
            }

            try {
                const response = await fetch(`/ajax/transaction-status/${encodeURIComponent(invoice.orderId)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                if (!isActive || !payload?.success) {
                    return;
                }

                applyInvoiceStatusUpdate(payload.status_pembayaran, payload.status_pembelian);
            } catch {
                // no-op: polling should fail silently for UX
            }
        };

        pollStatus();
        const timer = window.setInterval(pollStatus, 5000);

        return () => {
            isActive = false;
            window.clearInterval(timer);
        };
    }, [applyInvoiceStatusUpdate, invoice?.orderId, orderStatus.code, paymentStatus.code]);

    const showCopyToast = (type, text) => {
        setCopyToast({ type, text });

        if (copyToastTimerRef.current) {
            window.clearTimeout(copyToastTimerRef.current);
        }

        copyToastTimerRef.current = window.setTimeout(() => {
            setCopyToast(null);
            copyToastTimerRef.current = null;
        }, 2200);
    };

    const copyText = async (value, label = 'Data') => {
        if (!value) {
            return;
        }

        const copied = await writeTextToClipboard(value);
        if (copied) {
            showCopyToast('success', `${label} disalin.`);
        } else {
            showCopyToast('error', `Gagal menyalin ${label.toLowerCase()}.`);
        }
    };

    const introTone = useMemo(() => {
        if (introState === 'success') {
            return 'paid';
        }

        return introState;
    }, [introState]);

    const introIcon = useMemo(() => {
        const iconKey = String(introConfig.icon || '').toLowerCase().trim();

        if (iconKey === 'check') {
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            );
        }

        if (iconKey === 'x' || iconKey === 'warning') {
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
    }, [introConfig.icon]);

    const statusBannerClassName = `invoice-status-banner-react invoice-status-banner-react--${introTone} ${isStatusBannerRevealed ? 'is-revealed' : ''}`;
    const introOverlayClassName = `invoice-status-banner-react__intro-overlay invoice-status-banner-react__intro-overlay--${introTone} ${isIntroOverlayExiting ? 'is-exiting' : 'is-visible'}`;
    const progressSteps = useMemo(() => {
        const isPaymentPaid = paymentStatus.code === 'paid';
        const isPaymentExpired = paymentStatus.code === 'expired';
        const isOrderProcessing = orderStatus.code === 'processing';
        const isOrderSuccess = orderStatus.code === 'success';
        const isOrderFailed = orderStatus.code === 'failed';

        return [
            {
                key: 'created',
                label: 'Transaksi Dibuat',
                description: 'Transaksi telah berhasil dibuat',
                state: 'done',
            },
            {
                key: 'payment',
                label: 'Pembayaran',
                description: isPaymentPaid
                    ? 'Pembayaran telah diterima'
                    : isPaymentExpired
                        ? 'Pembayaran telah kedaluwarsa'
                        : 'Silakan melakukan pembayaran',
                state: isPaymentPaid ? 'done' : isPaymentExpired ? 'failed' : 'active',
            },
            {
                key: 'processing',
                label: 'Sedang Di Proses',
                description: isOrderSuccess || isOrderProcessing
                    ? 'Pembelian sedang dalam proses.'
                    : isOrderFailed
                        ? 'Transaksi tidak dapat diproses.'
                        : 'Menunggu pembayaran diterima.',
                state: isOrderSuccess ? 'done' : isOrderProcessing ? 'active' : isOrderFailed ? 'failed' : 'pending',
            },
            {
                key: 'completed',
                label: 'Transaksi Selesai',
                description: isOrderSuccess
                    ? 'Transaksi telah berhasil dilakukan.'
                    : isOrderFailed
                        ? 'Transaksi gagal diproses.'
                        : 'Transaksi telah berhasil dilakukan.',
                state: isOrderSuccess ? 'done' : isOrderFailed ? 'failed' : 'pending',
            },
        ];
    }, [paymentStatus.code, orderStatus.code]);

    const progressIndex = useMemo(() => {
        let index = 0;
        progressSteps.forEach((step, stepIndex) => {
            if (step.state === 'done' || step.state === 'active') {
                index = stepIndex;
            }
        });

        return index;
    }, [progressSteps]);

    const progressPercent = useMemo(() => {
        if (progressSteps.length < 2) {
            return 0;
        }

        return (progressIndex / (progressSteps.length - 1)) * 100;
    }, [progressIndex, progressSteps.length]);

    const statusMessage = useMemo(() => {
        if (orderStatus.code === 'success') {
            return 'Transaksi telah berhasil dilakukan.';
        }

        if (orderStatus.code === 'processing') {
            return 'Pembelian sedang dalam proses.';
        }

        if (orderStatus.code === 'failed') {
            return 'Transaksi tidak dapat diproses.';
        }

        if (paymentStatus.code === 'expired') {
            return 'Pembayaran telah kedaluwarsa.';
        }

        if (paymentStatus.code === 'paid') {
            return 'Pembayaran sudah kami terima, transaksi akan segera diproses.';
        }

        return 'Silakan melakukan pembayaran.';
    }, [orderStatus.code, paymentStatus.code]);

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className={statusBannerClassName}>
                {isIntroOverlayMounted ? (
                    <div className={introOverlayClassName} aria-hidden="true">
                        <div className="invoice-status-banner-react__intro-panel">
                            <span className={`invoice-status-banner-react__intro-icon ${hasIntroLottie ? 'invoice-status-banner-react__intro-icon--lottie' : ''}`}>
                                {hasIntroLottie ? (
                                    <SequentialLottiePlayer
                                        sequence={introLottieSequence}
                                        switchAfterMs={introSequenceSwitchMs}
                                        loopLast
                                        className="invoice-status-banner-react__intro-lottie"
                                    />
                                ) : introIcon}
                            </span>
                            <h1 className="invoice-status-banner-react__intro-title">{invoice?.hero?.title}</h1>
                            <p className="invoice-status-banner-react__intro-description">{invoice?.hero?.description}</p>
                        </div>
                    </div>
                ) : null}

                <div className={`invoice-status-banner-react__inner ${isStatusBannerRevealed ? 'is-visible' : ''}`}>
                    <div className="invoice-status-banner-react__panel">
                        {introTone === 'pending' && introUsesLottie && introLottieSequence.length > 0 ? (
                            <span className="invoice-status-banner-react__icon invoice-status-banner-react__icon--lottie" aria-hidden="true">
                                <SequentialLottiePlayer
                                    sequence={[introLottieSequence[1] || introLottieSequence[0]]}
                                    loopLast
                                />
                            </span>
                        ) : (
                            <span className="invoice-status-banner-react__icon" aria-hidden="true">
                                {introIcon}
                            </span>
                        )}
                        <h1 className="invoice-status-banner-react__title">{invoice?.hero?.title}</h1>
                        <p className="invoice-status-banner-react__description">{invoice?.hero?.description}</p>
                    </div>
                </div>
            </section>
            <section className={`invoice-page invoice-page--bangjeff invoice-page-shell ${isPageShellReady ? 'is-ready' : ''}`}>
                <div className={`invoice-progress-section invoice-animate invoice-animate-delay-1 ${isInvoiceAnimated ? 'is-visible' : ''}`}>
                    <h2 className="invoice-progress-section__title">Progress Transaksi</h2>
                    <div className="invoice-progress-section__track-shell">
                        <div className="invoice-progress-section__track-base" aria-hidden="true" />
                        <div className="invoice-progress-section__track-fill" aria-hidden="true" style={{ width: `${progressPercent}%` }} />
                        <ol className="invoice-progress-section__list">
                            {progressSteps.map((step) => (
                                <li key={step.key} className={`invoice-progress-step invoice-progress-step--${step.state}`}>
                                    <span className="invoice-progress-step__dot" aria-hidden="true">
                                        {renderProgressIcon(step.key)}
                                    </span>
                                    <span className="invoice-progress-step__label">{step.label}</span>
                                    <span className="invoice-progress-step__description">{step.description}</span>
                                </li>
                            ))}
                        </ol>
                    </div>
                </div>

                <div className={`invoice-countdown-row invoice-animate invoice-animate-delay-2 ${isInvoiceAnimated ? 'is-visible' : ''}`}>
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

                <div className="invoice-page__grid">
                    <div className="invoice-page__left">
                        <div className={`invoice-card invoice-account-card invoice-animate invoice-animate-delay-2 ${isInvoiceAnimated ? 'is-visible' : ''}`}>
                            <div className="invoice-account-card__shell">
                                <div className="invoice-account-card__thumb-wrap">
                                    <div className="invoice-account-card__thumb">
                                        <img
                                            src={invoice?.thumbnail}
                                            alt={invoice?.productName || 'Produk'}
                                            className="invoice-account-card__thumb-image"
                                        />
                                    </div>
                                    <div className="invoice-account-card__thumb-meta">
                                        <p className="invoice-account-card__thumb-title">{invoice?.productName}</p>
                                        <p className="invoice-account-card__thumb-subtitle">{invoice?.itemName}</p>
                                    </div>
                                </div>

                                <div className="invoice-account-card__content">
                                    <p className="invoice-account-card__title">Informasi Akun</p>
                                    <div className="invoice-account-card__rows">
                                        {!!invoice?.account?.nickname && (
                                            <div className="invoice-account-card__row">
                                                <span>Nickname</span>
                                                <span>: {invoice.account.nickname}</span>
                                            </div>
                                        )}
                                        <div className="invoice-account-card__row">
                                            <span>ID</span>
                                            <span>: {accountId}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className={`invoice-animate invoice-animate-delay-3 ${isInvoiceAnimated ? 'is-visible' : ''}`}>
                            <button
                                type="button"
                                className={`invoice-summary-card__head ${isSummaryExpanded ? 'is-open' : ''}`}
                                aria-expanded={isSummaryExpanded}
                                aria-controls="invoice-payment-summary-panel"
                                onClick={() => setIsSummaryExpanded((previous) => !previous)}
                            >
                                <span>Rincian Pembayaran</span>
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.7"
                                    aria-hidden="true"
                                    className="invoice-summary-card__chevron"
                                >
                                    <path strokeLinecap="round" strokeLinejoin="round" d="m18 15-6-6-6 6" />
                                </svg>
                            </button>
                            <div
                                id="invoice-payment-summary-panel"
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
                                                <dt>Jumlah</dt>
                                                <dd>{invoice?.amount?.quantity || 1}x</dd>
                                            </div>
                                            <div className="invoice-summary__divider" />
                                            <div className="invoice-summary__row">
                                                <dt>Subtotal</dt>
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
                        </div>

                        <div className={`invoice-total-box invoice-animate invoice-animate-delay-3 ${isInvoiceAnimated ? 'is-visible' : ''}`}>
                            <div className="invoice-total-box__label">Total Pembayaran</div>
                            <div className="invoice-total-box__value">
                                <span className={`invoice-amount-pop ${isInvoiceAnimated ? 'is-visible' : ''}`}>{formatCurrency(invoice?.amount?.total)}</span>
                                <button type="button" className="invoice-total-box__copy" onClick={() => copyText(formatCurrency(invoice?.amount?.total), 'Total pembayaran')} aria-label="Salin total pembayaran">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
                                        <rect x="8" y="8" width="11" height="11" rx="2" />
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 15V6a2 2 0 012-2h9" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="invoice-page__right">
                        <div className={`invoice-card invoice-meta-card invoice-animate invoice-animate-delay-3 ${isInvoiceAnimated ? 'is-visible' : ''}`}>
                            <div className="invoice-meta-card__top">
                                <p className="invoice-meta-card__label">Metode Pembayaran</p>
                                <p className="invoice-meta-card__method">{invoice?.payment?.methodName}</p>
                            </div>

                            {invoice?.payment?.methodImage && !isMethodLogoBroken ? (
                                <img
                                    src={invoice.payment.methodImage}
                                    alt={invoice.payment.methodName}
                                    className="invoice-meta-card__method-logo"
                                    onError={() => setIsMethodLogoBroken(true)}
                                />
                            ) : null}

                            {invoice?.payment?.methodImage && isMethodLogoBroken ? (
                                <div className="invoice-meta-card__method-logo-fallback" role="note">
                                    Logo metode tidak tersedia
                                </div>
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

                                {!!invoice?.payment?.showCopyPaymentNumber && invoice?.payment?.paymentNumber && (
                                    <div className="invoice-status-grid__row">
                                        <dt>Nomor Pembayaran</dt>
                                        <dd>
                                            <span>{invoice.payment.paymentNumber}</span>
                                            <button
                                                type="button"
                                                className="invoice-inline-copy"
                                                onClick={() => copyText(invoice.payment.paymentNumber, 'Nomor pembayaran')}
                                                aria-label="Salin nomor pembayaran"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7">
                                                    <rect x="8" y="8" width="11" height="11" rx="2" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 15V6a2 2 0 012-2h9" />
                                                </svg>
                                            </button>
                                        </dd>
                                    </div>
                                )}

                                <div className="invoice-status-grid__row">
                                    <dt>Status Pembayaran</dt>
                                    <dd className={`invoice-badge ${paymentBadgeClass(paymentStatus.code)}`}>
                                        {paymentStatus.label}
                                    </dd>
                                </div>

                                <div className="invoice-status-grid__row">
                                    <dt>Status Transaksi</dt>
                                    <dd className={`invoice-badge ${orderBadgeClass(orderStatus.code)}`}>
                                        {orderStatus.label}
                                    </dd>
                                </div>

                                <div className="invoice-status-grid__row">
                                    <dt>Pesan</dt>
                                    <dd>{statusMessage}</dd>
                                </div>
                            </dl>

                            <div className="invoice-expiry">
                                <p className="invoice-expiry__label">Batas Waktu Pembayaran</p>
                                <p className="invoice-expiry__value">{countdown}</p>
                            </div>

                            {showLiveQrImage ? (
                                <div className="invoice-qr">
                                    {!isQrImageBroken ? (
                                        <img
                                            src={invoice.payment.qrImageUrl}
                                            alt="QR Pembayaran"
                                            className="invoice-qr__image"
                                            onError={() => setIsQrImageBroken(true)}
                                        />
                                    ) : (
                                        <div className="invoice-qr__fallback">
                                            <p>QR pembayaran tidak dapat dimuat.</p>
                                            {invoice?.payment?.paymentNumber ? (
                                                <button type="button" onClick={() => copyText(invoice.payment.paymentNumber, 'Data pembayaran')}>
                                                    Salin data pembayaran
                                                </button>
                                            ) : null}
                                        </div>
                                    )}
                                </div>
                            ) : null}

                            {showActivePayButton ? (
                                <a
                                    className="invoice-pay-button"
                                    href={invoice.payment.paymentUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <span>{invoice?.payment?.payButtonLabel || 'Klik di sini untuk melakukan pembayaran'}</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M14 5h5v5" />
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M10 14L19 5" />
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 14v4a1 1 0 01-1 1H6a1 1 0 01-1-1V6a1 1 0 011-1h4" />
                                    </svg>
                                </a>
                            ) : null}

                            {showDisabledPayButton ? (
                                <button type="button" className="invoice-pay-button is-disabled" disabled>
                                    <span>Pembayaran kedaluwarsa</span>
                                </button>
                            ) : null}
                        </div>
                    </div>
                </div>
            </section>

            {copyToast ? (
                <div className={`invoice-copy-toast invoice-copy-toast--${copyToast.type}`} role="status" aria-live="polite">
                    {copyToast.text}
                </div>
            ) : null}
        </PublicLayout>
    );
}

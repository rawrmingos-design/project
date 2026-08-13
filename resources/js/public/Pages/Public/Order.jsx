import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import SectionShell from '../../Components/SectionShell';
import ProductCard from '../../Components/ProductCard';
import PaymentMethodCard from '../../Components/PaymentMethodCard';
import {
    getMethodFinalPrice,
    getSelectedAmountBeforePoint,
    getSelectedBaseAmount,
    getSelectedFeeAmount,
    getSelectedFinalPrice,
    getSelectedPointDiscount,
} from '../../orderPricing';

function buildDefaultFaqQuestions(appName = 'website ini') {
    return [
        `Apakah aman melakukan top up di ${appName}?`,
        'Berapa lama transaksi akan diproses?',
        'Metode pembayaran apa saja yang tersedia?',
        `Apakah ada promo di ${appName}?`,
    ];
}

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
}

const ORDER_ACCOUNT_DRAFT_STORAGE_PREFIX = 'order:account:draft:';
const ORDER_RESET_AFTER_INVOICE_KEY = 'order:reset-after-invoice';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function buildInitialSpecialForm(fields = []) {
    return fields.reduce((acc, field) => {
        acc[field.name] = field.defaultValue ?? '';
        return acc;
    }, {});
}

async function parseJsonSafe(response) {
    try {
        return await response.json();
    } catch {
        return null;
    }
}

function toStablePayloadString(payload = {}) {
    return JSON.stringify(
        Object.keys(payload)
            .sort()
            .reduce((acc, key) => {
                acc[key] = payload[key];
                return acc;
            }, {}),
    );
}

function hashFNV1a(value) {
    let hash = 0x811c9dc5;

    for (let index = 0; index < value.length; index += 1) {
        hash ^= value.charCodeAt(index);
        hash = Math.imul(hash, 0x01000193);
    }

    return (hash >>> 0).toString(16).padStart(8, '0');
}

function buildOrderIdempotencyKey(identity, payload = {}) {
    const canonical = `${identity}:${toStablePayloadString(payload)}`;
    return `ord-${hashFNV1a(canonical)}`;
}

function extractOrderSubmitError(payload, response) {
    const errorCode = String(payload?.error_code || '').trim().toUpperCase();
    const mappedByCode = {
        ORDER_DUPLICATE_REQUEST: 'Order yang sama sedang diproses. Tunggu sebentar, lalu cek halaman invoice.',
        PAYMENT_GATEWAY_FAILED: 'Pembayaran gagal dibuat. Coba pilih metode lain atau ulangi beberapa saat lagi.',
        PAYMENT_METHOD_NOT_FOUND: 'Metode pembayaran tidak valid. Silakan pilih ulang metode pembayaran.',
        SERVICE_NOT_FOUND: 'Nominal tidak ditemukan. Silakan pilih nominal ulang.',
        ORDER_RECORD_CREATE_FAILED: 'Order belum tersimpan sempurna. Coba ulangi sekali lagi.',
        ORDER_PROCESSING_FAILED: 'Order gagal diproses. Silakan coba lagi dalam beberapa saat.',
        POINT_REDEMPTION_FAILED: 'Poin tidak bisa dipakai saat ini. Silakan refresh lalu coba lagi.',
        VOUCHER_STOCK_FAILED: 'Voucher sudah habis atau tidak valid. Promo sudah dihapus dari pesanan, silakan pilih promo lain.',
    };

    if (payload?.errors && typeof payload.errors === 'object') {
        const firstMessage = Object.values(payload.errors)
            .flat()
            .find((value) => typeof value === 'string' && value.trim() !== '');

        if (firstMessage) {
            return {
                message: firstMessage,
                errorCode,
            };
        }
    }

    if (typeof payload?.data === 'string' && payload.data.trim() !== '') {
        return {
            message: payload.data,
            errorCode,
        };
    }

    if (typeof payload?.message === 'string' && payload.message.trim() !== '') {
        return {
            message: payload.message,
            errorCode,
        };
    }

    if (mappedByCode[errorCode]) {
        return {
            message: mappedByCode[errorCode],
            errorCode,
        };
    }

    if (response?.status === 422) {
        return {
            message: 'Data order tidak valid. Cek kembali input yang diperlukan.',
            errorCode: errorCode || 'VALIDATION_ERROR',
        };
    }

    if (response?.status === 419) {
        return {
            message: 'Sesi kamu sudah berakhir. Refresh halaman lalu coba lagi.',
            errorCode: errorCode || 'SESSION_EXPIRED',
        };
    }

    return {
        message: 'Gagal membuat order.',
        errorCode,
    };
}

function stripHtml(value) {
    return String(value || '')
        .replace(/<br\s*\/?>/gi, ' ')
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function formatMethodGroup(group) {
    const normalizedGroup = String(group || '')
        .toLowerCase()
        .replace(/[_\s]+/g, '-');

    const labels = {
        qris: 'QRIS',
        ewallet: 'E-Wallet',
        'e-wallet': 'E-Wallet',
        'e-walet': 'E-Wallet',
        bank: 'Virtual Account',
        'virtual-account': 'Virtual Account',
        convenience: 'Convenience Store',
        'convenience-store': 'Convenience Store',
        'credit-card': 'Kartu Kredit',
        'kartu-kredit': 'Kartu Kredit',
        manual: 'Manual Transfer',
        'manual-transfer': 'Manual Transfer',
        pulsa: 'Pulsa',
        lainnya: 'Lainnya',
    };

    // Use original casing if it doesn't match standard keys — this allows custom category labels to be displayed as-is
    return labels[normalizedGroup] || String(group || 'Lainnya');
}

function getPaymentPreviewLogos(methods = [], limit = 12) {
    const seenSources = new Set();
    const logos = [];

    methods.forEach((method) => {
        const source = typeof method?.image === 'string' ? method.image.trim() : '';
        if (!source || seenSources.has(source)) {
            return;
        }

        seenSources.add(source);
        logos.push({
            key: method?.id ?? method?.code ?? source,
            src: source,
            alt: method?.name || 'Payment logo',
        });
    });

    return logos.slice(0, limit);
}

function buildFaqItems(category, isComplexOrder, appName) {
    const defaultFaqQuestions = buildDefaultFaqQuestions(appName);
    const notes = Array.isArray(category?.specialNotes) ? category.specialNotes.filter(Boolean) : [];

    if (notes.length) {
        return notes.map((note, index) => ({
            question: defaultFaqQuestions[index] || `Informasi Penting ${index + 1}`,
            answer: note,
        }));
    }

    const targetName = category?.name || 'produk ini';
    const inputLabel = isComplexOrder
        ? 'Lengkapi seluruh data akun dan instruksi tambahan dengan benar sebelum melanjutkan ke pembayaran.'
        : `Masukkan User ID${category?.serverId ? ' dan Server ID' : ''} sesuai akun ${targetName}, lalu pilih nominal dan metode pembayaran.`;

    return [
        {
            question: `Apakah aman melakukan top up ${targetName}?`,
            answer: inputLabel,
        },
        {
            question: 'Berapa lama transaksi akan diproses?',
            answer: 'Pesanan akan diproses setelah pembayaran berhasil terverifikasi oleh sistem. Pastikan metode pembayaran yang dipilih sesuai dengan nominal order.',
        },
        {
            question: 'Metode pembayaran apa saja yang tersedia?',
            answer: 'Kamu bisa memilih metode pembayaran yang tersedia pada panel pembayaran, seperti QRIS, e-wallet, virtual account, dan channel lainnya yang aktif untuk kategori ini.',
        },
        {
            question: `Apakah ada promo di ${appName || 'website ini'}?`,
            answer: 'Jika kamu punya kode promo, masukkan pada bagian Kode Promo sebelum menyelesaikan order. Total pembayaran akan menyesuaikan pada ringkasan.',
        },
        {
            question: 'Bagaimana jika transaksi bermasalah?',
            answer: 'Gunakan nomor WhatsApp aktif agar tim support bisa menghubungi kamu jika ada kendala saat verifikasi atau proses order.',
        },
    ];
}

function BangjeffOrderPanel({
    title,
    subtitle = null,
    children,
    actions = null,
    step = null,
    panelClassName = '',
    panelRef = null,
    sectionId = null,
}) {
    return (
        <section
            ref={panelRef}
            id={sectionId}
            className={`order-panel order-panel--bangjeff ${panelClassName}`.trim()}
        >
            <div className="order-panel__header order-panel__header--bangjeff">
                <div className="order-panel__heading order-panel__heading--bangjeff">
                    {step ? (
                        <div className="order-panel__step order-panel__step--bangjeff">{step}</div>
                    ) : (
                        <div className="order-panel__step order-panel__step--bangjeff order-panel__step--bangjeff-accent" aria-hidden="true" />
                    )}
                    <div className="order-panel__heading-copy">
                        <h2>{title}</h2>
                    </div>
                </div>
                {actions ? <div className="order-panel__actions">{actions}</div> : null}
            </div>
            <div className="order-panel__body order-panel__body--bangjeff">{children}</div>
        </section>
    );
}

function BangjeffVariantCard({ item, selected, onSelect }) {
    const currentPrice = item.isFlashSale && item.flashPrice ? item.flashPrice : item.price;
    const discountPercent = item.isFlashSale && item.flashPrice && item.price > item.flashPrice
        ? Math.round(((item.price - item.flashPrice) / item.price) * 100)
        : null;
    const mediaSource = item.productLogo || item.thumbnail || null;

    return (
        <button
            type="button"
            className={`variant-card variant-card--bangjeff ${selected ? 'is-active' : ''}`}
            onClick={onSelect}
        >
            <div className="variant-card__surface">
                <div className="variant-card__head">
                    <div className="variant-card__title-wrap">
                        <strong>{item.name}</strong>
                        <small>{item.provider || item.brand || 'Nominal siap diproses'}</small>
                    </div>
                    {item.isFlashSale && item.flashPrice ? <span className="variant-card__badge">Promo</span> : null}
                </div>

                <div className="variant-card__body">
                    <div className="variant-card__media">
                        {mediaSource ? <img src={mediaSource} alt={item.name} /> : <span>{item.name.slice(0, 1)}</span>}
                    </div>
                    <div className="variant-card__price">
                        <span>{formatCurrency(currentPrice)}</span>
                        {item.isFlashSale && item.flashPrice ? <small>{formatCurrency(item.price)}</small> : null}
                    </div>
                </div>
            </div>

            <div className="variant-card__footer">
                <div className="variant-card__footer-badges">
                    {discountPercent ? <span className="variant-card__footer-badge variant-card__footer-badge--discount">Disc {discountPercent}%</span> : <span className="variant-card__footer-spacer" />}
                    <span className="variant-card__footer-badge variant-card__footer-badge--instant">Pengiriman Instan</span>
                </div>

                <div className="variant-card__meta">
                    <small>{item.productLogo ? 'Instant' : 'Ready stock'}</small>
                    {selected ? <em>Dipilih</em> : <span>{item.isFlashSale ? 'Diskon aktif' : 'Tersedia'}</span>}
                </div>
            </div>
        </button>
    );
}

function BangjeffPaymentCard({ method, selected, onSelect, requiresLogin = false, onLoginRequired = null }) {
    const logoSource = method.image || null;
    let helperLabel = 'Biaya menyesuaikan';

    if (method.maxAmount) {
        helperLabel = `Max. ${formatBangjeffDecimalAmount(method.maxAmount)}`;
    } else if (method.minAmount) {
        helperLabel = `Min. ${formatBangjeffDecimalAmount(method.minAmount)}`;
    } else if (method.fixFee) {
        helperLabel = `Fee Rp ${formatBangjeffDecimalAmount(method.fixFee)}`;
    } else if (method.feePercent) {
        helperLabel = `Fee ${method.feePercent}%`;
    }

    return (
        <button
            type="button"
            className={`payment-card payment-card--bangjeff ${selected ? 'is-active' : ''}`}
            data-bangjeff-login-required={requiresLogin ? 'true' : undefined}
            data-bangjeff-payment-code={method.code}
            onClickCapture={(event) => {
                if (!requiresLogin) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                onLoginRequired?.();
            }}
            onClick={() => {
                if (requiresLogin) {
                    onLoginRequired?.();
                    return;
                }

                onSelect(method);
            }}
        >
            <div className="payment-card__tile payment-card__tile--bangjeff">
                <div className="payment-card__tile-logo-wrap">
                    <div className="payment-card__tile-logo">
                        {logoSource ? <img src={logoSource} alt={method.name} /> : <strong>{method.name.slice(0, 1)}</strong>}
                    </div>
                </div>
                <div className="payment-card__tile-price">
                    <small className="payment-card__tile-limit">{helperLabel}</small>
                </div>
            </div>
        </button>
    );
}

function formatBangjeffDecimalAmount(value) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '0,00';
    }

    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value));
}

function isBangjeffBalanceMethod(method) {
    return /credit|balance|saldo|bjcredits?|koin|coin|point/i.test(`${method?.name || ''} ${method?.code || ''} ${method?.group || ''}`);
}

function isBangjeffQrisMethod(method) {
    return /qris/i.test(`${method.name} ${method.code} ${method.group || ''}`) || String(method.group || '').toLowerCase() === 'qris';
}

function isBangjeffShopeeMethod(method) {
    return /shopee/i.test(`${method.name} ${method.code} ${method.group || ''}`);
}

function getBangjeffFeaturedPaymentLabel(method) {
    if (isBangjeffBalanceMethod(method)) {
        return 'Saldo Akun';
    }

    if (isBangjeffQrisMethod(method)) {
        return 'QRIS (All Payment)';
    }

    if (isBangjeffShopeeMethod(method)) {
        return 'ShopeePay App';
    }

    return method.name;
}

function getBangjeffFeaturedPaymentKind(method) {
    if (isBangjeffBalanceMethod(method)) {
        return 'balance';
    }

    if (isBangjeffQrisMethod(method)) {
        return 'qris';
    }

    if (isBangjeffShopeeMethod(method)) {
        return 'shopee';
    }

    return 'default';
}

function BangjeffFeaturedPaymentCard({
    method,
    selected,
    onSelect,
    accent = 'BEST PRICE',
    requiresLogin = false,
    onLoginRequired = null,
    displayPrice = 0,
}) {
    const isCredit = isBangjeffBalanceMethod(method);
    const paymentKind = getBangjeffFeaturedPaymentKind(method);
    let helperLabel = 'Pilihan cepat';

    if (isCredit) {
        helperLabel = `Max. ${formatBangjeffDecimalAmount(method.maxAmount)} Koin`;
    } else if (method.minAmount && Number(method.minAmount) > 0) {
        helperLabel = `Min. ${formatBangjeffDecimalAmount(method.minAmount)}`;
    } else if (method.fixFee || method.feePercent) {
        const feeParts = [];
        if (method.fixFee) feeParts.push(formatBangjeffDecimalAmount(method.fixFee));
        if (method.feePercent) feeParts.push(`${method.feePercent}%`);
        helperLabel = `Fee ${feeParts.join(' + ')}`;
    }

    const amountLabel = formatCurrency(displayPrice || 0);

    return (
        <button
            type="button"
            className={`payment-card payment-card--bangjeff payment-card--bangjeff-featured payment-card--bangjeff-featured-${paymentKind} ${selected ? 'is-active' : ''}`}
            data-bangjeff-login-required={requiresLogin ? 'true' : undefined}
            data-bangjeff-payment-code={method.code}
            onClickCapture={(event) => {
                if (!requiresLogin) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                onLoginRequired?.();
            }}
            onClick={() => {
                if (requiresLogin) {
                    onLoginRequired?.();
                    return;
                }

                onSelect(method);
            }}
        >
            <span className="payment-card__ribbon" aria-hidden="true">{accent}</span>
            <div className="payment-card__featured-main">
                <div className="payment-card__body payment-card__body--bangjeff payment-card__body--bangjeff-featured">
                    <div className="payment-card__featured-left">
                        <div className="payment-card__body-main payment-card__body-main--featured-blade">
                            <strong>{getBangjeffFeaturedPaymentLabel(method)}</strong>
                            <small>{amountLabel}</small>
                        </div>
                        <div className={`payment-card__featured-logo payment-card__featured-logo--${paymentKind}`}>
                            <div className={`payment-card__media payment-card__media--bangjeff payment-card__media--bangjeff-featured payment-card__media--bangjeff-featured-${paymentKind}`}>
                                {method.image ? <img src={method.image} alt={method.name} /> : <span>{method.name.slice(0, 1)}</span>}
                            </div>
                        </div>
                    </div>
                    <div className="payment-card__body-side payment-card__body-side--bangjeff-featured">
                        <small>{helperLabel}</small>
                    </div>
                </div>
            </div>
        </button>
    );
}

function FaqItem({ item, open, onToggle }) {
    return (
        <article className={`order-faq__item ${open ? 'is-open' : ''}`}>
            <button type="button" className="order-faq__trigger" onClick={onToggle}>
                <span>{item.question}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M6 9l6 6 6-6" />
                </svg>
            </button>
            {open ? <div className="order-faq__content"><p>{item.answer}</p></div> : null}
        </article>
    );
}

function BangjeffLoginRequiredModal({ open, onClose }) {
    const [hoveredAction, setHoveredAction] = useState(null);

    if (!open) {
        return null;
    }

    const rootStyle = {
        position: 'fixed',
        inset: 0,
        zIndex: 1200,
        isolation: 'isolate',
    };

    const backdropStyle = {
        position: 'absolute',
        inset: 0,
        background: 'rgba(0, 0, 0, 0.82)',
        backdropFilter: 'blur(2px)',
    };

    const viewportStyle = {
        position: 'relative',
        zIndex: 1,
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '18px 16px',
    };

    const panelStyle = {
        position: 'relative',
        width: 'min(100%, 512px)',
        borderRadius: '17px',
        border: '1px solid rgba(255, 255, 255, 0.12)',
        background: '#1e1e22',
        color: '#fafaf9',
        padding: '24px 24px 24px',
        boxShadow: '0 24px 56px rgba(0, 0, 0, 0.42)',
    };

    const closeStyle = {
        position: 'absolute',
        top: '13px',
        right: '13px',
        width: '31px',
        height: '31px',
        border: '1px solid rgba(255, 255, 255, 0.14)',
        borderRadius: '999px',
        background: 'rgba(255, 255, 255, 0.05)',
        color: 'rgba(250, 250, 249, 0.96)',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        cursor: 'pointer',
    };

    const iconShellStyle = {
        width: '96px',
        height: '96px',
        margin: '42px auto 44px',
        borderRadius: '999px',
        background: '#0b82c8',
        color: '#38bdf8',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
    };

    const iconRingStyle = {
        width: '39px',
        height: '39px',
        borderRadius: '999px',
        border: '2px solid rgba(56, 189, 248, 0.88)',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
    };

    const iconGlyphStyle = {
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        width: '100%',
        height: '100%',
        color: 'rgba(56, 189, 248, 0.96)',
        fontFamily: 'var(--font-bangjeff-display)',
        fontSize: '1.24rem',
        fontWeight: 700,
        lineHeight: 1,
    };

    const copyStyle = { textAlign: 'center' };
    const titleStyle = {
        margin: 0,
        color: '#f8fafc',
        fontFamily: 'var(--font-bangjeff-display)',
        fontSize: '1.08rem',
        lineHeight: 1.2,
    };
    const descriptionStyle = {
        margin: '12px auto 0',
        maxWidth: '418px',
        color: 'rgba(248, 250, 252, 0.78)',
        fontFamily: 'var(--font-bangjeff-meta)',
        fontSize: '0.93rem',
        lineHeight: 1.66,
    };

    const actionsStyle = {
        marginTop: '22px',
        display: 'grid',
        gap: '14px',
    };

    const primaryStyle = {
        minHeight: '44px',
        width: '100%',
        padding: '0 18px',
        borderRadius: '999px',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        textDecoration: 'none',
        fontSize: '0.95rem',
        fontWeight: 700,
        background: 'linear-gradient(180deg, #f97316 0%, #ea580c 100%)',
        color: '#fff',
        boxShadow: 'inset 0 1px 0 rgba(255, 255, 255, 0.18)',
        cursor: 'pointer',
        transition: 'transform 180ms ease, filter 180ms ease, box-shadow 180ms ease',
    };

    const dividerStyle = {
        display: 'flex',
        alignItems: 'center',
        gap: '12px',
        color: 'rgba(248, 250, 252, 0.8)',
        fontFamily: 'var(--font-bangjeff-meta)',
        fontSize: '0.89rem',
        fontStyle: 'normal',
        margin: '1px 0 0',
    };

    const dividerLineStyle = {
        flex: 1,
        height: '1px',
        background: 'rgba(255, 255, 255, 0.12)',
    };

    const secondaryStyle = {
        minHeight: '44px',
        width: '100%',
        padding: '0 18px',
        borderRadius: '999px',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '10px',
        textDecoration: 'none',
        fontSize: '0.95rem',
        fontWeight: 700,
        border: '1px solid rgba(255, 255, 255, 0.18)',
        background: '#fff',
        color: '#111827',
        position: 'relative',
        cursor: 'pointer',
        transition: 'transform 180ms ease, background-color 180ms ease, box-shadow 180ms ease',
    };

    const secondaryIconStyle = {
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        position: 'absolute',
        left: '18px',
        top: '50%',
        transform: 'translateY(-50%)',
        width: '20px',
        height: '20px',
        flex: '0 0 auto',
        fontFamily: '"Arial", sans-serif',
        color: '#4285F4',
        fontSize: '1.35rem',
        fontWeight: 700,
        lineHeight: 1,
    };

    return (
        <div className="bangjeff-auth-modal" style={rootStyle} role="dialog" aria-modal="true" aria-labelledby="bangjeff-login-required-title">
            <div className="bangjeff-auth-modal__backdrop" style={backdropStyle} onClick={onClose} />
            <div className="bangjeff-auth-modal__viewport" style={viewportStyle} onClick={onClose}>
                <div className="bangjeff-auth-modal__panel" style={panelStyle} onClick={(event) => event.stopPropagation()}>
                    <button type="button" className="bangjeff-auth-modal__close" style={closeStyle} onClick={onClose} aria-label="Tutup modal login">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M6 18 18 6" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>

                    <div className="bangjeff-auth-modal__icon" style={iconShellStyle} aria-hidden="true">
                        <span className="bangjeff-auth-modal__icon-ring" style={iconRingStyle}>
                            <span className="bangjeff-auth-modal__icon-glyph" style={iconGlyphStyle}>i</span>
                        </span>
                    </div>

                    <div className="bangjeff-auth-modal__copy" style={copyStyle}>
                        <h3 id="bangjeff-login-required-title" style={titleStyle}>Ups! Kamu Belum Login</h3>
                        <p style={descriptionStyle}>
                            Untuk Melakukan Pembelian Produk ini, Silahkan Login atau Daftar
                            <br />
                            Terlebih Dahulu!
                        </p>
                    </div>

                    <div className="bangjeff-auth-modal__actions" style={actionsStyle}>
                        <a
                            href="/id/sign-in"
                            className="bangjeff-auth-modal__primary bangjeff-gradient-button"
                            style={{
                                ...primaryStyle,
                                ...(hoveredAction === 'login'
                                    ? {
                                        transform: 'translateY(-1px)',
                                        filter: 'brightness(1.04)',
                                        boxShadow: 'inset 0 1px 0 rgba(255, 255, 255, 0.18), 0 10px 18px rgba(249, 115, 22, 0.2)',
                                    }
                                    : {}),
                            }}
                            onMouseEnter={() => setHoveredAction('login')}
                            onMouseLeave={() => setHoveredAction(null)}
                            onClick={onClose}
                        >
                            Login
                        </a>
                        <div className="bangjeff-auth-modal__divider" style={dividerStyle} aria-hidden="true">
                            <span style={dividerLineStyle} />
                            <em>Atau lanjutkan dengan</em>
                            <span style={dividerLineStyle} />
                        </div>
                        <a
                            href="/id/sign-in"
                            className="bangjeff-auth-modal__secondary"
                            style={{
                                ...secondaryStyle,
                                ...(hoveredAction === 'google'
                                    ? {
                                        transform: 'translateY(-1px)',
                                        background: '#f8fafc',
                                        boxShadow: '0 8px 16px rgba(15, 23, 42, 0.12)',
                                    }
                                    : {}),
                            }}
                            onMouseEnter={() => setHoveredAction('google')}
                            onMouseLeave={() => setHoveredAction(null)}
                            onClick={onClose}
                        >
                            <span className="bangjeff-auth-modal__secondary-icon" style={secondaryIconStyle} aria-hidden="true">G</span>
                            <span>Google</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
}

function BangjeffSupportModal({
    open,
    onClose,
    title = 'Customer Support',
    items = [],
    closeLabel = 'Tutup',
}) {
    if (!open) {
        return null;
    }

    const renderItemIcon = (key) => {
        if (key === 'whatsapp') {
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M20.2 11.7a8.2 8.2 0 0 1-12.1 7.2l-3.6 1 1-3.5A8.2 8.2 0 1 1 20.2 11.7Z" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="M9.4 9.4c.2-.5.4-.5.6-.5h.5c.2 0 .4 0 .5.4l.4 1.1c.1.3 0 .4-.1.6l-.3.4c-.1.1-.2.3-.1.5.3.6.9 1.3 1.7 1.7.2.1.4 0 .5-.1l.4-.3c.2-.1.3-.2.6-.1l1.1.4c.4.1.4.3.4.5v.5c0 .2 0 .5-.5.6-.4.1-1.2.2-2.6-.4a7 7 0 0 1-3.3-3.3c-.6-1.4-.5-2.2-.4-2.6Z" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            );
        }

        if (key === 'instagram') {
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="currentColor" strokeWidth="1.8" />
                    <circle cx="12" cy="12" r="4" stroke="currentColor" strokeWidth="1.8" />
                    <circle cx="17.3" cy="6.9" r="1.1" fill="currentColor" />
                </svg>
            );
        }

        if (key === 'facebook') {
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M14.2 8.5h2.3V5.4h-2.3c-2.2 0-3.9 1.7-3.9 3.9v2H8.1v3.1h2.2v4.3h3.1v-4.3h2.6l.5-3.1h-3.1v-1.7c0-.6.4-1.1 1-1.1Z" fill="currentColor" />
                </svg>
            );
        }

        if (key === 'tiktok') {
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M14.7 4.5c.6 1.3 1.6 2.2 3.1 2.6V10c-1.3 0-2.4-.3-3.4-.9V14c0 3-2.4 5.4-5.4 5.4A5.4 5.4 0 1 1 11 8.8v2.8a2.6 2.6 0 1 0 .3 2.4V4.5h3.4Z" fill="currentColor" />
                </svg>
            );
        }

        if (key === 'youtube') {
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3.2" y="6.6" width="17.6" height="10.8" rx="3.4" stroke="currentColor" strokeWidth="1.8" />
                    <path d="m10.4 9.5 4.4 2.5-4.4 2.5V9.5Z" fill="currentColor" />
                </svg>
            );
        }

        if (key === 'telegram') {
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M21.5 4.5 3.7 11.2c-1 .4-1 1.8 0 2.2l4.5 1.7 1.7 4.5c.4 1 1.8 1 2.2 0L18.8 2.5c.4-1.1-.6-2.1-1.7-1.7Z" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="m8.8 15.1 5.1-5.1" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            );
        }

        return (
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M14 3h7v7" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M10 14 21 3" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M21 14v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h6" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        );
    };

    return (
        <div className="bangjeff-support-modal" role="dialog" aria-modal="true" aria-labelledby="bangjeff-support-modal-title">
            <div className="bangjeff-support-modal__backdrop" onClick={onClose} />
            <div className="bangjeff-support-modal__viewport" onClick={onClose}>
                <div className="bangjeff-support-modal__panel" onClick={(event) => event.stopPropagation()}>
                    <h3 id="bangjeff-support-modal-title" className="bangjeff-support-modal__title">{title}</h3>
                    <ul className="bangjeff-support-modal__list">
                        {items.map((item) => {
                            const href = typeof item?.href === 'string' ? item.href.trim() : '';
                            const isExternal = /^https?:\/\//i.test(href);

                            if (!href) {
                                return (
                                    <li key={item.key}>
                                        <button type="button" className="bangjeff-support-modal__link bangjeff-support-modal__link--disabled" disabled>
                                            <span className={`bangjeff-support-modal__icon bangjeff-support-modal__icon--${item.key}`}>{renderItemIcon(item.key)}</span>
                                            <span>{item.label}</span>
                                        </button>
                                    </li>
                                );
                            }

                            return (
                                <li key={item.key}>
                                    <a
                                        href={href}
                                        className="bangjeff-support-modal__link"
                                        target={isExternal ? '_blank' : undefined}
                                        rel={isExternal ? 'noopener noreferrer' : undefined}
                                    >
                                        <span className={`bangjeff-support-modal__icon bangjeff-support-modal__icon--${item.key}`}>{renderItemIcon(item.key)}</span>
                                        <span>{item.label}</span>
                                    </a>
                                </li>
                            );
                        })}
                    </ul>
                    <div className="bangjeff-support-modal__footer">
                        <button type="button" className="bangjeff-support-modal__close" onClick={onClose}>{closeLabel}</button>
                    </div>
                </div>
            </div>
        </div>
    );
}

function BangjeffAvailablePromoModal({
    open,
    onClose,
    loading,
    promos,
    onApply,
    applyingCode = null,
}) {
    const [hoveredClose, setHoveredClose] = useState(false);
    const [hoveredPromoCode, setHoveredPromoCode] = useState(null);

    if (!open) {
        return null;
    }

    const rootStyle = {
        position: 'fixed',
        inset: 0,
        zIndex: 1210,
        isolation: 'isolate',
    };

    const backdropStyle = {
        position: 'absolute',
        inset: 0,
        background: 'rgba(0, 0, 0, 0.82)',
        backdropFilter: 'blur(2px)',
    };

    const viewportStyle = {
        position: 'relative',
        zIndex: 1,
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '20px 16px',
    };

    const panelStyle = {
        position: 'relative',
        width: 'min(100%, 448px)',
        borderRadius: '20px',
        border: '1px solid rgba(255, 255, 255, 0.08)',
        background: '#292929',
        color: '#fafaf9',
        padding: '18px 18px 20px',
        boxShadow: '0 28px 60px rgba(0, 0, 0, 0.48)',
    };

    const closeStyle = {
        position: 'absolute',
        top: '16px',
        right: '16px',
        width: '34px',
        height: '34px',
        border: '2px solid rgba(249, 115, 22, 0.9)',
        borderRadius: '999px',
        background: hoveredClose ? 'rgba(249, 115, 22, 0.08)' : 'rgba(255, 255, 255, 0.04)',
        color: '#e5e7eb',
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        cursor: 'pointer',
        transition: 'transform 180ms ease, filter 180ms ease, background-color 180ms ease',
        transform: hoveredClose ? 'translateY(-1px)' : 'none',
        filter: hoveredClose ? 'brightness(1.06)' : 'none',
    };

    const titleStyle = {
        margin: '2px 0 0',
        color: '#f8fafc',
        textAlign: 'center',
        fontFamily: 'var(--font-bangjeff-display)',
        fontSize: '1.02rem',
        lineHeight: 1.25,
    };

    const bodyStyle = {
        marginTop: '18px',
    };

    const emptyStyle = {
        minHeight: '156px',
        borderRadius: '16px',
        border: '1px dashed rgba(255, 255, 255, 0.08)',
        background: 'rgba(255, 255, 255, 0.02)',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '7px',
        padding: '22px 18px',
        textAlign: 'center',
    };

    const emptyIconStyle = {
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        width: '28px',
        height: '28px',
        color: 'rgba(250, 250, 249, 0.82)',
    };

    const emptyStrongStyle = {
        color: '#fafaf9',
        fontFamily: 'var(--font-bangjeff-display)',
        fontSize: '0.98rem',
        lineHeight: 1.3,
    };

    const emptyTextStyle = {
        margin: 0,
        color: 'rgba(248, 250, 252, 0.86)',
        fontFamily: 'var(--font-bangjeff-meta)',
        fontSize: '0.92rem',
        lineHeight: 1.5,
    };

    const listStyle = {
        display: 'grid',
        gap: '10px',
        maxHeight: 'min(52vh, 420px)',
        overflowY: 'auto',
        paddingRight: '2px',
    };

    const cardStyle = {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: '14px',
        borderRadius: '14px',
        border: '1px solid rgba(255, 255, 255, 0.08)',
        background: '#323232',
        padding: '13px 14px',
    };

    const cardCopyStyle = {
        minWidth: 0,
        display: 'grid',
        gap: '4px',
    };

    const toplineStyle = {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        flexWrap: 'wrap',
    };

    const codeStyle = {
        color: '#fff7ed',
        fontFamily: 'var(--font-bangjeff-display)',
        fontSize: '0.97rem',
        lineHeight: 1.15,
    };

    const badgeStyle = {
        display: 'inline-flex',
        alignItems: 'center',
        minHeight: '22px',
        padding: '0 9px',
        borderRadius: '999px',
        background: 'rgba(249, 115, 22, 0.16)',
        color: '#fdba74',
        fontSize: '0.72rem',
        fontWeight: 700,
        lineHeight: 1,
    };

    const cardTextStyle = {
        margin: 0,
        color: 'rgba(248, 250, 252, 0.82)',
        fontFamily: 'var(--font-bangjeff-meta)',
        fontSize: '0.86rem',
        lineHeight: 1.45,
    };

    const cardMetaStyle = {
        margin: 0,
        color: 'rgba(248, 250, 252, 0.62)',
        fontFamily: 'var(--font-bangjeff-meta)',
        fontSize: '0.76rem',
        lineHeight: 1.35,
    };

    const getApplyButtonStyle = (promoCode) => ({
        flex: '0 0 auto',
        minWidth: '84px',
        minHeight: '34px',
        padding: '0 16px',
        borderRadius: '999px',
        border: '1px solid rgba(249, 115, 22, 0.36)',
        background: 'linear-gradient(180deg, #fb923c 0%, #f97316 100%)',
        color: '#fff7ed',
        fontSize: '0.78rem',
        fontWeight: 700,
        lineHeight: 1,
        boxShadow: '0 6px 14px rgba(249, 115, 22, 0.16)',
        cursor: applyingCode === promoCode ? 'progress' : 'pointer',
        opacity: applyingCode === promoCode ? 0.72 : 1,
        transition: 'filter 180ms ease, transform 180ms ease',
        transform: hoveredPromoCode === promoCode ? 'translateY(-1px)' : 'none',
        filter: hoveredPromoCode === promoCode ? 'brightness(1.03)' : 'none',
    });

    return (
        <div className="bangjeff-promo-modal" style={rootStyle} role="dialog" aria-modal="true" aria-labelledby="bangjeff-promo-modal-title">
            <div className="bangjeff-promo-modal__backdrop" style={backdropStyle} onClick={onClose} />
            <div className="bangjeff-promo-modal__viewport" style={viewportStyle} onClick={onClose}>
                <div className="bangjeff-promo-modal__panel" style={panelStyle} onClick={(event) => event.stopPropagation()}>
                    <button
                        type="button"
                        className="bangjeff-promo-modal__close"
                        style={closeStyle}
                        onClick={onClose}
                        onMouseEnter={() => setHoveredClose(true)}
                        onMouseLeave={() => setHoveredClose(false)}
                        aria-label="Tutup modal promo"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M6 18 18 6" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>

                    <h2 id="bangjeff-promo-modal-title" className="bangjeff-promo-modal__title" style={titleStyle}>Promo yang tersedia</h2>

                    <div className="bangjeff-promo-modal__body" style={bodyStyle}>
                        {loading ? (
                            <div className="bangjeff-promo-modal__empty" style={emptyStyle}>
                                <div className="bangjeff-promo-modal__empty-icon" style={emptyIconStyle} aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M20 12V8a2 2 0 0 0-2-2h-4l-2-2-2 2H6a2 2 0 0 0-2 2v4" />
                                        <path d="M4 14h16" />
                                        <path d="m9 10 6 4" />
                                        <path d="m15 10-6 4" />
                                    </svg>
                                </div>
                                <strong style={emptyStrongStyle}>Mencari promo yang tersedia...</strong>
                                <p style={emptyTextStyle}>Tunggu sebentar, kami sedang menyiapkan daftar promo terbaik untuk nominal ini.</p>
                            </div>
                        ) : promos.length ? (
                            <div className="bangjeff-promo-modal__list" style={listStyle}>
                                {promos.map((promo) => (
                                    <article key={promo.kode} className="bangjeff-promo-card" style={cardStyle}>
                                        <div className="bangjeff-promo-card__copy" style={cardCopyStyle}>
                                            <div className="bangjeff-promo-card__topline" style={toplineStyle}>
                                                <strong style={codeStyle}>{promo.kode}</strong>
                                                <span style={badgeStyle}>{promo.promo}% OFF</span>
                                            </div>
                                            <p style={cardTextStyle}>Potongan {formatCurrency(promo.discount_amount)} untuk total {formatCurrency(promo.final_price)}</p>
                                            <small style={cardMetaStyle}>Min. transaksi {formatCurrency(promo.mintrx || 0)} · Stok {promo.stock}</small>
                                        </div>
                                        <button
                                            type="button"
                                            className="bangjeff-promo-card__apply bangjeff-gradient-button"
                                            style={getApplyButtonStyle(promo.kode)}
                                            onClick={() => onApply(promo)}
                                            onMouseEnter={() => setHoveredPromoCode(promo.kode)}
                                            onMouseLeave={() => setHoveredPromoCode(null)}
                                            disabled={applyingCode === promo.kode}
                                        >
                                            {applyingCode === promo.kode ? 'Memakai...' : 'Pakai'}
                                        </button>
                                    </article>
                                ))}
                            </div>
                        ) : (
                            <div className="bangjeff-promo-modal__empty" style={emptyStyle}>
                                <div className="bangjeff-promo-modal__empty-icon" style={emptyIconStyle} aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M7 7h10l3 3-3 3H7l-3-3 3-3Z" />
                                        <path d="M12 9v2" />
                                        <path d="M12 13h.01" />
                                    </svg>
                                </div>
                                <strong style={emptyStrongStyle}>Tidak ada promo yang tersedia!</strong>
                                <p style={emptyTextStyle}>Silahkan cek kembali nanti!</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

function BangjeffOrderPreviewModal({
    open,
    onClose,
    onConfirm,
    rows,
    submitting = false,
    agreementChecked = true,
    onAgreementChange = null,
    errorMessage = '',
    canConfirm = false,
}) {
    const confettiPieces = useMemo(() => {
        const colors = ['#22d3ee', '#a3e635', '#f472b6', '#facc15', '#38bdf8', '#fb7185', '#a78bfa', '#f97316'];
        return Array.from({ length: 44 }, (_, index) => ({
            id: index,
            left: `${Math.round((index / 44) * 100)}%`,
            delay: `${(index % 8) * 0.12}s`,
            duration: `${2.8 + ((index % 5) * 0.35)}s`,
            rotate: `${(index * 17) % 360}deg`,
            color: colors[index % colors.length],
            width: `${6 + (index % 5)}px`,
            height: `${2 + (index % 3)}px`,
        }));
    }, []);

    if (!open) {
        return null;
    }

    return (
        <div className="bangjeff-order-preview-modal" role="dialog" aria-modal="true" aria-labelledby="bangjeff-order-preview-title">
            <div className="bangjeff-order-preview-modal__backdrop" onClick={() => (!submitting ? onClose?.() : null)} />
            <div className="bangjeff-order-preview-modal__confetti" aria-hidden="true">
                {confettiPieces.map((piece) => (
                    <span
                        key={piece.id}
                        style={{
                            left: piece.left,
                            animationDelay: piece.delay,
                            animationDuration: piece.duration,
                            transform: `rotate(${piece.rotate})`,
                            backgroundColor: piece.color,
                            width: piece.width,
                            height: piece.height,
                        }}
                    />
                ))}
            </div>
            <div className="bangjeff-order-preview-modal__viewport" onClick={() => (!submitting ? onClose?.() : null)}>
                <div className="bangjeff-order-preview-modal__panel" onClick={(event) => event.stopPropagation()}>
                    <div className="bangjeff-order-preview-modal__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="m5 13 4 4L19 7" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </div>
                    <h3 id="bangjeff-order-preview-title">Buat Pesanan</h3>
                    <p>Pastikan data akun kamu dan produk yang kamu pilih sudah benar.</p>

                    <div className="bangjeff-order-preview-modal__summary">
                        {rows.map((row) => (
                            <div key={row.label} className="bangjeff-order-preview-modal__row">
                                <span>{row.label}</span>
                                <strong>{row.value}</strong>
                            </div>
                        ))}
                    </div>

                    <label className="bangjeff-order-preview-modal__agreement">
                        <input
                            type="checkbox"
                            checked={agreementChecked}
                            onChange={(event) => onAgreementChange?.(event.target.checked)}
                            disabled={submitting}
                        />
                        <span>Dengan mengklik <em>Pesan Sekarang</em>, kamu menyetujui <a href="/id/terms-and-condition" target="_blank" rel="noreferrer">Syarat & Ketentuan</a>.</span>
                    </label>

                    {errorMessage ? <div className="bangjeff-order-preview-modal__error">{errorMessage}</div> : null}

                    <div className="bangjeff-order-preview-modal__actions">
                        <button
                            type="button"
                            className="bangjeff-order-preview-modal__confirm"
                            onClick={onConfirm}
                            disabled={!canConfirm || submitting}
                        >
                            {submitting ? 'Memproses...' : 'Pesan Sekarang!'}
                        </button>
                        <button
                            type="button"
                            className="bangjeff-order-preview-modal__cancel"
                            onClick={onClose}
                            disabled={submitting}
                        >
                            Batalkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function Order({ meta, category, products, packages, paymentMethods, ratings, gtm }) {
    const { theme, authUser, siteConfig } = usePage().props;
    const [mobileOrderTab, setMobileOrderTab] = useState('transaction');
    const [selectedPackage, setSelectedPackage] = useState(0);
    const [selectedProductId, setSelectedProductId] = useState(products[0]?.id ?? packages[0]?.items?.[0]?.id ?? null);
    const [selectedMethodCode, setSelectedMethodCode] = useState(null);
    const [voucher, setVoucher] = useState('');
    const [uid, setUid] = useState('');
    const [zone, setZone] = useState('');
    const [phone, setPhone] = useState('');
    const [email, setEmail] = useState('');
    const [nickname, setNickname] = useState('');
    const [pricePreview, setPricePreview] = useState(null);
    const [priceLoading, setPriceLoading] = useState(false);
    const [usePoint, setUsePoint] = useState(0);
    const priceRequestSequenceRef = useRef(0);
    const [checkLoading, setCheckLoading] = useState(false);
    const [submitLoading, setSubmitLoading] = useState(false);
    const [message, setMessage] = useState(null);
    const [activeFaqIndex, setActiveFaqIndex] = useState(0);
    const [specialForm, setSpecialForm] = useState(() => buildInitialSpecialForm(category.specialFields));
    const [accountLookup, setAccountLookup] = useState(null);
    const [openPaymentGroup, setOpenPaymentGroup] = useState(null);
    const [showLoginRequiredModal, setShowLoginRequiredModal] = useState(false);
    const [showSupportModal, setShowSupportModal] = useState(false);
    const [showAvailablePromoModal, setShowAvailablePromoModal] = useState(false);
    const [voucherActionLoading, setVoucherActionLoading] = useState(null);
    const [availablePromos, setAvailablePromos] = useState([]);
    const [expandedVariantGroups, setExpandedVariantGroups] = useState({});
    const [mobileCheckoutExpanded, setMobileCheckoutExpanded] = useState(false);
    const submitRequestInFlightRef = useRef(false);
    const preventAutoSelectRef = useRef(false);
    const savedAccountQuickFillRef = useRef(null);
    const [savedAccountDraft, setSavedAccountDraft] = useState(null);
    const [savedAccountDropdownOpen, setSavedAccountDropdownOpen] = useState(false);
    const [showOrderPreviewModal, setShowOrderPreviewModal] = useState(false);
    const [orderAgreementChecked, setOrderAgreementChecked] = useState(true);
    const [orderPreviewError, setOrderPreviewError] = useState('');
    const [accountStepInteracted, setAccountStepInteracted] = useState(false);
    const [nominalStepInteracted, setNominalStepInteracted] = useState(false);
    const [quantityStepInteracted, setQuantityStepInteracted] = useState(false);
    const [paymentStepInteracted, setPaymentStepInteracted] = useState(false);
    const accountPanelRef = useRef(null);
    const nominalPanelRef = useRef(null);
    const quantityPanelRef = useRef(null);
    const paymentPanelRef = useRef(null);
    const contactPanelRef = useRef(null);
    const accountAutoScrollDoneRef = useRef(false);
    const nominalAutoScrollDoneRef = useRef(false);
    const quantityAutoScrollDoneRef = useRef(false);
    const paymentAutoScrollDoneRef = useRef(false);

    const isBangjeff = theme?.key === 'bangjeff';
    const isComplexOrder = category.orderMode === 'complex';
    const variantGroups = useMemo(() => {
        if (packages.length) {
            return packages.map((item) => ({
                name: item.name,
                items: item.items || [],
            }));
        }

        return [
            {
                name: null,
                items: products,
            },
        ];
    }, [packages, products]);
    const activeVariantGroup = variantGroups[selectedPackage] ?? variantGroups[0] ?? { items: [] };
    const variantItems = activeVariantGroup.items ?? [];
    const allVariantItems = useMemo(
        () => variantGroups.flatMap((group) => group.items ?? []),
        [variantGroups],
    );
    const allCatalogItems = useMemo(() => [...products, ...packages.flatMap((item) => item.items || [])], [packages, products]);
    const selectedProduct = useMemo(
        () => allCatalogItems.find((item) => item.id === selectedProductId) || null,
        [allCatalogItems, selectedProductId],
    );
    const gtmItemCatalog = gtm?.itemCatalog || {};
    const gtmPaymentMethods = gtm?.paymentMethods || {};
    const gtmViewItemPayload = gtm?.viewItemPayload || null;
    const selectedGtmItem = selectedProductId ? (gtmItemCatalog[String(selectedProductId)] || null) : null;
    const selectedGtmPaymentMethod = selectedMethodCode ? (gtmPaymentMethods[String(selectedMethodCode)] || null) : null;
    const selectedMethod = paymentMethods.find((item) => item.code === selectedMethodCode) || null;
    const groupedMethods = useMemo(() => paymentMethods.reduce((acc, method) => {
        const key = method.groupLabel || method.group || 'lainnya';
        if (!acc[key]) acc[key] = [];
        acc[key].push(method);
        return acc;
    }, {}), [paymentMethods]);
    const methodEntries = useMemo(() => Object.entries(groupedMethods), [groupedMethods]);
    const featuredPaymentMethods = useMemo(() => {
        const picked = [];
        const pickedCodes = new Set();

        const priorities = [
            (method) => isBangjeffBalanceMethod(method),
            (method) => isBangjeffQrisMethod(method) && !isBangjeffShopeeMethod(method),
            (method) => isBangjeffShopeeMethod(method),
            (method) => String(method.group || '').toLowerCase() === 'e-walet',
        ];

        priorities.forEach((matcher) => {
            const found = paymentMethods.find((method) => !pickedCodes.has(method.code) && matcher(method));
            if (!found) {
                return;
            }

            picked.push(found);
            pickedCodes.add(found.code);
        });

        paymentMethods.forEach((method) => {
            if (picked.length >= 3 || pickedCodes.has(method.code)) {
                return;
            }

            picked.push(method);
            pickedCodes.add(method.code);
        });

        return picked.slice(0, 3);
    }, [paymentMethods]);
    const featuredPaymentCodes = useMemo(
        () => new Set(featuredPaymentMethods.map((method) => method.code)),
        [featuredPaymentMethods],
    );
    const groupedMethodEntries = useMemo(
        () => methodEntries
            .map(([group, methods]) => [group, methods.filter((method) => !featuredPaymentCodes.has(method.code))])
            .filter(([, methods]) => methods.length),
        [featuredPaymentCodes, methodEntries],
    );
    const specialFieldsWithoutQty = useMemo(
        () => category.specialFields.filter((field) => field.name !== 'qty'),
        [category.specialFields],
    );
    const faqItems = useMemo(
        () => buildFaqItems(category, isComplexOrder, siteConfig?.name || 'website ini'),
        [category, isComplexOrder, siteConfig?.name],
    );
    const canUseBangjeffBalancePayment = isBangjeff && ['Member', 'Gold', 'Platinum'].includes(String(authUser?.role || ''));
    const requiresLoginForBalancePayment = isBangjeff && !canUseBangjeffBalancePayment;
    const quantityEnabled = useMemo(
        () => category.specialFields.some((field) => field.name === 'qty'),
        [category.specialFields],
    );

    useEffect(() => {
        if (!showLoginRequiredModal) {
            return undefined;
        }

        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setShowLoginRequiredModal(false);
            }
        };

        window.addEventListener('keydown', handleEscape);

        return () => window.removeEventListener('keydown', handleEscape);
    }, [showLoginRequiredModal]);

    useEffect(() => {
        if (!showAvailablePromoModal) {
            return undefined;
        }

        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setShowAvailablePromoModal(false);
            }
        };

        window.addEventListener('keydown', handleEscape);

        return () => window.removeEventListener('keydown', handleEscape);
    }, [showAvailablePromoModal]);

    useEffect(() => {
        if (!showSupportModal) {
            return undefined;
        }

        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setShowSupportModal(false);
            }
        };

        window.addEventListener('keydown', handleEscape);

        return () => window.removeEventListener('keydown', handleEscape);
    }, [showSupportModal]);

    useEffect(() => {
        if (!selectedProduct) {
            setMobileCheckoutExpanded(false);
        }
    }, [selectedProduct]);

    useEffect(() => {
        if (!requiresLoginForBalancePayment) {
            return undefined;
        }

        const handleDelegatedLoginRequired = (event) => {
            const trigger = event.target instanceof Element
                ? event.target.closest('[data-bangjeff-login-required="true"]')
                : null;

            if (!trigger) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            setShowLoginRequiredModal(true);
        };

        document.addEventListener('click', handleDelegatedLoginRequired, true);

        return () => {
            document.removeEventListener('click', handleDelegatedLoginRequired, true);
        };
    }, [requiresLoginForBalancePayment]);

    const handleBangjeffPaymentSelect = (methodOrCode) => {
        const method = typeof methodOrCode === 'string'
            ? paymentMethods.find((entry) => entry.code === methodOrCode) ?? null
            : methodOrCode;

        if (!method) {
            if (typeof methodOrCode === 'string') {
                setPaymentStepInteracted(true);
                setSelectedMethodCode(methodOrCode);
            }

            return;
        }

        if (requiresLoginForBalancePayment && isBangjeffBalanceMethod(method)) {
            setShowLoginRequiredModal(true);
            return;
        }

        setPaymentStepInteracted(true);
        setSelectedMethodCode(method.code);
    };
    const openBangjeffLoginRequiredModal = () => setShowLoginRequiredModal(true);
    const closeBangjeffLoginRequiredModal = () => setShowLoginRequiredModal(false);
    const openBangjeffSupportModal = () => setShowSupportModal(true);
    const closeBangjeffSupportModal = () => setShowSupportModal(false);
    const closeAvailablePromoModal = () => setShowAvailablePromoModal(false);
    const heroDescription = useMemo(
        () => stripHtml(category.fieldDescription || category.description || category.subtitle || '') || `Top up ${category.name} dengan proses cepat dan metode pembayaran lengkap.`,
        [category.description, category.fieldDescription, category.name, category.subtitle],
    );
    const heroMetaItems = useMemo(() => ([
        {
            key: 'speed',
            label: 'Proses Cepat',
            icon: (
                <img src="/assets/thumbnail/lightning.gif" alt="Proses cepat" />
            ),
        },
        {
            key: 'support',
            label: 'Layanan Chat 24/7',
            icon: (
                <img src="/assets/thumbnail/contact-support.gif" alt="Layanan cepat" />
            ),
        },
        {
            key: 'secure',
            label: 'Pembayaran Aman!',
            icon: (
                <img src="/assets/thumbnail/secure.gif" alt="Pembayaran aman" />
            ),
        },
    ]), []);
    const ratingSummary = useMemo(() => {
        const total = Array.isArray(ratings) ? ratings.length : 0;
        const average = total ? ratings.reduce((sum, item) => sum + Number(item.stars || 0), 0) / total : 0;

        return { total, average };
    }, [ratings]);
    const supportModalTitle = useMemo(
        () => String(siteConfig?.support?.title || 'Customer Support'),
        [siteConfig?.support?.title],
    );
    const supportItems = useMemo(() => {
        const socials = siteConfig?.socials || {};
        const mapped = [
            { key: 'whatsapp', label: 'Whatsapp', href: socials.whatsapp || null },
            { key: 'instagram', label: 'Instagram', href: socials.instagram || null },
            { key: 'facebook', label: 'Facebook', href: socials.facebook || null },
            { key: 'tiktok', label: 'TikTok', href: socials.tiktok || null },
            { key: 'youtube', label: 'YouTube', href: socials.youtube || null },
        ].filter((item) => Boolean(item.href));

        if (mapped.length) {
            return mapped;
        }

        return [
            {
                key: 'form',
                label: 'Form Hubungi Kami',
                href: '/id/terms-and-condition',
            },
        ];
    }, [siteConfig?.socials]);
    const accountDraftStorageKey = useMemo(
        () => `${ORDER_ACCOUNT_DRAFT_STORAGE_PREFIX}${category.slug}`,
        [category.slug],
    );

    const resetOrderAfterCheckout = useCallback(() => {
        preventAutoSelectRef.current = true;

        setMobileOrderTab('transaction');
        setSelectedPackage(0);
        setSelectedProductId(null);
        setSelectedMethodCode(null);
        setVoucher('');
        setUid('');
        setZone('');
        setPhone('');
        setEmail('');
        setNickname('');
        setPricePreview(null);
        setPriceLoading(false);
        setUsePoint(0);
        setCheckLoading(false);
        setSubmitLoading(false);
        setMessage(null);
        setAccountLookup(null);
        setOpenPaymentGroup(null);
        setShowLoginRequiredModal(false);
        setShowSupportModal(false);
        setShowAvailablePromoModal(false);
        setVoucherActionLoading(null);
        setAvailablePromos([]);
        setExpandedVariantGroups({});
        setMobileCheckoutExpanded(false);
        setSavedAccountDropdownOpen(false);
        setShowOrderPreviewModal(false);
        setOrderAgreementChecked(true);
        setOrderPreviewError('');
        setAccountStepInteracted(false);
        setNominalStepInteracted(false);
        setQuantityStepInteracted(false);
        setPaymentStepInteracted(false);
        setSpecialForm(buildInitialSpecialForm(category.specialFields));
        accountAutoScrollDoneRef.current = false;
        nominalAutoScrollDoneRef.current = false;
        quantityAutoScrollDoneRef.current = false;
        paymentAutoScrollDoneRef.current = false;
    }, [category.specialFields]);

    const handleApplySavedAccountDraft = () => {
        if (!savedAccountDraft?.uid) {
            return;
        }

        setUid(String(savedAccountDraft.uid || ''));

        if (category.customInputs.zone && savedAccountDraft.zone) {
            setZone(String(savedAccountDraft.zone || ''));
        }

        if (savedAccountDraft.nickname) {
            setNickname(String(savedAccountDraft.nickname || ''));
        }

        setAccountStepInteracted(true);
        setSavedAccountDropdownOpen(false);
        setMessage({ type: 'info', text: 'Data akun terakhir berhasil digunakan.' });
    };

    const openSavedAccountQuickFillFromInput = useCallback(() => {
        const canOpen = Boolean(
            isBangjeff
            && !isComplexOrder
            && category.requireUserId
            && String(savedAccountDraft?.uid || '').trim(),
        );

        if (!canOpen) {
            return;
        }

        setSavedAccountDropdownOpen(true);
    }, [category.requireUserId, isBangjeff, isComplexOrder, savedAccountDraft?.uid]);

    const persistSavedAccountDraftOnSuccessfulOrder = useCallback(() => {
        if (typeof window === 'undefined' || isComplexOrder || !category.requireUserId) {
            return;
        }

        const nextUid = String(uid || '').trim();
        if (!nextUid) {
            return;
        }

        try {
            const existingDraftRaw = window.localStorage.getItem(accountDraftStorageKey);
            if (existingDraftRaw) {
                const existingDraft = JSON.parse(existingDraftRaw);
                if (existingDraft?.uid) {
                    setSavedAccountDraft({
                        uid: String(existingDraft.uid || '').trim(),
                        zone: String(existingDraft.zone || '').trim(),
                        nickname: String(existingDraft.nickname || '').trim(),
                        updatedAt: Number(existingDraft.updatedAt || Date.now()),
                    });
                    return;
                }
            }
        } catch (error) {
            // Ignore parse errors and continue writing a fresh draft.
        }

        const nextDraft = {
            uid: nextUid,
            zone: category.customInputs.zone ? String(zone || '').trim() : '',
            nickname: String(nickname || '').trim(),
            updatedAt: Date.now(),
        };

        try {
            window.localStorage.setItem(accountDraftStorageKey, JSON.stringify(nextDraft));
        } catch (error) {
            // Ignore localStorage failures (private mode / quota exceeded).
        }

        setSavedAccountDraft(nextDraft);
    }, [accountDraftStorageKey, category.customInputs.zone, category.requireUserId, isComplexOrder, nickname, uid, zone]);

    useEffect(() => {
        if (typeof window === 'undefined' || isComplexOrder || !category.requireUserId) {
            setSavedAccountDraft(null);
            return;
        }

        try {
            const rawDraft = window.localStorage.getItem(accountDraftStorageKey);
            if (!rawDraft) {
                setSavedAccountDraft(null);
                return;
            }

            const parsedDraft = JSON.parse(rawDraft);
            if (!parsedDraft || !String(parsedDraft.uid || '').trim()) {
                setSavedAccountDraft(null);
                return;
            }

            setSavedAccountDraft({
                uid: String(parsedDraft.uid || '').trim(),
                zone: String(parsedDraft.zone || '').trim(),
                nickname: String(parsedDraft.nickname || '').trim(),
                updatedAt: Number(parsedDraft.updatedAt || Date.now()),
            });
        } catch (error) {
            setSavedAccountDraft(null);
        }
    }, [accountDraftStorageKey, category.requireUserId, isComplexOrder]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return undefined;
        }

        const consumeResetFlag = () => {
            try {
                const rawFlag = window.sessionStorage.getItem(ORDER_RESET_AFTER_INVOICE_KEY);
                if (!rawFlag) {
                    return false;
                }

                const parsedFlag = JSON.parse(rawFlag);
                const isSamePath = parsedFlag?.path === window.location.pathname;
                const isFreshFlag = Number(parsedFlag?.at || 0) > 0 && (Date.now() - Number(parsedFlag.at)) < (1000 * 60 * 30);

                if (!isSamePath || !isFreshFlag) {
                    window.sessionStorage.removeItem(ORDER_RESET_AFTER_INVOICE_KEY);
                    return false;
                }

                window.sessionStorage.removeItem(ORDER_RESET_AFTER_INVOICE_KEY);
                return true;
            } catch (error) {
                window.sessionStorage.removeItem(ORDER_RESET_AFTER_INVOICE_KEY);
                return false;
            }
        };

        const handlePageShow = () => {
            if (!consumeResetFlag()) {
                return;
            }

            resetOrderAfterCheckout();
        };

        handlePageShow();
        window.addEventListener('pageshow', handlePageShow);

        return () => window.removeEventListener('pageshow', handlePageShow);
    }, [resetOrderAfterCheckout]);

    useEffect(() => {
        if (!savedAccountDropdownOpen) {
            return undefined;
        }

        const handleOutsidePointer = (event) => {
            if (!savedAccountQuickFillRef.current?.contains(event.target)) {
                setSavedAccountDropdownOpen(false);
            }
        };

        document.addEventListener('mousedown', handleOutsidePointer);
        document.addEventListener('touchstart', handleOutsidePointer, { passive: true });

        return () => {
            document.removeEventListener('mousedown', handleOutsidePointer);
            document.removeEventListener('touchstart', handleOutsidePointer);
        };
    }, [savedAccountDropdownOpen]);

    useEffect(() => {
        setSpecialForm(buildInitialSpecialForm(category.specialFields));
    }, [category.specialFields]);

    useEffect(() => {
        if (selectedPackage >= variantGroups.length) {
            setSelectedPackage(0);
        }
    }, [selectedPackage, variantGroups.length]);

    useEffect(() => {
        const candidateItems = isBangjeff ? allVariantItems : variantItems;

        if (!selectedProductId && candidateItems[0]) {
            if (preventAutoSelectRef.current) {
                return;
            }

            setSelectedProductId(candidateItems[0].id);
            return;
        }

        if (selectedProductId && candidateItems.length && !candidateItems.some((item) => item.id === selectedProductId)) {
            if (preventAutoSelectRef.current) {
                setSelectedProductId(null);
                return;
            }

            setSelectedProductId(candidateItems[0]?.id ?? null);
        }
    }, [allVariantItems, isBangjeff, selectedProductId, variantItems]);

    useEffect(() => {
        if (activeFaqIndex >= faqItems.length) {
            setActiveFaqIndex(0);
        }
    }, [activeFaqIndex, faqItems.length]);

    useEffect(() => {
        setMobileOrderTab('transaction');
        setOpenPaymentGroup(null);
        setSavedAccountDropdownOpen(false);
        setShowOrderPreviewModal(false);
        setOrderPreviewError('');
        setOrderAgreementChecked(true);
        setAccountStepInteracted(false);
        setNominalStepInteracted(false);
        setQuantityStepInteracted(false);
        setPaymentStepInteracted(false);
        accountAutoScrollDoneRef.current = false;
        nominalAutoScrollDoneRef.current = false;
        quantityAutoScrollDoneRef.current = false;
        paymentAutoScrollDoneRef.current = false;
        preventAutoSelectRef.current = false;
    }, [category.slug]);

    useEffect(() => {
        if (!groupedMethodEntries.length) {
            setOpenPaymentGroup(null);
            return;
        }

        if (openPaymentGroup && !groupedMethodEntries.some(([group]) => group === openPaymentGroup)) {
            setOpenPaymentGroup(null);
        }
    }, [groupedMethodEntries, openPaymentGroup]);

    useEffect(() => {
        if (isComplexOrder || !category.requiresGameValidation || !isBangjeff) {
            return undefined;
        }

        const currentUid = String(uid || '').trim();
        if (!currentUid) {
            setNickname('');
            setAccountLookup(null);
            return undefined;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setCheckLoading(true);

            try {
                const body = new URLSearchParams();
                body.append('uid', currentUid);
                body.append('kategori_kode', category.slug);
                if (zone) body.append('zone', zone);

                const response = await fetch('/ajax/check-account', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json',
                    },
                    body,
                    signal: controller.signal,
                });

                const payload = await response.json();

                if (payload?.status?.code === 200) {
                    const username = payload?.data?.username || currentUid;
                    setNickname(username);
                    setAccountLookup({
                        type: 'success',
                        username,
                        location: 'Indonesia',
                    });
                    return;
                }

                if (payload?.skip_check) {
                    setAccountLookup({
                        type: 'info',
                        text: 'Validasi akun tidak diperlukan untuk kategori ini.',
                    });
                    return;
                }

                setNickname('');
                setAccountLookup({
                    type: 'error',
                    text: payload?.status?.message || 'Akun tidak ditemukan.',
                });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setNickname('');
                    setAccountLookup({
                        type: 'error',
                        text: 'Gagal melakukan validasi akun.',
                    });
                }
            } finally {
                setCheckLoading(false);
            }
        }, 420);

        return () => {
            controller.abort();
            window.clearTimeout(timer);
        };
    }, [category.requiresGameValidation, category.slug, isBangjeff, isComplexOrder, uid, zone]);

    const quantity = useMemo(() => {
        const raw = Number(specialForm.qty || 1);
        return Number.isFinite(raw) && raw > 0 ? raw : 1;
    }, [specialForm.qty]);

    const accountStepReady = useMemo(() => {
        if (isComplexOrder) {
            return specialFieldsWithoutQty.every((field) => String(specialForm[field.name] ?? '').trim() !== '');
        }

        if (category.requireUserId && !String(uid || '').trim()) {
            return false;
        }

        if (category.customInputs.zone && !String(zone || '').trim()) {
            return false;
        }

        return true;
    }, [category.customInputs.zone, category.requireUserId, isComplexOrder, specialFieldsWithoutQty, specialForm, uid, zone]);

    const scrollToOrderPanel = useCallback((targetRef) => {
        if (!isBangjeff || !targetRef?.current || typeof window === 'undefined') {
            return;
        }

        const offset = window.innerWidth < 1024 ? 94 : 108;
        const top = Math.max(0, window.scrollY + targetRef.current.getBoundingClientRect().top - offset);

        window.requestAnimationFrame(() => {
            window.scrollTo({
                top,
                behavior: 'smooth',
            });
        });
    }, [isBangjeff]);

    useEffect(() => {
        if (!accountStepReady) {
            accountAutoScrollDoneRef.current = false;
            return;
        }

        if (!accountStepInteracted || accountAutoScrollDoneRef.current) {
            return;
        }

        scrollToOrderPanel(nominalPanelRef);
        accountAutoScrollDoneRef.current = true;
    }, [accountStepInteracted, accountStepReady, scrollToOrderPanel]);

    useEffect(() => {
        if (!selectedProductId) {
            nominalAutoScrollDoneRef.current = false;
            return;
        }

        if (!nominalStepInteracted || nominalAutoScrollDoneRef.current) {
            return;
        }

        scrollToOrderPanel(paymentPanelRef);
        nominalAutoScrollDoneRef.current = true;
    }, [nominalStepInteracted, scrollToOrderPanel, selectedProductId]);

    useEffect(() => {
        if (!quantityEnabled) {
            quantityAutoScrollDoneRef.current = false;
            return;
        }

        if (!quantityStepInteracted || quantityAutoScrollDoneRef.current) {
            return;
        }

        scrollToOrderPanel(paymentPanelRef);
        quantityAutoScrollDoneRef.current = true;
    }, [quantityEnabled, quantityStepInteracted, scrollToOrderPanel]);

    useEffect(() => {
        if (!selectedMethodCode) {
            paymentAutoScrollDoneRef.current = false;
            return;
        }

        if (!paymentStepInteracted || paymentAutoScrollDoneRef.current) {
            return;
        }

        scrollToOrderPanel(contactPanelRef);
        paymentAutoScrollDoneRef.current = true;
    }, [paymentStepInteracted, scrollToOrderPanel, selectedMethodCode]);

    useEffect(() => {
        if (!gtmViewItemPayload || typeof window === 'undefined' || typeof window.pushDataLayerEvent !== 'function') {
            return;
        }

        window.pushDataLayerEvent('view_item', gtmViewItemPayload, {
            dedupeKey: `view_item:${category.slug}`,
        });
    }, [category.slug, gtmViewItemPayload]);

    useEffect(() => {
        const requestSequence = priceRequestSequenceRef.current + 1;
        priceRequestSequenceRef.current = requestSequence;

        if (!selectedProductId) {
            setPricePreview(null);
            setPriceLoading(false);
            return undefined;
        }

        const controller = new AbortController();
        const loadPrice = async () => {
            setPriceLoading(true);
            try {
                const body = new URLSearchParams();
                body.append('nominal', selectedProductId);
                body.append('ktg_tipe', category.type);
                body.append('qty', String(quantity));
                if (selectedMethodCode) body.append('payment_method', selectedMethodCode);
                if (voucher) body.append('voucher', voucher);
                body.append('use_point', String(usePoint));

                const response = await fetch('/id/harga', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-CSRF-TOKEN': csrfToken(),
                        Accept: 'application/json',
                    },
                    body,
                    signal: controller.signal,
                });

                if (!response.ok || requestSequence !== priceRequestSequenceRef.current) {
                    return;
                }

                const payload = await response.json();
                if (requestSequence === priceRequestSequenceRef.current) {
                    setPricePreview(payload);
                }
            } catch (error) {
                if (error.name !== 'AbortError' && requestSequence === priceRequestSequenceRef.current) {
                    console.warn('Failed to load price preview', error);
                }
            } finally {
                if (requestSequence === priceRequestSequenceRef.current) {
                    setPriceLoading(false);
                }
            }
        };

        loadPrice();

        return () => controller.abort();
    }, [category.type, quantity, selectedMethodCode, selectedProductId, usePoint, voucher]);

    useEffect(() => {
        if (selectedProductId) {
            setPricePreview(null);
            setPriceLoading(true);
        }
    }, [quantity, selectedMethodCode, selectedProductId, usePoint, voucher]);

    useEffect(() => {
        setUsePoint(0);
    }, [authUser?.id, category.slug, selectedProductId]);

    useEffect(() => {
        if (!authUser) {
            setUsePoint(0);
        }
    }, [authUser]);

    useEffect(() => {
        if (!pricePreview?.point_info) {
            return;
        }

        const backendMaxPoints = Number(pricePreview.point_info.max_points);
        const normalizedMaxPoints = Number.isFinite(backendMaxPoints) && backendMaxPoints > 0
            ? Math.floor(backendMaxPoints)
            : 0;
        const normalizedUsePoint = Number(usePoint);
        const boundedUsePoint = Number.isFinite(normalizedUsePoint)
            ? Math.max(0, Math.min(Math.floor(normalizedUsePoint), normalizedMaxPoints))
            : 0;

        if (boundedUsePoint !== normalizedUsePoint) {
            setUsePoint(boundedUsePoint);
        }
    }, [pricePreview, usePoint]);

    const handleCheckAccount = async () => {
        if (isComplexOrder || !category.requiresGameValidation) {
            return;
        }

        if (!uid) {
            setMessage({ type: 'error', text: 'User ID wajib diisi terlebih dahulu.' });
            return;
        }

        setCheckLoading(true);
        setMessage(null);

        try {
            const body = new URLSearchParams();
            body.append('uid', uid);
            body.append('kategori_kode', category.slug);
            if (zone) body.append('zone', zone);

            const response = await fetch('/ajax/check-account', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body,
            });

            const payload = await response.json();
            if (payload?.status?.code === 200) {
                setNickname(payload.data.username);
                setMessage({ type: 'success', text: `Akun ditemukan: ${payload.data.username}` });
                return;
            }

            if (payload?.skip_check) {
                setMessage({ type: 'info', text: 'Kategori ini tidak membutuhkan validasi akun otomatis.' });
                return;
            }

            setMessage({ type: 'error', text: payload?.status?.message || 'Akun tidak ditemukan.' });
        } catch (error) {
            setMessage({ type: 'error', text: 'Gagal melakukan validasi akun.' });
        } finally {
            setCheckLoading(false);
        }
    };

    const handleSpecialFieldChange = (name, value) => {
        if (name === 'qty') {
            setQuantityStepInteracted(true);
        } else {
            setAccountStepInteracted(true);
        }

        setSpecialForm((current) => ({
            ...current,
            [name]: value,
        }));
    };

    const adjustQuantity = (delta) => {
        const nextValue = Math.max(1, Math.min(30, quantity + delta));
        handleSpecialFieldChange('qty', String(nextValue));
    };

    const applyVoucherPreview = (nextVoucherCode) => {
        setVoucher(nextVoucherCode);
        setPricePreview(null);
    };

    const resetStaleVoucherState = () => {
        setVoucher('');
        setAvailablePromos([]);
        setShowAvailablePromoModal(false);
        setPricePreview(null);
        setVoucherActionLoading(null);
    };

    const handleApplyVoucher = async () => {
        const nextVoucher = String(voucher || '').trim();

        if (!selectedProductId) {
            setMessage({ type: 'error', text: 'Pilih nominal terlebih dahulu sebelum memakai promo.' });
            return;
        }

        if (!nextVoucher) {
            setMessage({ type: 'error', text: 'Masukkan kode promo terlebih dahulu.' });
            return;
        }

        setVoucherActionLoading('apply');
        setMessage(null);

        try {
            const body = new URLSearchParams();
            body.append('voucher', nextVoucher);
            body.append('service', String(selectedProductId));

            const response = await fetch('/check-voucher', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });

            const payload = await response.json();

            if (!response.ok || payload?.status !== true) {
                setMessage({ type: 'error', text: payload?.message || 'Kode promo tidak bisa digunakan.' });
                return;
            }

            if (typeof payload?.harga === 'number') {
                applyVoucherPreview(nextVoucher);
            }

            setMessage({ type: 'success', text: `Promo ${nextVoucher} berhasil dipakai.` });
        } catch (error) {
            setMessage({ type: 'error', text: 'Gagal memvalidasi kode promo.' });
        } finally {
            setVoucherActionLoading(null);
        }
    };

    const handleApplyAvailableVoucher = async () => {
        if (!selectedProductId) {
            setMessage({ type: 'error', text: 'Pilih nominal terlebih dahulu sebelum mencari promo.' });
            return;
        }

        setVoucherActionLoading('available');
        setMessage(null);
        setAvailablePromos([]);
        setShowAvailablePromoModal(true);

        try {
            const body = new URLSearchParams();
            body.append('service', String(selectedProductId));

            const response = await fetch('/available-voucher', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });

            const payload = await response.json();

            if (!response.ok || payload?.status !== true) {
                setShowAvailablePromoModal(false);
                setMessage({ type: 'error', text: payload?.message || 'Gagal mengambil promo yang tersedia.' });
                return;
            }

            setAvailablePromos(Array.isArray(payload?.vouchers) ? payload.vouchers : []);
        } catch (error) {
            setShowAvailablePromoModal(false);
            setMessage({ type: 'error', text: 'Gagal mengambil promo yang tersedia.' });
        } finally {
            setVoucherActionLoading(null);
        }
    };

    const handleSelectAvailablePromo = (promo) => {
        if (!promo?.kode) {
            return;
        }

        setVoucherActionLoading(promo.kode);
        setVoucher(promo.kode);

        if (typeof promo.final_price === 'number') {
            applyVoucherPreview(promo.kode);
        }

        setMessage({ type: 'success', text: `Promo ${promo.kode} berhasil dipakai.` });
        setShowAvailablePromoModal(false);

        window.setTimeout(() => {
            setVoucherActionLoading((current) => (current === promo.kode ? null : current));
        }, 120);
    };

    const validateBeforeSubmit = () => {
        if (!selectedProductId) {
            return 'Pilih nominal terlebih dahulu.';
        }

        if (!selectedMethodCode) {
            return 'Pilih metode pembayaran terlebih dahulu.';
        }

        const contactEmail = String(email || '').trim();
        const contactPhone = String(phone || '').trim();

        if (!contactEmail && !contactPhone) {
            return 'Isi minimal salah satu: email atau nomor WhatsApp.';
        }

        if (isComplexOrder) {
            for (const field of category.specialFields) {
                if (field.required && !String(specialForm[field.name] ?? '').trim()) {
                    return `${field.label} wajib diisi.`;
                }
            }

            return null;
        }

        if (category.requireUserId && !uid) {
            return 'Data akun wajib diisi terlebih dahulu.';
        }

        return null;
    };

    const orderValidationMessage = validateBeforeSubmit();
    const isOrderReady = !orderValidationMessage;

    const buildOrderSubmitPayload = () => {
        const body = new URLSearchParams();
        body.append('service', String(selectedProductId));
        body.append('payment_method', String(selectedMethodCode));
        body.append('nomor', String(phone).trim());
        body.append('kategori_kode', category.slug);
        body.append('ktg_tipe', category.type);
        body.append('qty', String(quantity));

        const nextVoucher = String(voucher || '').trim();
        const nextEmail = String(email || '').trim();

        if (nextVoucher) {
            body.append('voucher', nextVoucher);
        }

        if (nextEmail) {
            body.append('email', nextEmail);
        }

        body.append('use_point', String(usePoint));

        if (isComplexOrder) {
            category.specialFields.forEach((field) => {
                if (field.name === 'qty') {
                    return;
                }
                body.append(field.name, String(specialForm[field.name] ?? ''));
            });
        } else {
            body.append('uid', String(uid || '').trim());

            const nextZone = String(zone || '').trim();
            if (nextZone) {
                body.append('zone', nextZone);
            }

            const nextNickname = String(nickname || '').trim();
            if (nextNickname) {
                body.append('nickname', nextNickname);
            }
        }

        const payload = Object.fromEntries(body.entries());

        return { body, payload };
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (submitRequestInFlightRef.current || submitLoading) {
            return;
        }

        const validationMessage = validateBeforeSubmit();
        if (validationMessage) {
            setMessage({ type: 'error', text: validationMessage });
            return;
        }

        setOrderPreviewError('');
        setOrderAgreementChecked(true);
        setShowOrderPreviewModal(true);
    };

    const handleConfirmOrderFromPreview = async () => {
        if (submitRequestInFlightRef.current || submitLoading) {
            return;
        }

        const validationMessage = validateBeforeSubmit();
        if (validationMessage) {
            setOrderPreviewError(validationMessage);
            setMessage({ type: 'error', text: validationMessage });
            return;
        }

        if (!orderAgreementChecked) {
            setOrderPreviewError('Kamu perlu menyetujui syarat & ketentuan terlebih dahulu.');
            return;
        }

        submitRequestInFlightRef.current = true;
        setSubmitLoading(true);
        setMessage(null);
        setOrderPreviewError('');

        try {
            const { body, payload } = buildOrderSubmitPayload();
            const submitIdentity = authUser?.id ? `auth:${authUser.id}` : `guest:${category.slug}`;
            const idempotencyKey = buildOrderIdempotencyKey(submitIdentity, payload);
            body.append('idempotency_key', idempotencyKey);

            const requestController = new AbortController();
            const timeoutId = window.setTimeout(() => {
                requestController.abort();
            }, 45000);

            let response;
            let payloadResponse = null;

            try {
                response = await fetch('/id', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Idempotency-Key': idempotencyKey,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                    signal: requestController.signal,
                });

                payloadResponse = await parseJsonSafe(response);
            } finally {
                window.clearTimeout(timeoutId);
            }

            if (response.ok && payloadResponse?.status === true && payloadResponse?.order_id) {
                persistSavedAccountDraftOnSuccessfulOrder();

                if (typeof window !== 'undefined' && typeof window.pushDataLayerEvent === 'function' && selectedGtmItem) {
                    const checkoutValue = Number(pricePreview?.selected_final_price || pricePreview?.harga || selectedGtmItem.price || 0);
                    const checkoutItem = {
                        ...selectedGtmItem,
                        price: checkoutValue,
                        quantity: selectedPurchaseQuantity,
                    };
                    const paymentType = selectedGtmPaymentMethod?.name || selectedMethod?.name || selectedMethodCode || 'Tidak Diketahui';

                    window.pushDataLayerEvent('begin_checkout', {
                        payment_type: paymentType,
                        ecommerce: {
                            currency: 'IDR',
                            value: checkoutValue,
                            items: [checkoutItem],
                        },
                    }, {
                        dedupeKey: `begin_checkout:${payloadResponse.order_id}`,
                    });
                }

                try {
                    window.sessionStorage.setItem(ORDER_RESET_AFTER_INVOICE_KEY, JSON.stringify({
                        path: window.location.pathname,
                        at: Date.now(),
                    }));
                } catch (error) {
                    // Ignore sessionStorage write failures.
                }

                window.location.assign(`/id/invoices/${payloadResponse.order_id}`);
                return;
            }

            const submitFailure = extractOrderSubmitError(payloadResponse, response);
            const messageType = submitFailure.errorCode === 'ORDER_DUPLICATE_REQUEST' ? 'info' : 'error';

            if (submitFailure.errorCode === 'VOUCHER_STOCK_FAILED') {
                resetStaleVoucherState();
            }

            setOrderPreviewError(submitFailure.message);
            setMessage({ type: messageType, text: submitFailure.message });
        } catch (error) {
            if (error?.name === 'AbortError') {
                const timeoutMessage = 'Permintaan order melebihi batas waktu. Cek invoice dulu, lalu coba lagi jika belum masuk.';
                setOrderPreviewError(timeoutMessage);
                setMessage({ type: 'info', text: timeoutMessage });
            } else {
                setOrderPreviewError('Terjadi kendala saat mengirim order.');
                setMessage({ type: 'error', text: 'Terjadi kendala saat mengirim order.' });
            }
        } finally {
            submitRequestInFlightRef.current = false;
            setSubmitLoading(false);
        }
    };

    const orderSummaryAccount = isComplexOrder
        ? (specialForm.nickname_joki || specialForm.email_joki || 'Belum diisi')
        : (nickname || uid || 'Belum dicek');
    const selectedUnitPrice = getSelectedBaseAmount(
        pricePreview,
        selectedMethodCode,
        selectedProduct?.isFlashSale && selectedProduct?.flashPrice
            ? selectedProduct.flashPrice
            : selectedProduct?.price || 0,
    );
    const selectedPurchaseQuantity = quantityEnabled ? Math.max(1, Number(quantity) || 1) : 1;
    const previewPrice = getSelectedFinalPrice(pricePreview, selectedMethodCode, 0);
    const summaryFeeAmount = getSelectedFeeAmount(pricePreview, selectedMethodCode);
    const selectedMethodPrice = getMethodFinalPrice(pricePreview, selectedMethodCode);
    const summaryProductImage = category.thumbnail || selectedProduct?.productLogo || selectedProduct?.thumbnail || '/assets/logo/favicon.webp';
    const hasBackendPrice = selectedMethodPrice !== null || pricePreview?.selected_final_price !== undefined || pricePreview?.harga !== undefined;
    const displayPreviewPrice = priceLoading && !hasBackendPrice ? 'Menghitung...' : formatCurrency(previewPrice);
    const displaySummaryFee = priceLoading && !hasBackendPrice
        ? 'Menghitung...'
        : summaryFeeAmount === null ? '—' : formatCurrency(summaryFeeAmount);
    const displaySummaryBase = priceLoading && !hasBackendPrice ? 'Menghitung...' : formatCurrency(selectedUnitPrice);
    const displaySummaryTotal = priceLoading && !hasBackendPrice ? 'Menghitung...' : formatCurrency(previewPrice);
    const pointInfo = pricePreview?.point_info || null;
    const pointBalance = Number.isFinite(Number(pointInfo?.balance))
        ? Math.max(0, Math.floor(Number(pointInfo.balance)))
        : Math.max(0, Math.floor(Number(authUser?.pointBalance || 0)));
    const maxRedeemablePoints = Number.isFinite(Number(pointInfo?.max_points))
        ? Math.max(0, Math.floor(Number(pointInfo.max_points)))
        : 0;
    const pointValue = Number.isFinite(Number(pointInfo?.point_value)) ? Number(pointInfo.point_value) : 0;
    const pointDiscountAmount = getSelectedPointDiscount(pricePreview, selectedMethodCode, 0);
    const amountBeforePoint = getSelectedAmountBeforePoint(pricePreview, selectedMethodCode, null);
    const pointControlAvailable = Boolean(authUser);
    const pointControlReady = Boolean(pointInfo);
    const pointControlDisabled = !pointControlReady || maxRedeemablePoints <= 0 || pointBalance <= 0 || priceLoading;
    const pointRedemptionPanel = pointControlAvailable ? (
        <div className={`order-points ${isBangjeff ? 'order-points--bangjeff' : ''}`}>
            <div className="order-points__header">
                <div>
                    <strong>Gunakan Points</strong>
                    <small>Saldo kamu: {pointBalance.toLocaleString('id-ID')} points</small>
                </div>
                <strong className="order-points__selected">{usePoint.toLocaleString('id-ID')} points</strong>
            </div>
            <input
                className="order-points__range"
                type="range"
                min="0"
                max={maxRedeemablePoints}
                step="1"
                value={Math.min(usePoint, maxRedeemablePoints)}
                onChange={(event) => setUsePoint(Math.max(0, Math.min(Number(event.target.value) || 0, maxRedeemablePoints)))}
                disabled={pointControlDisabled}
                aria-label="Jumlah points yang digunakan"
            />
            <div className="order-points__meta">
                <span>{pointControlReady ? `Maksimal ${maxRedeemablePoints.toLocaleString('id-ID')} points` : 'Mengambil batas redeem...'}</span>
                {pointValue > 0 ? <span>1 point = {formatCurrency(pointValue)}</span> : null}
            </div>
            {pointBalance <= 0 && pointControlReady ? <small className="order-points__message">Saldo points belum tersedia.</small> : null}
            {pointDiscountAmount > 0 ? <small className="order-points__discount">Diskon points: -{formatCurrency(pointDiscountAmount)}</small> : null}
        </div>
    ) : null;
    const bangjeffVariantInitialVisibleCount = 6;
    const savedAccountUid = String(savedAccountDraft?.uid || '').trim();
    const showSavedAccountQuickFill = Boolean(
        isBangjeff
        && !isComplexOrder
        && category.requireUserId
        && savedAccountUid,
    );

    useEffect(() => {
        if (!showSavedAccountQuickFill) {
            setSavedAccountDropdownOpen(false);
        }
    }, [showSavedAccountQuickFill]);

    const renderComplexFields = (fields) => (
        <div className={`form-grid ${isBangjeff ? 'form-grid--bangjeff-account' : ''}`}>
            {fields.map((field) => (
                <label key={field.name} className="field">
                    <span>{field.label}</span>
                    {field.type === 'select' ? (
                        <select
                            value={specialForm[field.name] ?? ''}
                            onChange={(event) => handleSpecialFieldChange(field.name, event.target.value)}
                        >
                            <option value="">{field.placeholder || `Pilih ${field.label}`}</option>
                            {field.options?.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    ) : (
                        <input
                            type={field.type || 'text'}
                            value={specialForm[field.name] ?? ''}
                            min={field.min}
                            max={field.max}
                            onChange={(event) => handleSpecialFieldChange(field.name, event.target.value)}
                            placeholder={field.placeholder}
                        />
                    )}
                </label>
            ))}
        </div>
    );

    const renderStandardAccountFields = () => (
        <>
            <div className={`form-grid ${isBangjeff ? 'form-grid--bangjeff-account' : ''}`}>
                {category.requireUserId ? (
                    <label className="field">
                        <span>{category.customInputs.userId.label}</span>
                        <div className="order-account-draft-anchor" ref={savedAccountQuickFillRef}>
                            <input
                                className={isBangjeff ? 'order-promo__input--bangjeff order-account-id-input--bangjeff' : undefined}
                                type={isBangjeff && category.customInputs.userId.type === 'number' ? 'text' : (category.customInputs.userId.type || 'text')}
                                value={uid}
                                inputMode={category.customInputs.userId.type === 'number' ? 'numeric' : undefined}
                                pattern={category.customInputs.userId.type === 'number' ? '[0-9]*' : undefined}
                                onFocus={openSavedAccountQuickFillFromInput}
                                onClick={openSavedAccountQuickFillFromInput}
                                onKeyDown={(event) => {
                                    if (event.key === 'Escape') {
                                        setSavedAccountDropdownOpen(false);
                                    }
                                }}
                                onChange={(event) => {
                                    const nextValue = category.customInputs.userId.type === 'number'
                                        ? event.target.value.replace(/\D+/g, '')
                                        : event.target.value;
                                    setAccountStepInteracted(true);
                                    setUid(nextValue);
                                }}
                                aria-haspopup={showSavedAccountQuickFill ? 'listbox' : undefined}
                                aria-expanded={showSavedAccountQuickFill ? savedAccountDropdownOpen : undefined}
                                placeholder={category.customInputs.userId.placeholder}
                            />

                            {showSavedAccountQuickFill && savedAccountDropdownOpen ? (
                                <div className="order-account-draft-dropdown" role="listbox" aria-label="Quick fill akun terakhir">
                                    <button
                                        type="button"
                                        className="order-account-draft-dropdown__item"
                                        onClick={handleApplySavedAccountDraft}
                                    >
                                        <strong>{savedAccountDraft?.nickname ? `@${savedAccountDraft.nickname}` : 'Akun Tersimpan'}</strong>
                                        <span>
                                            ID: {savedAccountUid}
                                            {savedAccountDraft?.zone ? ` (${savedAccountDraft.zone})` : ''}
                                        </span>
                                    </button>
                                </div>
                            ) : null}
                        </div>
                    </label>
                ) : null}

                {category.customInputs.zone ? (
                    <label className="field">
                        <span>{category.customInputs.zone.label}</span>
                        {category.customInputs.zone.isSelect ? (
                            <select
                                value={zone}
                                onChange={(event) => {
                                    setAccountStepInteracted(true);
                                    setZone(event.target.value);
                                }}
                            >
                                <option value="">{category.customInputs.zone.placeholder}</option>
                                {category.customInputs.zone.options.map((option) => (
                                    <option key={option.value} value={option.value}>{option.label}</option>
                                ))}
                            </select>
                        ) : (
                            <input
                                type={category.customInputs.zone.type || 'text'}
                                value={zone}
                                onChange={(event) => {
                                    setAccountStepInteracted(true);
                                    setZone(event.target.value);
                                }}
                                placeholder={category.customInputs.zone.placeholder}
                            />
                        )}
                    </label>
                ) : null}
            </div>

            {category.requiresGameValidation ? (
                <div className={`inline-actions ${isBangjeff ? 'inline-actions--bangjeff-account' : ''}`}>
                    {isBangjeff ? (
                        uid ? (
                            accountLookup?.type === 'success' ? (
                                <div className="account-pill account-pill--bangjeff-success">
                                    <span className="account-pill__line">Your account is <strong>{accountLookup.username} from <strong>{accountLookup.location} 🇮🇩</strong></strong></span>
                                </div>
                            ) : accountLookup?.type === 'error' ? (
                                <div className="account-pill account-pill--bangjeff-error">
                                    <span className="account-pill__line">{accountLookup.text}</span>
                                </div>
                            ) : checkLoading ? (
                                <div className="account-pill account-pill--bangjeff-loading">
                                    <span className="account-pill__line">Checking your account...</span>
                                </div>
                            ) : null
                        ) : null
                    ) : (
                        <>
                            <button type="button" className="public-button public-button--ghost" onClick={handleCheckAccount} disabled={checkLoading}>
                                {checkLoading ? 'Memeriksa...' : 'Cek Username Game'}
                            </button>
                            {nickname ? <div className="account-pill">Nickname: {nickname}</div> : null}
                        </>
                    )}
                </div>
            ) : null}
        </>
    );

    const legacyLayout = (
        <div className="public-shell public-shell--order">
            <section className="order-hero">
                <img src={category.banner || category.thumbnail} alt={category.name} className="order-hero__bg" />
                <div className="order-hero__overlay" />
                <div className="order-hero__content">
                    <img src={category.thumbnail} alt={category.name} className="order-hero__thumb" />
                    <div>
                        <span className="hero__eyebrow">{isComplexOrder ? 'Complex Order Flow' : 'Order Page Inertia'}</span>
                        <h1>{category.name}</h1>
                        <p>{category.subtitle}</p>
                        <div className="order-hero__chips">
                            <span>Verified</span>
                            <span>Instant Support</span>
                            <span>{isComplexOrder ? 'Manual Handling' : 'Auto Validation Ready'}</span>
                        </div>
                    </div>
                </div>
            </section>

            <div className="order-grid">
                <aside className="order-sidebar">
                    <SectionShell title={isComplexOrder ? 'Deskripsi Layanan' : 'Deskripsi Game'}>
                        <div className="rich-copy" dangerouslySetInnerHTML={{ __html: category.description || '<p>Deskripsi kategori belum tersedia.</p>' }} />
                    </SectionShell>

                    {category.specialNotes?.length ? (
                        <SectionShell title="Catatan Penting">
                            <ul className="order-notes">
                                {category.specialNotes.map((note) => (
                                    <li key={note}>{note}</li>
                                ))}
                            </ul>
                        </SectionShell>
                    ) : null}

                    <SectionShell title="Ulasan Terbaru">
                        <div className="rating-list">
                            {ratings.length ? ratings.map((rating) => (
                                <article key={rating.id} className="rating-item">
                                    <div className="rating-item__head">
                                        <strong>{rating.author}</strong>
                                        <span>{'★'.repeat(rating.stars)}</span>
                                    </div>
                                    <p>{rating.comment}</p>
                                    <small>{rating.service}</small>
                                </article>
                            )) : <div className="empty-card">Belum ada ulasan untuk kategori ini.</div>}
                        </div>
                    </SectionShell>
                </aside>

                <div className="order-main">
                    <form onSubmit={handleSubmit} className="order-stack">
                        <SectionShell
                            title="1. Data Akun"
                            subtitle={category.fieldDescription || (isComplexOrder ? 'Isi data yang dibutuhkan tim operasional sebelum order diproses.' : 'Masukkan data akun sesuai petunjuk kategori.')}
                        >
                            {isComplexOrder ? renderComplexFields(category.specialFields) : renderStandardAccountFields()}
                        </SectionShell>

                        {packages.length ? (
                            <SectionShell title="2. Paket Produk" subtitle="Pilih grup paket terlebih dahulu, lalu pilih item di dalamnya.">
                                <div className="package-tabs">
                                    {packages.map((item, index) => (
                                        <button
                                            key={item.name}
                                            type="button"
                                            className={selectedPackage === index ? 'is-active' : ''}
                                            onClick={() => {
                                                preventAutoSelectRef.current = false;
                                                setNominalStepInteracted(true);
                                                setSelectedPackage(index);
                                                setSelectedProductId(item.items[0]?.id ?? null);
                                            }}
                                        >
                                            {item.name}
                                        </button>
                                    ))}
                                </div>
                            </SectionShell>
                        ) : null}

                        <SectionShell title="3. Pilih Layanan" subtitle="Produk dan harga tetap diambil dari backend Laravel.">
                            <div className="product-grid">
                                {variantItems.map((item) => (
                                    <ProductCard
                                        key={item.id}
                                        item={item}
                                        onClick={() => {
                                            preventAutoSelectRef.current = false;
                                            setNominalStepInteracted(true);
                                            setSelectedProductId(item.id);
                                        }}
                                        isActive={selectedProductId === item.id}
                                    />
                                ))}
                            </div>
                        </SectionShell>

                        <SectionShell title="4. Metode Pembayaran" subtitle="Pilih metode yang ingin ditampilkan ke customer di halaman invoice.">
                            <div className="method-groups">
                                {methodEntries.map(([group, methods]) => (
                                    <div key={group} className="method-group">
                                        <h3>{group}</h3>
                                        <div className="method-grid">
                                            {methods.map((method) => (
                                                <PaymentMethodCard
                                                    key={method.id}
                                                    method={method}
                                                    selected={selectedMethodCode === method.code}
                                                    displayPrice={(() => {
                                                        const methodPrice = getMethodFinalPrice(pricePreview, method.code, null);
                                                        return methodPrice === null ? null : formatCurrency(methodPrice);
                                                    })()}
                                                    onSelect={(picked) => {
                                                        setPaymentStepInteracted(true);
                                                        setSelectedMethodCode(picked.code);
                                                    }}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </SectionShell>

                        <SectionShell title="5. Kontak & Konfirmasi" subtitle="Ringkasan order disiapkan di frontend, sedangkan create order tetap diproses backend existing.">
                            <div className="form-grid">
                                <label className="field">
                                    <span>Kode Voucher</span>
                                    <input type="text" value={voucher} onChange={(event) => setVoucher(event.target.value)} placeholder="Opsional" />
                                </label>

                                <label className="field">
                                    <span>Email</span>
                                    <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} placeholder="example@gmail.com" />
                                </label>

                                <label className="field">
                                    <span>Nomor WhatsApp</span>
                                    <input type="tel" value={phone} onChange={(event) => setPhone(event.target.value)} placeholder="08xxxxxxxxxx" />
                                </label>
                            </div>

                            {pointRedemptionPanel}

                            <div className="order-summary">
                                <div>
                                    <small>Layanan</small>
                                    <strong>{selectedProduct?.name || 'Belum dipilih'}</strong>
                                </div>
                                <div>
                                    <small>Account</small>
                                    <strong>{orderSummaryAccount}</strong>
                                </div>
                                <div>
                                    <small>Metode</small>
                                    <strong>{selectedMethod?.name || 'Belum dipilih'}</strong>
                                </div>
                                <div>
                                    <small>Estimasi Harga</small>
                                    <strong>{displayPreviewPrice}</strong>
                                </div>
                                {amountBeforePoint !== null ? (
                                    <div>
                                        <small>Sebelum Points</small>
                                        <strong>{formatCurrency(amountBeforePoint)}</strong>
                                    </div>
                                ) : null}
                                {pointDiscountAmount > 0 ? (
                                    <div>
                                        <small>Diskon Points</small>
                                        <strong className="order-summary__discount">-{formatCurrency(pointDiscountAmount)}</strong>
                                    </div>
                                ) : null}
                                {quantityEnabled ? (
                                    <div>
                                        <small>Qty</small>
                                        <strong>{quantity}</strong>
                                    </div>
                                ) : null}
                            </div>

                            {message ? <div className={`feedback feedback--${message.type}`}>{message.text}</div> : null}

                            <button
                                type="submit"
                                className="public-button public-button--wide"
                                disabled={submitLoading || !isOrderReady}
                                title={!isOrderReady ? orderValidationMessage : undefined}
                            >
                                {submitLoading ? 'Memproses Order...' : 'Buat Order'}
                            </button>
                        </SectionShell>
                    </form>
                </div>
            </div>
        </div>
    );

    const bangjeffTransactionPanels = (
        <>
            <BangjeffOrderPanel
                step="1"
                title="Masukkan Data Akun"
                subtitle={category.fieldDescription || (isComplexOrder ? 'Isi data akun dan instruksi order sebelum melanjutkan ke nominal.' : 'Masukkan data akun sesuai petunjuk kategori sebelum memilih nominal.')}
                panelClassName="order-panel--bangjeff-account"
                panelRef={accountPanelRef}
                sectionId="order-step-account"
            >
                {isComplexOrder ? renderComplexFields(specialFieldsWithoutQty) : renderStandardAccountFields()}
            </BangjeffOrderPanel>

            <BangjeffOrderPanel step="2" title="Pilih Nominal" panelRef={nominalPanelRef} sectionId="order-step-nominal">
                <div className="variant-groups variant-groups--bangjeff">
                    {variantGroups.some((group) => (group.items ?? []).length) ? variantGroups.map((group, index) => {
                        const groupItems = group.items ?? [];
                        const groupKey = `${group.name || 'group'}-${index}`;

                        if (!groupItems.length) {
                            return null;
                        }

                        const isGroupExpanded = expandedVariantGroups[groupKey] === true;
                        const visibleGroupItems = isGroupExpanded
                            ? groupItems
                            : groupItems.slice(0, bangjeffVariantInitialVisibleCount);
                        const hiddenCount = Math.max(0, groupItems.length - visibleGroupItems.length);

                        return (
                            <section className="variant-group variant-group--bangjeff" key={group.name || `group-${index}`}>
                                {group.name ? (
                                    <header className="variant-group__heading variant-group__heading--bangjeff">
                                        <h3>{group.name}</h3>
                                    </header>
                                ) : null}

                                <div className="variant-grid variant-grid--bangjeff">
                                    {visibleGroupItems.map((item) => (
                                        <BangjeffVariantCard
                                            key={item.id}
                                            item={item}
                                            selected={selectedProductId === item.id}
                                            onSelect={() => {
                                                preventAutoSelectRef.current = false;
                                                setNominalStepInteracted(true);
                                                setSelectedProductId(item.id);
                                            }}
                                        />
                                    ))}
                                </div>

                                {groupItems.length > bangjeffVariantInitialVisibleCount ? (
                                    <div className="variant-group__actions variant-group__actions--bangjeff">
                                        <button
                                            type="button"
                                            className="variant-group__more variant-group__more--bangjeff"
                                            onClick={() => {
                                                setExpandedVariantGroups((current) => ({
                                                    ...current,
                                                    [groupKey]: !isGroupExpanded,
                                                }));
                                            }}
                                        >
                                            {isGroupExpanded ? 'Tutup Lainnya' : `Lihat ${hiddenCount} Lainnya`}
                                        </button>
                                    </div>
                                ) : null}
                            </section>
                        );
                    }) : <div className="empty-card">Belum ada nominal yang tersedia untuk kategori ini.</div>}
                </div>
            </BangjeffOrderPanel>

            {quantityEnabled ? (
                <BangjeffOrderPanel step="3" title="Masukkan Jumlah Pembelian" panelRef={quantityPanelRef} sectionId="order-step-qty">
                    <div className="order-qty order-qty--bangjeff">
                        <div className="order-qty__row order-qty__row--bangjeff">
                            <label className="field field--compact order-qty__field order-qty__field--bangjeff">
                                <span>Jumlah</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="30"
                                    value={specialForm.qty ?? 1}
                                    onChange={(event) => handleSpecialFieldChange('qty', event.target.value.replace(/\D+/g, ''))}
                                    placeholder="1"
                                />
                            </label>

                            <div className="order-qty__controls order-qty__controls--bangjeff">
                                <button type="button" onClick={() => adjustQuantity(1)} aria-label="Tambah jumlah">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M12 5v14" />
                                        <path d="M5 12h14" />
                                    </svg>
                                </button>
                                <button type="button" onClick={() => adjustQuantity(-1)} disabled={quantity <= 1} aria-label="Kurangi jumlah">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M5 12h14" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </BangjeffOrderPanel>
            ) : null}

            <BangjeffOrderPanel step={quantityEnabled ? '4' : '3'} title="Pilih Pembayaran" panelRef={paymentPanelRef} sectionId="order-step-payment">
                <div
                    className="payment-groups payment-groups--bangjeff"
                    data-auth-user-id={authUser?.id ?? ''}
                    data-auth-role={authUser?.role ?? 'guest'}
                    data-balance-payment-allowed={canUseBangjeffBalancePayment ? 'true' : 'false'}
                    data-balance-payment-login-required={requiresLoginForBalancePayment ? 'true' : 'false'}
                >
                    {featuredPaymentMethods.length ? (
                        <div className="payment-featured payment-featured--bangjeff">
                            {featuredPaymentMethods.map((method) => (
                                <BangjeffFeaturedPaymentCard
                                    key={method.id}
                                    method={method}
                                    selected={selectedMethodCode === method.code}
                                    onSelect={handleBangjeffPaymentSelect}
                                    displayPrice={getMethodFinalPrice(pricePreview, method.code, method.code === selectedMethodCode ? previewPrice : null)}
                                    requiresLogin={requiresLoginForBalancePayment && isBangjeffBalanceMethod(method)}
                                    onLoginRequired={openBangjeffLoginRequiredModal}
                                />
                            ))}
                        </div>
                    ) : null}

                    {groupedMethodEntries.map(([group, methods]) => {
                        const isOpen = openPaymentGroup === group;
                        const previewLogos = getPaymentPreviewLogos(methods);

                        return (
                            <section key={group} className={`payment-group payment-group--bangjeff ${isOpen ? 'is-open' : ''}`}>
                                <button
                                    type="button"
                                    className="payment-group__header payment-group__header--bangjeff payment-group__toggle--bangjeff"
                                    onClick={() => setOpenPaymentGroup((current) => current === group ? null : group)}
                                    aria-expanded={isOpen}
                                >
                                    <h3>{formatMethodGroup(group)}</h3>
                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="m6 8 4 4 4-4" />
                                    </svg>
                                </button>
                                {previewLogos.length ? (
                                    <div className="payment-group__preview payment-group__preview--bangjeff" aria-hidden={isOpen}>
                                        <div className="payment-group__preview-logos">
                                            {previewLogos.map((logo) => (
                                                <span key={logo.key} className="payment-group__preview-logo">
                                                    <img src={logo.src} alt={logo.alt} loading="lazy" decoding="async" />
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                ) : null}
                                <div className="payment-group__collapse payment-group__collapse--bangjeff" aria-hidden={!isOpen} inert={isOpen ? undefined : ''}>
                                    <div className="payment-group__collapse-inner payment-group__collapse-inner--bangjeff">
                                        <div className="payment-group__body payment-group__body--bangjeff">
                                            <div className="payment-grid payment-grid--bangjeff">
                                                {methods.map((method) => (
                                                    <BangjeffPaymentCard
                                                        key={method.id}
                                                        method={method}
                                                        selected={selectedMethodCode === method.code}
                                                        onSelect={handleBangjeffPaymentSelect}
                                                        requiresLogin={requiresLoginForBalancePayment && isBangjeffBalanceMethod(method)}
                                                        onLoginRequired={openBangjeffLoginRequiredModal}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        );
                    })}
                </div>
            </BangjeffOrderPanel>

            <BangjeffOrderPanel step={quantityEnabled ? '5' : '4'} title="Detail Kontak" panelRef={contactPanelRef} sectionId="order-step-contact">
                <div className="order-contact-grid order-contact-grid--bangjeff">
                    <label className="field field--bangjeff-contact">
                        <span>Email</span>
                        <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} placeholder="example@gmail.com" />
                    </label>
                    <label className="field field--bangjeff-contact">
                        <span>No. WhatsApp</span>
                        <div className="order-contact-phone order-contact-phone--bangjeff">
                            <span className="order-contact-phone__prefix order-contact-phone__prefix--bangjeff" aria-hidden="true">
                                <span className="order-contact-phone__flag">
                                    <svg viewBox="0 0 513 342" role="img" aria-label="Indonesia">
                                        <path fill="#FFFFFF" d="M0 0h513v342H0z" />
                                        <path fill="#E70011" d="M0 0h513v171H0z" />
                                    </svg>
                                </span>
                                <span className="order-contact-phone__code">+62</span>
                            </span>
                            <input
                                type="tel"
                                value={phone}
                                onChange={(event) => setPhone(event.target.value)}
                                placeholder="8XXXXXXXXXX"
                                inputMode="numeric"
                                autoComplete="tel-national"
                            />
                        </div>
                    </label>
                    <p className="order-contact-help order-contact-help--bangjeff">**Nomor ini akan dihubungi jika terjadi masalah</p>
                    <p className="order-contact-note order-contact-note--bangjeff">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 16v-4" />
                            <path d="M12 8h.01" />
                        </svg>
                        <span>Isi minimal salah satu: email atau nomor WhatsApp.</span>
                    </p>
                </div>
            </BangjeffOrderPanel>

            {pointRedemptionPanel}

            <BangjeffOrderPanel step={quantityEnabled ? '6' : '5'} title="Kode Promo">
                <div className="order-promo order-promo--bangjeff">
                    <div className="order-promo__row order-promo__row--bangjeff">
                        <div className="order-promo__field order-promo__field--bangjeff">
                            <input className="order-promo__input order-promo__input--bangjeff" type="text" value={voucher} onChange={(event) => setVoucher(event.target.value)} placeholder="Ketik Kode Promo Kamu" />
                        </div>
                        <button type="button" className="order-promo__apply order-promo__apply--bangjeff bangjeff-gradient-button" onClick={handleApplyVoucher} disabled={voucherActionLoading !== null}>
                            {voucherActionLoading === 'apply' ? 'Memeriksa...' : 'Gunakan'}
                        </button>
                    </div>
                    <button type="button" className="order-promo__available order-promo__available--bangjeff bangjeff-gradient-button" onClick={handleApplyAvailableVoucher} disabled={voucherActionLoading !== null}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M2 9a3 3 0 1 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 1 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
                            <path d="M9 9h.01" />
                            <path d="m15 9-6 6" />
                            <path d="M15 15h.01" />
                        </svg>
                        <span>{voucherActionLoading === 'available' ? 'Mencari Promo...' : 'Pakai Promo Yang Tersedia'}</span>
                    </button>
                </div>
            </BangjeffOrderPanel>
        </>
    );

    const bangjeffSidebar = (
        <aside className="order-sidebar-bangjeff">
            <div className="order-sidebar-bangjeff__sticky">
                <section className="order-mini-card order-mini-card--bangjeff order-mini-card--rating">
                    <div className="order-mini-card__eyebrow">Ulasan dan rating</div>
                    <div className="order-rating__row">
                        <div className="order-rating__score">{ratingSummary.average.toFixed(2)}</div>
                        <div className="order-rating__stars" aria-label={`Rating ${ratingSummary.average.toFixed(1)} dari 5`}>
                            {Array.from({ length: 5 }).map((_, index) => (
                                <svg key={index} viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" />
                                </svg>
                            ))}
                        </div>
                    </div>
                    <p>Berdasarkan total {ratingSummary.total} rating</p>
                </section>

                <button type="button" className="order-help-card order-help-card--bangjeff" onClick={openBangjeffSupportModal}>
                    <svg viewBox="0 0 25 24" fill="none" aria-hidden="true">
                        <path d="M5.67151 19.7552C6.19123 20.657 7.34509 20.9653 8.24587 20.4446C9.14569 19.9239 9.45403 18.773 8.93431 17.8722L7.35188 15.1301C6.83216 14.2293 5.68121 13.92 4.77946 14.4397L4.55838 14.5667C3.99309 14.8935 3.70802 15.5645 3.90097 16.188C4.24422 17.2991 4.83278 18.4947 5.67151 19.7552Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                        <path d="M19.3415 19.7552C18.8217 20.657 17.6679 20.9653 16.7671 20.4446C15.8673 19.9239 15.559 18.773 16.0787 17.8722L17.6611 15.1301C18.1808 14.2293 19.3318 13.92 20.2335 14.4397L20.4546 14.5667C21.0199 14.8935 21.305 15.5645 21.112 16.188C20.7688 17.2991 20.1802 18.4947 19.3415 19.7552Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                        <path d="M6.10793 7.83938C5.62506 7.3565 5.595 6.56917 6.0643 6.07272C6.1186 6.01551 6.17387 5.9583 6.23107 5.90109C9.69555 2.43661 15.3116 2.43661 18.7761 5.90109C18.8323 5.95733 18.8866 6.01357 18.9409 6.07078C19.4112 6.5682 19.3812 7.35747 18.8973 7.84132C18.3844 8.35425 17.5612 8.3271 17.0589 7.8035C17.0288 7.77247 16.9988 7.74145 16.9678 7.71042C14.502 5.23496 10.5062 5.23496 8.0307 7.71042C8.00064 7.74048 7.97156 7.77053 7.9415 7.80156C7.4402 8.32322 6.61989 8.35134 6.10793 7.83938Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                    <div>
                        <strong>Butuh Bantuan?</strong>
                        <span>Kamu bisa hubungi admin disini.</span>
                    </div>
                </button>

                <section className="order-mini-card order-mini-card--bangjeff order-mini-card--summary">
                    {selectedProduct ? (
                        <>
                            <div className="order-mini-card__product">
                                <img src={summaryProductImage} alt={category.name} />
                                <div>
                                    <strong>{category.name}</strong>
                                    <span>{selectedProduct.name}</span>
                                </div>
                            </div>
                            <div className="order-summary order-summary--bangjeff">
                                <div className="order-summary__row">
                                    <small className="order-summary__label">Harga</small>
                                    <strong className="order-summary__value">{displaySummaryBase}</strong>
                                </div>
                                <div className="order-summary__row">
                                    <small className="order-summary__label">Jumlah Pembelian</small>
                                    <strong className="order-summary__value">{selectedPurchaseQuantity}</strong>
                                </div>
                                <div className="order-summary__row">
                                    <small className="order-summary__label">Biaya</small>
                                    <strong className="order-summary__value">{displaySummaryFee}</strong>
                                </div>
                                {amountBeforePoint !== null ? (
                                    <div className="order-summary__row">
                                        <small className="order-summary__label">Sebelum Points</small>
                                        <strong className="order-summary__value">{formatCurrency(amountBeforePoint)}</strong>
                                    </div>
                                ) : null}
                                {pointDiscountAmount > 0 ? (
                                    <div className="order-summary__row order-summary__row--discount">
                                        <small className="order-summary__label">Diskon Points</small>
                                        <strong className="order-summary__value">-{formatCurrency(pointDiscountAmount)}</strong>
                                    </div>
                                ) : null}
                                <div className="order-summary__divider" aria-hidden="true" />
                                <div className="order-summary__row order-summary__row--total">
                                    <small className="order-summary__label">Total Pembayaran</small>
                                    <strong className="order-summary__value order-summary__value--total">{displaySummaryTotal}</strong>
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="order-sidebar-empty">Belum ada item produk yang dipilih.</div>
                    )}

                    {message ? <div className={`feedback feedback--${message.type}`}>{message.text}</div> : null}

                    <button
                        type="submit"
                        className="public-button public-button--wide public-button--bangjeff-order"
                        disabled={submitLoading || !isOrderReady}
                        title={!isOrderReady ? orderValidationMessage : undefined}
                    >
                        <svg viewBox="0 0 25 24" fill="none" aria-hidden="true">
                            <path d="M16.5209 6.87109H8.47891C5.67599 6.87109 3.91895 8.8558 3.91895 11.6636V16.206C3.91895 19.0148 5.66724 20.9985 8.47891 20.9985H16.5199C19.3316 20.9985 21.0818 19.0148 21.0818 16.206V11.6636C21.0818 8.8558 19.3316 6.87109 16.5209 6.87109Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            <path d="M9.38477 11.1445C9.45871 11.8684 9.79338 12.5173 10.275 13.0096C10.8509 13.5748 11.639 13.928 12.5019 13.928C14.116 13.928 15.443 12.7119 15.6191 11.1445" opacity="0.4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            <path d="M16.3702 6.87115C16.3702 4.7337 14.6375 3 12.4991 3C10.3616 3 8.62891 4.7337 8.62891 6.87115" opacity="0.4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                        {submitLoading ? 'Memproses Order...' : 'Pesan Sekarang!'}
                    </button>
                </section>
            </div>
        </aside>
    );

    const bangjeffDetailsPanels = (
        <div className="order-bottom order-bottom--bangjeff">
            <BangjeffOrderPanel title={`Deskripsi ${category.name}`}>
                <div className="rich-copy rich-copy--bangjeff" dangerouslySetInnerHTML={{ __html: category.description || '<p>Deskripsi kategori belum tersedia.</p>' }} />
            </BangjeffOrderPanel>

            <BangjeffOrderPanel title="Kamu Punya Pertanyaan?">
                <div className="order-faq order-faq--bangjeff">
                    {faqItems.map((item, index) => (
                        <FaqItem
                            key={`${item.question}-${index}`}
                            item={item}
                            open={activeFaqIndex === index}
                            onToggle={() => setActiveFaqIndex(activeFaqIndex === index ? -1 : index)}
                        />
                    ))}
                </div>
            </BangjeffOrderPanel>
        </div>
    );

    const bangjeffLayout = (
        <div
            className="order-page order-page--bangjeff"
            data-auth-user-id={authUser?.id ?? ''}
            data-auth-role={authUser?.role ?? 'guest'}
            data-balance-payment-allowed={canUseBangjeffBalancePayment ? 'true' : 'false'}
            data-balance-payment-login-required={requiresLoginForBalancePayment ? 'true' : 'false'}
        >
            <section className="order-hero order-hero--bangjeff">
                <div className="order-hero__banner order-hero__banner--bangjeff">
                    <img src={category.banner || category.thumbnail} alt={category.name} className="order-hero__bg" />
                    <div className="order-hero__overlay order-hero__overlay--bangjeff" />
                </div>
                <div className="order-hero__titlebar order-hero__titlebar--bangjeff">
                    <div className="order-hero__titlebar-inner order-hero__titlebar-inner--bangjeff">
                        <div className="order-hero__head order-hero__head--bangjeff">
                            <div className="order-hero__thumb-wrap order-hero__thumb-wrap--bangjeff">
                                <div className="order-hero__thumb-shell order-hero__thumb-shell--bangjeff">
                                    <img src={category.thumbnail || category.banner} alt={category.name} className="order-hero__thumb order-hero__thumb--bangjeff" />
                                </div>
                            </div>
                            <div className="order-hero__copy order-hero__copy--bangjeff">
                                <h1>{category.name}</h1>
                                <p>{category.subtitle || heroDescription}</p>
                                <div className="order-hero__meta order-hero__meta--bangjeff order-hero__meta--desktop">
                                    {heroMetaItems.map((item) => (
                                        <span key={`${item.key}-desktop`}>
                                            <i
                                                className={`order-hero__meta-dot order-hero__meta-dot--${item.key} ${item.key === 'secure' ? 'order-hero__meta-dot--secure-adjust' : ''}`}
                                                aria-hidden="true"
                                            >
                                                {item.icon}
                                            </i>
                                            {item.label}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                        <div className="order-hero__meta order-hero__meta--bangjeff order-hero__meta--mobile">
                            {heroMetaItems.map((item) => (
                                <span key={`${item.key}-mobile`}>
                                    <i
                                        className={`order-hero__meta-dot order-hero__meta-dot--${item.key} ${item.key === 'secure' ? 'order-hero__meta-dot--secure-adjust' : ''}`}
                                        aria-hidden="true"
                                    >
                                        {item.icon}
                                    </i>
                                    {item.label}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <div className="public-shell public-shell--order order-page__body order-page__body--bangjeff">
                <div className="order-mobile-tabs order-mobile-tabs--bangjeff" role="tablist" aria-orientation="horizontal">
                    <button
                        type="button"
                        className={mobileOrderTab === 'transaction' ? 'is-active' : ''}
                        onClick={() => setMobileOrderTab('transaction')}
                    >
                        Transaksi
                    </button>
                    <button
                        type="button"
                        className={mobileOrderTab === 'details' ? 'is-active' : ''}
                        onClick={() => setMobileOrderTab('details')}
                    >
                        Keterangan
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="order-layout order-layout--bangjeff">
                    <div className={`order-layout__main order-layout__main--bangjeff ${mobileOrderTab !== 'transaction' ? 'is-hidden-mobile' : ''}`}>
                        <div className="order-flow order-flow--bangjeff">
                            {bangjeffTransactionPanels}
                        </div>
                    </div>

                    <div className={`order-layout__sidebar order-layout__sidebar--bangjeff ${mobileOrderTab !== 'transaction' ? 'is-hidden-mobile' : ''}`}>
                        {bangjeffSidebar}
                    </div>

                    <div className={`order-layout__details-mobile order-layout__details-mobile--bangjeff ${mobileOrderTab !== 'details' ? 'is-hidden-mobile' : ''}`}>
                        {bangjeffDetailsPanels}
                    </div>

                    <div className={`order-mobile-checkout order-mobile-checkout--bangjeff ${mobileOrderTab !== 'transaction' ? 'is-hidden-mobile' : ''}`}>
                        <div className="order-mobile-checkout__summary">
                            {selectedProduct ? (
                                <div className="order-mobile-checkout__summary-content">
                                    <button
                                        type="button"
                                        className="order-mobile-checkout__toggle"
                                        aria-expanded={mobileCheckoutExpanded}
                                        onClick={() => setMobileCheckoutExpanded((state) => !state)}
                                    >
                                        <div className="order-mobile-checkout__product">
                                            <img src={summaryProductImage} alt={category.name} />
                                            <div>
                                                <strong>{category.name}</strong>
                                                <span>{selectedProduct.name}</span>
                                            </div>
                                        </div>
                                        <svg className={`order-mobile-checkout__chevron ${mobileCheckoutExpanded ? 'is-open' : ''}`} viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m6 9 6 6 6-6" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                                        </svg>
                                    </button>

                                    <div className={`order-mobile-checkout__details ${mobileCheckoutExpanded ? 'is-open' : ''}`}>
                                        <div className="order-mobile-checkout__row">
                                            <small>Metode Pembayaran</small>
                                            <strong>{selectedMethod?.name || 'Belum dipilih'}</strong>
                                        </div>
                                        <div className="order-mobile-checkout__row">
                                            <small>Harga</small>
                                            <strong>{displaySummaryBase}</strong>
                                        </div>
                                        <div className="order-mobile-checkout__row">
                                            <small>Jumlah Pembelian</small>
                                            <strong>{selectedPurchaseQuantity}</strong>
                                        </div>
                                        <div className="order-mobile-checkout__row">
                                            <small>Biaya</small>
                                            <strong>{displaySummaryFee}</strong>
                                        </div>
                                        {amountBeforePoint !== null ? (
                                            <div className="order-mobile-checkout__row">
                                                <small>Sebelum Points</small>
                                                <strong>{formatCurrency(amountBeforePoint)}</strong>
                                            </div>
                                        ) : null}
                                        {pointDiscountAmount > 0 ? (
                                            <div className="order-mobile-checkout__row order-mobile-checkout__row--discount">
                                                <small>Diskon Points</small>
                                                <strong>-{formatCurrency(pointDiscountAmount)}</strong>
                                            </div>
                                        ) : null}
                                        <div className="order-mobile-checkout__row order-mobile-checkout__row--total">
                                            <small>Total Pembayaran</small>
                                            <strong>{displayPreviewPrice}</strong>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="order-mobile-checkout__empty">Belum ada item produk yang dipilih.</div>
                            )}
                        </div>
                        <button
                            type="submit"
                            className="public-button public-button--wide public-button--bangjeff-order public-button--bangjeff-order-mobile"
                            disabled={submitLoading || !isOrderReady}
                            title={!isOrderReady ? orderValidationMessage : undefined}
                        >
                            <svg viewBox="0 0 25 24" fill="none" aria-hidden="true">
                                <path d="M16.5209 6.87109H8.47891C5.67599 6.87109 3.91895 8.8558 3.91895 11.6636V16.206C3.91895 19.0148 5.66724 20.9985 8.47891 20.9985H16.5199C19.3316 20.9985 21.0818 19.0148 21.0818 16.206V11.6636C21.0818 8.8558 19.3316 6.87109 16.5209 6.87109Z" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                <path d="M9.38477 11.1445C9.45871 11.8684 9.79338 12.5173 10.275 13.0096C10.8509 13.5748 11.639 13.928 12.5019 13.928C14.116 13.928 15.443 12.7119 15.6191 11.1445" opacity="0.4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                <path d="M16.3702 6.87115C16.3702 4.7337 14.6375 3 12.4991 3C10.3616 3 8.62891 4.7337 8.62891 6.87115" opacity="0.4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                            {submitLoading ? 'Memproses Order...' : 'Pesan Sekarang!'}
                        </button>
                    </div>
                </form>

                <div className="order-bottom-desktop order-bottom-desktop--bangjeff">
                    {bangjeffDetailsPanels}
                </div>
            </div>
        </div>
    );

    return (
        <PublicLayout
            meta={meta}
            mainClassName={isBangjeff ? 'public-main--hero-bleed public-main--order-bangjeff' : ''}
        >
            <BangjeffLoginRequiredModal open={showLoginRequiredModal} onClose={closeBangjeffLoginRequiredModal} />
            <BangjeffSupportModal
                open={showSupportModal}
                onClose={closeBangjeffSupportModal}
                title={supportModalTitle}
                items={supportItems}
            />
            <BangjeffAvailablePromoModal
                open={showAvailablePromoModal}
                onClose={closeAvailablePromoModal}
                loading={voucherActionLoading === 'available'}
                promos={availablePromos}
                applyingCode={typeof voucherActionLoading === 'string' && voucherActionLoading !== 'available' && voucherActionLoading !== 'apply' ? voucherActionLoading : null}
                onApply={handleSelectAvailablePromo}
            />
            <BangjeffOrderPreviewModal
                open={showOrderPreviewModal}
                onClose={() => {
                    if (submitLoading) return;
                    setShowOrderPreviewModal(false);
                    setOrderPreviewError('');
                }}
                onConfirm={handleConfirmOrderFromPreview}
                submitting={submitLoading}
                agreementChecked={orderAgreementChecked}
                onAgreementChange={setOrderAgreementChecked}
                errorMessage={orderPreviewError}
                canConfirm={isOrderReady && orderAgreementChecked}
                rows={[
                    { label: 'Username', value: orderSummaryAccount },
                    { label: 'ID', value: uid ? `${uid}${zone ? ` (${zone})` : ''}` : '-' },
                    { label: 'Item', value: selectedProduct?.name || '-' },
                    { label: 'Product', value: category.name || '-' },
                    { label: 'Payment', value: selectedMethod?.name || '-' },
                    { label: 'Total', value: displaySummaryTotal },
                ]}
            />
            {isBangjeff ? bangjeffLayout : legacyLayout}
        </PublicLayout>
    );
}

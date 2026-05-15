import React, { useMemo, useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
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

function StateIcon({ type }) {
    if (type === 'pending') {
        return (
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.7" />
                <path d="M12 7v5l3 2" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
        );
    }

    if (type === 'rejected') {
        return (
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.7" />
                <path d="m15 9-6 6m0-6 6 6" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            </svg>
        );
    }

    return (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M8 11.5 4.5 8A2.5 2.5 0 1 1 8 4.5L12 8.5l4-4A2.5 2.5 0 1 1 19.5 8L16 11.5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M12 8.5V19.5M8.5 15.5h7" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function normalizePath(url) {
    const rawUrl = typeof url === 'string' ? url : '/';
    const [pathOnly] = rawUrl.split('?');
    if (!pathOnly) {
        return '/';
    }

    return pathOnly.startsWith('/') ? pathOnly : `/${pathOnly}`;
}

export default function Affiliate({ meta, affiliate }) {
    const page = usePage();
    const currentPath = normalizePath(page?.url);
    const status = (affiliate?.status || 'inactive').toLowerCase();
    const links = affiliate?.links || {};
    const application = affiliate?.application || {};
    const lastReview = application?.lastReview || {};
    const histories = Array.isArray(affiliate?.histories) ? affiliate.histories : [];
    const categories = Array.isArray(affiliate?.categories) ? affiliate.categories : [];
    const referralCode = affiliate?.referralCode && affiliate.referralCode !== '-' ? affiliate.referralCode : '';
    const flash = affiliate?.flash || {};
    const [selectedCategoryUrl, setSelectedCategoryUrl] = useState(categories[0]?.url || '/id');
    const {
        data: requestData,
        setData: setRequestData,
        post: postAffiliateRequest,
        processing: requestProcessing,
        errors: requestErrors,
        reset: resetRequestData,
    } = useForm({
        whatsapp: application?.defaultWhatsapp || '',
        promotion_channel_url: '',
        notes: '',
        agree_terms: false,
        agree_affiliate_policy: false,
    });
    const isHistoryActive = /^\/id\/affiliate(?:\/|$)/i.test(currentPath);
    const isSettlementActive = /^\/id\/withdrawal(?:\/|$)/i.test(currentPath);
    const isAffiliateRequestReady = Boolean(
        String(requestData.whatsapp || '').trim()
        && String(requestData.promotion_channel_url || '').trim()
        && requestData.agree_terms
        && requestData.agree_affiliate_policy,
    );

    const generatedLink = useMemo(() => {
        if (!referralCode) {
            return '';
        }

        const separator = selectedCategoryUrl.includes('?') ? '&' : '?';
        return `${selectedCategoryUrl}${separator}ref=${encodeURIComponent(referralCode)}`;
    }, [selectedCategoryUrl, referralCode]);

    const copyText = async (text, fallbackMessage) => {
        if (!text) {
            return;
        }

        try {
            await navigator.clipboard.writeText(text);
            window.alert(fallbackMessage);
        } catch (error) {
            window.alert('Clipboard tidak tersedia di browser ini.');
        }
    };

    const shareTo = (platform) => {
        if (!generatedLink) {
            return;
        }

        const trackedLink = `${generatedLink}&source=${platform}`;
        const message = encodeURIComponent('Mau topup game murah, aman, dan terpercaya? Cek di sini: ');
        const encodedLink = encodeURIComponent(trackedLink);

        let shareUrl = '';
        if (platform === 'wa') {
            shareUrl = `https://api.whatsapp.com/send?text=${message}%0A${encodedLink}`;
        } else if (platform === 'fb') {
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedLink}`;
        } else if (platform === 'tw') {
            shareUrl = `https://twitter.com/intent/tweet?text=${message}&url=${encodedLink}`;
        }

        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=680,height=560');
        }
    };

    const submitAffiliateRequest = (event) => {
        event.preventDefault();

        if (!isAffiliateRequestReady || requestProcessing) {
            return;
        }

        postAffiliateRequest(links.request || '/id/affiliate/request', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                resetRequestData(
                    'notes',
                    'agree_terms',
                    'agree_affiliate_policy',
                );
            },
        });
    };

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-dashboard-page public-affiliate-page">
                <div className="public-shell">
                    <div className="public-dashboard">
                        <UserDashboardSidebar links={links} />

                        <main className="public-dashboard-main">
                            <header className="public-dashboard-page-header public-dashboard-page-header--affiliate">
                                <h1>Program Afiliasi</h1>
                                <p>Ajak teman dan dapatkan komisi dari setiap transaksi mereka.</p>
                            </header>

                            <Notice message={flash.success} tone="success" />
                            <Notice message={flash.error} tone="error" />

                            <div className="public-affiliate-content">
                                <nav className="public-affiliate-tabs" aria-label="Tab afiliasi">
                                    <Link href={links.affiliate || '/id/affiliate'} className={isHistoryActive ? 'is-active' : ''}>Riwayat</Link>
                                    {links.canWithdraw ? (
                                        <Link href={links.withdrawal || '/id/withdrawal'} className={isSettlementActive ? 'is-active' : ''}>Pembayaran</Link>
                                    ) : null}
                                </nav>

                                    {(status === 'inactive' || status === 'rejected') ? (
                                        <section className="public-affiliate-state-card public-affiliate-state-card--application">
                                            <div className="public-affiliate-state-card__icon"><StateIcon type="inactive" /></div>
                                            <h2>Bergabung dengan Program Afiliasi</h2>
                                            <p>
                                                Dapatkan penghasilan tambahan dengan mereferensikan teman. Nikmati komisi menarik dari
                                                setiap transaksi referral kamu.
                                            </p>

                                            {status === 'rejected' ? (
                                                <div className="public-affiliate-notice is-error">
                                                    <strong>Pengajuan sebelumnya ditolak.</strong>{' '}
                                                    {String(lastReview?.note || '').trim()
                                                        ? `Catatan admin: ${String(lastReview.note)}`
                                                        : 'Silakan lengkapi ulang data terbaru, lalu kirim ulang pengajuan.'}
                                                </div>
                                            ) : null}

                                            <ul className="public-affiliate-requirements">
                                                {(application?.requirements || []).map((item) => (
                                                    <li key={item}>{item}</li>
                                                ))}
                                            </ul>

                                            <form className="public-affiliate-request-form" onSubmit={submitAffiliateRequest}>
                                                <div className="public-affiliate-request-form__grid">
                                                    <label>
                                                        <span>No. WhatsApp Aktif</span>
                                                        <input
                                                            type="text"
                                                            value={requestData.whatsapp}
                                                            onChange={(event) => setRequestData('whatsapp', event.target.value)}
                                                            placeholder="Contoh: 62812xxxx"
                                                        />
                                                        {requestErrors.whatsapp ? <small>{requestErrors.whatsapp}</small> : null}
                                                    </label>

                                                    <label>
                                                        <span>URL Channel Promosi (Wajib)</span>
                                                        <input
                                                            type="url"
                                                            value={requestData.promotion_channel_url}
                                                            onChange={(event) => setRequestData('promotion_channel_url', event.target.value)}
                                                            placeholder="https://instagram.com/username"
                                                        />
                                                        {requestErrors.promotion_channel_url ? <small>{requestErrors.promotion_channel_url}</small> : null}
                                                    </label>

                                                    <label className="is-full">
                                                        <span>Catatan Tambahan (Opsional)</span>
                                                        <textarea
                                                            rows={3}
                                                            value={requestData.notes}
                                                            onChange={(event) => setRequestData('notes', event.target.value)}
                                                            placeholder="Ceritakan singkat pengalaman atau strategi promosi kamu."
                                                        />
                                                        {requestErrors.notes ? <small>{requestErrors.notes}</small> : null}
                                                    </label>

                                                    <label className="is-full public-affiliate-request-form__checkbox">
                                                        <input
                                                            type="checkbox"
                                                            checked={requestData.agree_terms}
                                                            onChange={(event) => setRequestData('agree_terms', event.target.checked)}
                                                        />
                                                        <span>
                                                            Saya menyetujui{' '}
                                                            <a href={links.affiliateProgramTerms || '/id/affiliate/program-terms'} target="_blank" rel="noreferrer">
                                                                syarat program affiliate
                                                            </a>.
                                                        </span>
                                                    </label>
                                                    {requestErrors.agree_terms ? <small className="public-affiliate-request-form__checkbox-error">{requestErrors.agree_terms}</small> : null}

                                                    <label className="is-full public-affiliate-request-form__checkbox">
                                                        <input
                                                            type="checkbox"
                                                            checked={requestData.agree_affiliate_policy}
                                                            onChange={(event) => setRequestData('agree_affiliate_policy', event.target.checked)}
                                                        />
                                                        <span>
                                                            Saya menyetujui verifikasi data dan{' '}
                                                            <a href={links.privacyPolicy || '/id/privacy-policy'} target="_blank" rel="noreferrer">
                                                                kebijakan privasi
                                                            </a>.
                                                        </span>
                                                    </label>
                                                    {requestErrors.agree_affiliate_policy ? <small className="public-affiliate-request-form__checkbox-error">{requestErrors.agree_affiliate_policy}</small> : null}
                                                </div>

                                                <div className="public-affiliate-request-form__meta">
                                                    <p>{application?.allowedFilesLabel || 'Tidak perlu upload dokumen pada tahap pendaftaran awal.'}</p>
                                                    <p>
                                                        <a href={links.terms || '/id/terms-and-condition'} target="_blank" rel="noreferrer">
                                                            Terms & Conditions
                                                        </a>
                                                        {' '}•{' '}
                                                        <a href={links.privacyPolicy || '/id/privacy-policy'} target="_blank" rel="noreferrer">
                                                            Kebijakan Privasi
                                                        </a>
                                                    </p>
                                                </div>

                                                <button
                                                    type="submit"
                                                    className="public-affiliate-cta"
                                                    disabled={requestProcessing || !isAffiliateRequestReady}
                                                >
                                                    {requestProcessing ? 'Mengirim Pengajuan...' : 'Ajukan Permintaan Sekarang'}
                                                </button>
                                            </form>
                                        </section>
                                    ) : null}

                                    {status === 'pending' ? (
                                        <section className="public-affiliate-state-card is-pending">
                                            <div className="public-affiliate-state-card__icon"><StateIcon type="pending" /></div>
                                            <h2>Permintaan Sedang Diproses</h2>
                                            <p>
                                                Permintaan affiliate kamu sedang direview admin. Silakan cek berkala untuk status
                                                terbarunya.
                                            </p>
                                            <span className="public-affiliate-pill is-pending">Status: Pending</span>
                                        </section>
                                    ) : null}

                                    {status === 'active' ? (
                                        <>
                                            <section className="public-affiliate-overview">
                                                <article className="public-affiliate-overview__card">
                                                    <p>Kode Referral Anda</p>
                                                    <div className="public-affiliate-codebox">
                                                        <strong>{referralCode || '-'}</strong>
                                                        <div className="public-affiliate-codebox__actions">
                                                            <button type="button" onClick={() => copyText(referralCode, 'Kode referral disalin!')}>Salin Kode</button>
                                                            <button type="button" onClick={() => copyText(generatedLink, 'Link referral disalin!')}>Salin Link</button>
                                                        </div>
                                                    </div>
                                                </article>

                                                <article className="public-affiliate-overview__card is-highlight">
                                                    <p>Total Komisi Diterima</p>
                                                    <strong>{formatRupiah(affiliate?.totalCommission || 0)}</strong>
                                                    <span>Komisi dicairkan ke saldo akun secara otomatis.</span>
                                                </article>
                                            </section>

                                            <section className="public-affiliate-tools">
                                                <header>
                                                    <h2>Alat Marketing (Otomatis)</h2>
                                                    <p>Buat link referral spesifik game, lalu bagikan ke sosial media.</p>
                                                </header>
                                                <div className="public-affiliate-tools__body">
                                                    <div className="public-affiliate-tools__form">
                                                        <label htmlFor="affiliate-category">Pilih Game / Layanan</label>
                                                        <select
                                                            id="affiliate-category"
                                                            value={selectedCategoryUrl}
                                                            onChange={(event) => setSelectedCategoryUrl(event.target.value)}
                                                        >
                                                            {categories.map((category) => (
                                                                <option key={category.url} value={category.url}>{category.label}</option>
                                                            ))}
                                                        </select>

                                                        <label htmlFor="affiliate-link">Link Referral Anda</label>
                                                        <div className="public-affiliate-linkbox">
                                                            <input id="affiliate-link" type="text" readOnly value={generatedLink} />
                                                            <button type="button" onClick={() => copyText(generatedLink, 'Link referral disalin!')}>Salin</button>
                                                        </div>
                                                    </div>

                                                    <div className="public-affiliate-tools__share">
                                                        <p>Bagikan Cepat (Auto Tracking)</p>
                                                        <div className="public-affiliate-tools__share-grid">
                                                            <button type="button" onClick={() => shareTo('wa')}>WhatsApp</button>
                                                            <button type="button" onClick={() => shareTo('fb')}>Facebook</button>
                                                            <button type="button" onClick={() => shareTo('tw')}>X</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </section>

                                            <section className="public-dashboard-table public-affiliate-history public-dashboard-table--history public-dashboard-table--affiliate">
                                                <header className="public-affiliate-history__header">
                                                    <h2>Riwayat Komisi</h2>
                                                </header>

                                                <div className="public-dashboard-table__shell">
                                                    {histories.length ? (
                                                        <table className="public-dashboard-table__table">
                                                            <thead>
                                                                <tr>
                                                                    <th>Waktu</th>
                                                                    <th>Dari (Downlink)</th>
                                                                    <th>Order ID</th>
                                                                    <th>Jumlah</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {histories.map((history) => (
                                                                    <tr key={`${history.orderId}-${history.createdAt}`}>
                                                                        <td>{history.createdAt || '-'}</td>
                                                                        <td>{history.downlink || 'Unknown'}</td>
                                                                        <td className="public-dashboard-table__invoice-link">{history.orderId || '-'}</td>
                                                                        <td>{formatRupiah(history.amount)}</td>
                                                                        <td>
                                                                            <span className="public-dashboard-table__badge public-dashboard-table__badge--success">
                                                                                {history.status || 'Sukses'}
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    ) : (
                                                        <div className="public-dashboard-table__empty">Belum ada komisi affiliate.</div>
                                                    )}
                                                </div>

                                                {(affiliate?.pagination?.prevPageUrl || affiliate?.pagination?.nextPageUrl) ? (
                                                    <div className="public-affiliate-pagination">
                                                        {affiliate?.pagination?.prevPageUrl ? (
                                                            <a href={affiliate.pagination.prevPageUrl}>Sebelumnya</a>
                                                        ) : (
                                                            <span className="is-disabled">Sebelumnya</span>
                                                        )}
                                                        <span>
                                                            Halaman {affiliate?.pagination?.currentPage || 1} / {affiliate?.pagination?.lastPage || 1}
                                                        </span>
                                                        {affiliate?.pagination?.nextPageUrl ? (
                                                            <a href={affiliate.pagination.nextPageUrl}>Berikutnya</a>
                                                        ) : (
                                                            <span className="is-disabled">Berikutnya</span>
                                                        )}
                                                    </div>
                                                ) : null}
                                            </section>
                                        </>
                                    ) : null}
                            </div>
                        </main>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

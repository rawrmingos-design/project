import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ResellerLayout from '../../Layouts/ResellerLayout';

/**
 * Pagination component using Laravel paginator links.
 * Renders Prev/Next + page numbers consistent with the rh-button design system.
 */
function Pagination({ links }) {
    if (!links || links.length <= 3) return null;

    return (
        <div style={{ display: 'flex', justifyContent: 'center', gap: '6px', marginTop: '24px', flexWrap: 'wrap' }}>
            {links.map((link, index) => {
                const isPrev = index === 0;
                const isNext = index === links.length - 1;
                const label  = link.label
                    .replace('&laquo;', '←')
                    .replace('&raquo;', '→')
                    .replace(/&amp;/g, '&');

                return link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        style={{
                            padding: '6px 12px',
                            borderRadius: '8px',
                            fontSize: '0.85rem',
                            textDecoration: 'none',
                            border: link.active
                                ? '1px solid var(--primary)'
                                : '1px solid rgba(255,255,255,0.1)',
                            background: link.active
                                ? 'var(--primary)'
                                : 'rgba(255,255,255,0.05)',
                            color: link.active ? '#fff' : 'var(--on-surface-variant)',
                            fontWeight: link.active ? 600 : 400,
                            transition: 'all 0.15s ease',
                            minWidth: isPrev || isNext ? 'auto' : '36px',
                            textAlign: 'center',
                        }}
                        preserveScroll
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                ) : (
                    <span
                        key={index}
                        style={{
                            padding: '6px 12px',
                            borderRadius: '8px',
                            fontSize: '0.85rem',
                            border: '1px solid rgba(255,255,255,0.05)',
                            background: 'transparent',
                            color: 'rgba(255,255,255,0.2)',
                            minWidth: '36px',
                            textAlign: 'center',
                        }}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </div>
    );
}

export default function CallbackLogs({ deliveries, hasActiveSandboxProfile }) {
    // Track which delivery IDs are currently being resent
    const [resendingIds, setResendingIds] = useState({});
    // Track test webhook loading state
    const [isTesting, setIsTesting] = useState(false);

    const handleResend = (deliveryId) => {
        setResendingIds(prev => ({ ...prev, [deliveryId]: true }));

        router.post(
            `/id/reseller/callbacks/${deliveryId}/resend`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setResendingIds(prev => ({ ...prev, [deliveryId]: false }));
                },
            }
        );
    };

    const handleTestWebhook = () => {
        setIsTesting(true);
        router.post(
            '/id/reseller/callbacks/test',
            {},
            {
                preserveScroll: true,
                onFinish: () => setIsTesting(false),
            }
        );
    };

    return (
        <ResellerLayout headerTitle="Callback Logs">
            <Head title="Callback Logs" />

            <section className="rh-card">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
                    <div>
                        <h2 style={{ fontSize: '1.2rem', margin: 0, color: '#fff' }}>Webhook Delivery Logs</h2>
                        {deliveries?.total > 0 && (
                            <span style={{ fontSize: '0.8rem', color: 'var(--on-surface-variant)' }}>
                                {deliveries.total} entries
                            </span>
                        )}
                    </div>
                    {/* Test Webhook Button — Phase 5 Task 5.3 */}
                    <button
                        onClick={handleTestWebhook}
                        disabled={isTesting || !hasActiveSandboxProfile}
                        title={!hasActiveSandboxProfile
                            ? 'Setup webhook profile sandbox di halaman Credentials terlebih dahulu'
                            : 'Kirim synthetic test event ke webhook URL Anda'
                        }
                        style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '6px',
                            padding: '8px 14px',
                            borderRadius: '8px',
                            fontSize: '0.8rem',
                            fontWeight: 500,
                            border: '1px solid rgba(255,255,255,0.15)',
                            background: isTesting || !hasActiveSandboxProfile
                                ? 'rgba(255,255,255,0.05)'
                                : 'rgba(255,255,255,0.1)',
                            color: isTesting || !hasActiveSandboxProfile
                                ? 'rgba(255,255,255,0.3)'
                                : 'var(--on-surface)',
                            cursor: isTesting || !hasActiveSandboxProfile ? 'not-allowed' : 'pointer',
                            transition: 'all 0.15s ease',
                        }}
                    >
                        <span>{isTesting ? '⏳' : '🧪'}</span>
                        <span>{isTesting ? 'Mengirim...' : 'Test Webhook'}</span>
                    </button>
                </div>

                <div className="rh-table-container">
                    {deliveries?.data?.length > 0 ? (
                        <table className="rh-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Environment</th>
                                    <th>Order ID</th>
                                    <th>Status</th>
                                    <th>HTTP</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {deliveries.data.map((log) => {
                                    const isResending   = !!resendingIds[log.id];
                                    const canResend     = log.status === 'failed' && log.attempt_count < 4;
                                    const maxedOut      = log.status === 'failed' && log.attempt_count >= 4;

                                    return (
                                        <tr key={log.id}>
                                            <td style={{ color: 'var(--on-surface-variant)', fontSize: '0.85rem' }}>
                                                {new Date(log.created_at).toLocaleString('id-ID')}
                                            </td>

                                            <td>
                                                <span style={{
                                                    fontSize: '0.75rem',
                                                    padding: '2px 6px',
                                                    borderRadius: '4px',
                                                    background: 'rgba(255,255,255,0.1)',
                                                }}>
                                                    {log.integration?.mode === 'sandbox' ? 'SANDBOX' : 'LIVE'}
                                                </span>
                                            </td>

                                            <td style={{ color: 'var(--primary)', fontFamily: 'var(--font-mono, monospace)', fontSize: '0.85rem' }}>
                                                {log.pembelian ? log.pembelian.order_id : '-'}
                                            </td>

                                            <td>
                                                {log.status === 'success' || log.status === 'delivered' ? (
                                                    <span className="rh-badge rh-badge--success">Delivered</span>
                                                ) : log.status === 'pending' ? (
                                                    <span className="rh-badge rh-badge--warning">Pending</span>
                                                ) : (
                                                    <span className="rh-badge rh-badge--danger">Failed</span>
                                                )}
                                            </td>

                                            <td style={{ color: 'var(--on-surface-variant)', fontSize: '0.9rem' }}>
                                                {log.last_response_status || '-'}
                                            </td>

                                            <td>
                                                {log.status === 'delivered' || log.status === 'success' ? (
                                                    <span style={{ color: 'var(--on-surface-variant)', fontSize: '0.8rem' }}>—</span>
                                                ) : maxedOut ? (
                                                    <span style={{ color: 'rgba(255,255,255,0.3)', fontSize: '0.8rem' }}>Max retries</span>
                                                ) : canResend ? (
                                                    <button
                                                        onClick={() => handleResend(log.id)}
                                                        disabled={isResending}
                                                        id={`resend-btn-${log.id}`}
                                                        style={{
                                                            background: 'rgba(99,102,241,0.15)',
                                                            border: '1px solid rgba(99,102,241,0.4)',
                                                            borderRadius: '6px',
                                                            color: '#a5b4fc',
                                                            fontSize: '0.8rem',
                                                            padding: '4px 10px',
                                                            cursor: isResending ? 'not-allowed' : 'pointer',
                                                            opacity: isResending ? 0.6 : 1,
                                                            display: 'inline-flex',
                                                            alignItems: 'center',
                                                            gap: '4px',
                                                            transition: 'all 0.15s ease',
                                                        }}
                                                        aria-label={`Resend callback for ${log.order_id ?? log.id}`}
                                                    >
                                                        <span
                                                            className="material-symbols-outlined"
                                                            style={{
                                                                fontSize: '14px',
                                                                animation: isResending ? 'spin 1s linear infinite' : 'none',
                                                            }}
                                                        >
                                                            {isResending ? 'progress_activity' : 'send'}
                                                        </span>
                                                        {isResending ? 'Sending…' : 'Resend'}
                                                    </button>
                                                ) : null}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    ) : (
                        <div style={{ textAlign: 'center', padding: '48px 0', color: 'var(--on-surface-variant)' }}>
                            <span className="material-symbols-outlined" style={{ fontSize: '40px', display: 'block', marginBottom: '12px', opacity: 0.4 }}>
                                history_toggle_off
                            </span>
                            No callback logs found.
                        </div>
                    )}
                </div>

                {/* Traditional Pagination */}
                <Pagination links={deliveries?.links} />
            </section>

            <style>{`
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to   { transform: rotate(360deg); }
                }
            `}</style>
        </ResellerLayout>
    );
}

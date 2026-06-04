import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ResellerLayout from '../../Layouts/ResellerLayout';

function StatusBadge({ status }) {
    const successLabels = ['Sukses', 'Success'];
    const pendingLabels = ['Pending', 'Processing', 'Proses'];
    const tone = successLabels.includes(status)
        ? 'success'
        : pendingLabels.includes(status)
            ? 'warning'
            : 'danger';
    return <span className={`rh-badge rh-badge--${tone}`}>{status}</span>;
}

/**
 * Pagination component — reuses same pattern as CallbackLogs.jsx
 */
function Pagination({ links }) {
    if (!links || links.length <= 3) return null;
    return (
        <div style={{ display: 'flex', justifyContent: 'center', gap: '6px', marginTop: '24px', flexWrap: 'wrap' }}>
            {links.map((link, index) => {
                const isPrev = index === 0;
                const isNext = index === links.length - 1;
                const label = link.label
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
                            border: link.active ? '1px solid var(--primary)' : '1px solid rgba(255,255,255,0.1)',
                            background: link.active ? 'var(--primary)' : 'rgba(255,255,255,0.05)',
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

// Phase 5 — Task 5.4: Status filter tabs (server-side, bookmarkable)
const STATUS_TABS = [
    { key: null,      label: 'Semua' },
    { key: 'success', label: '✓ Sukses' },
    { key: 'pending', label: '⏳ Pending' },
    { key: 'failed',  label: '✕ Gagal' },
];

function StatusFilterTabs({ currentFilter }) {
    const handleTab = (key) => {
        const params = key ? { status: key } : {};
        router.get('/id/reseller/orders', params, { preserveScroll: true, replace: true });
    };

    return (
        <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
            {STATUS_TABS.map(tab => {
                const isActive = tab.key === currentFilter || (tab.key === null && !currentFilter);
                return (
                    <button
                        key={tab.key ?? 'all'}
                        onClick={() => handleTab(tab.key)}
                        style={{
                            padding: '6px 14px',
                            borderRadius: '20px',
                            fontSize: '0.8rem',
                            fontWeight: isActive ? 600 : 400,
                            border: isActive ? '1px solid var(--primary)' : '1px solid rgba(255,255,255,0.15)',
                            background: isActive ? 'var(--primary)' : 'transparent',
                            color: isActive ? '#fff' : 'var(--on-surface-variant)',
                            cursor: 'pointer',
                            transition: 'all 0.15s ease',
                        }}
                    >
                        {tab.label}
                    </button>
                );
            })}
        </div>
    );
}

export default function OrderLogs({ orders, currentFilter }) {
    return (
        <ResellerLayout headerTitle="Order History">
            <Head title="Order History" />

            <section className="rh-card">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px', flexWrap: 'wrap', gap: '12px' }}>
                    <div>
                        <h2 style={{ fontSize: '1.2rem', margin: 0, color: '#fff' }}>Transaction Logs</h2>
                        {orders?.total > 0 && (
                            <span style={{ fontSize: '0.8rem', color: 'var(--on-surface-variant)' }}>
                                {orders.total} orders
                                {currentFilter && ` — filter: ${currentFilter}`}
                            </span>
                        )}
                    </div>
                    <StatusFilterTabs currentFilter={currentFilter} />
                </div>

                <div className="rh-table-container">
                    {orders && orders.data && orders.data.length > 0 ? (
                        <table className="rh-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Environment</th>
                                    <th>Order ID</th>
                                    <th>Product</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {orders.data.map((order) => (
                                    <tr key={order.id}>
                                        <td style={{ color: 'var(--on-surface-variant)', fontSize: '0.85rem' }}>
                                            {new Date(order.created_at).toLocaleString('id-ID')}
                                        </td>
                                        <td>
                                            <span style={{ fontSize: '0.75rem', padding: '2px 6px', borderRadius: '4px', background: 'rgba(255,255,255,0.1)' }}>
                                                {(order.reseller_integration?.mode === 'sandbox' || order.is_sandbox) ? 'SANDBOX' : 'LIVE'}
                                            </span>
                                        </td>
                                        <td>
                                            <div style={{ color: 'var(--primary)', fontFamily: 'var(--font-heading)', fontWeight: 500 }}>{order.order_id}</div>
                                            {order.provider_order_id && (
                                                <div style={{ fontSize: '0.8rem', color: 'var(--on-surface-variant)' }}>{order.provider_order_id}</div>
                                            )}
                                        </td>
                                        <td style={{ color: '#fff' }}>{order.layanan}</td>
                                        <td>
                                            <StatusBadge status={order.status} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <div style={{ textAlign: 'center', padding: '32px 0', color: 'var(--on-surface-variant)' }}>
                            {currentFilter
                                ? `Tidak ada order dengan status "${currentFilter}".`
                                : 'No order history found.'
                            }
                        </div>
                    )}
                </div>

                <Pagination links={orders?.links} />
            </section>
        </ResellerLayout>
    );
}

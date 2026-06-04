import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import ResellerLayout from '../../Layouts/ResellerLayout';

function formatRupiah(value) {
    return `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;
}

/**
 * Format date based on deposit status and available data.
 * - For Success: show "Lunas" date (updated_at — saat status berubah ke Success)
 * - For Pending/Gagal: show "Order" date (created_at)
 */
function formatDate(deposit) {
    if (deposit.status === 'Success' && deposit.updated_at) {
        return {
            label: 'Lunas',
            value: new Date(deposit.updated_at).toLocaleString('id-ID', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            }),
        };
    }
    return {
        label: 'Dibuat',
        value: new Date(deposit.created_at).toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        }),
    };
}

const STATUS_TONE = {
    Success: 'success',
    Pending: 'warning',
    Gagal:   'danger',
};

const STATUS_FILTERS = [
    { value: '',        label: 'Semua' },
    { value: 'Success', label: 'Lunas' },
    { value: 'Pending', label: 'Pending' },
    { value: 'Gagal',   label: 'Gagal' },
];

export default function DepositHistory({ deposits, activeFilter }) {
    const handleFilterChange = (value) => {
        router.get('/id/reseller/deposits', value ? { status: value } : {}, {
            preserveState: true,
            replace: true,
        });
    };

    const data  = deposits?.data ?? [];
    const links = deposits?.links ?? [];
    const meta  = deposits?.meta ?? {};

    return (
        <ResellerLayout headerTitle="Riwayat Deposit">
            <Head title="Riwayat Deposit" />

            <section className="rh-card" style={{ marginBottom: '32px' }}>
                {/* Header */}
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px', flexWrap: 'wrap', gap: '12px' }}>
                    <div>
                        <h2 style={{ fontSize: '1.2rem', color: '#fff', margin: '0 0 4px' }}>Riwayat Deposit</h2>
                        <p style={{ color: 'var(--on-surface-variant)', fontSize: '0.9rem', margin: 0 }}>
                            Histori top-up saldo akun Reseller Hub Anda.
                        </p>
                    </div>

                    {/* Filter Status */}
                    <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                        {STATUS_FILTERS.map((f) => (
                            <button
                                key={f.value}
                                onClick={() => handleFilterChange(f.value)}
                                style={{
                                    padding: '6px 16px',
                                    borderRadius: '99px',
                                    border: '1px solid',
                                    fontSize: '0.8rem',
                                    cursor: 'pointer',
                                    transition: 'all 0.15s ease',
                                    borderColor: (activeFilter ?? '') === f.value
                                        ? 'var(--accent-primary)'
                                        : 'rgba(255,255,255,0.12)',
                                    background: (activeFilter ?? '') === f.value
                                        ? 'var(--primary-container)'
                                        : 'transparent',
                                    color: (activeFilter ?? '') === f.value
                                        ? 'var(--accent-primary)'
                                        : 'var(--on-surface-variant)',
                                    fontWeight: (activeFilter ?? '') === f.value ? '600' : '400',
                                }}
                            >
                                {f.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Table */}
                <div className="rh-table-container">
                    {data.length === 0 ? (
                        <div style={{ textAlign: 'center', padding: '48px 0', color: 'var(--on-surface-variant)' }}>
                            <span className="material-symbols-outlined" style={{ fontSize: '48px', marginBottom: '12px', display: 'block', opacity: 0.4 }}>
                                payments
                            </span>
                            <p style={{ margin: 0 }}>
                                {activeFilter
                                    ? `Tidak ada deposit dengan status "${STATUS_FILTERS.find(f => f.value === activeFilter)?.label}".`
                                    : 'Belum ada riwayat deposit.'}
                            </p>
                        </div>
                    ) : (
                        <table className="rh-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Metode</th>
                                    <th style={{ textAlign: 'right' }}>Jumlah</th>
                                    <th style={{ textAlign: 'center' }}>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                {data.map((deposit) => {
                                    const { label: dateLabel, value: dateValue } = formatDate(deposit);
                                    return (
                                        <tr key={deposit.id}>
                                            <td>
                                                <span style={{ fontFamily: 'var(--font-mono, monospace)', fontSize: '0.85rem', color: 'var(--accent-primary)' }}>
                                                    {deposit.order_id}
                                                </span>
                                            </td>
                                            <td style={{ color: 'var(--on-surface-variant)' }}>
                                                {deposit.metode}
                                            </td>
                                            <td style={{ textAlign: 'right', fontWeight: '600', color: '#fff' }}>
                                                {formatRupiah(deposit.jumlah)}
                                            </td>
                                            <td style={{ textAlign: 'center' }}>
                                                <span className={`rh-badge rh-badge--${STATUS_TONE[deposit.status] ?? 'default'}`}>
                                                    {deposit.status === 'Success' ? 'Lunas' : deposit.status}
                                                </span>
                                            </td>
                                            <td>
                                                <div style={{ fontSize: '0.8rem' }}>
                                                    <span style={{ color: 'rgba(255,255,255,0.35)', marginRight: '4px' }}>
                                                        {dateLabel}:
                                                    </span>
                                                    <span style={{ color: 'var(--on-surface-variant)' }}>
                                                        {dateValue}
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* Pagination */}
                {links.length > 3 && (
                    <div style={{ display: 'flex', justifyContent: 'center', gap: '6px', marginTop: '24px', flexWrap: 'wrap' }}>
                        {links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url ?? '#'}
                                preserveState
                                className={`rh-button ${link.active ? 'rh-button--primary' : 'rh-button--secondary'}`}
                                style={{
                                    minWidth: '36px',
                                    padding: '6px 10px',
                                    fontSize: '0.8rem',
                                    opacity: link.url ? 1 : 0.35,
                                    pointerEvents: link.url ? 'auto' : 'none',
                                }}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}

                {/* Count */}
                {meta.total > 0 && (
                    <p style={{ textAlign: 'center', marginTop: '12px', color: 'rgba(255,255,255,0.3)', fontSize: '0.8rem' }}>
                        Menampilkan {meta.from}–{meta.to} dari {meta.total} deposit
                    </p>
                )}
            </section>
        </ResellerLayout>
    );
}

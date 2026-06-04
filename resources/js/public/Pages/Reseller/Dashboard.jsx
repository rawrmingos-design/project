import React from 'react';
import { Head, Link } from '@inertiajs/react';
import ResellerLayout from '../../Layouts/ResellerLayout';

function formatRupiah(value) {
    return `Rp ${new Intl.NumberFormat('id-ID').format(Number(value || 0))}`;
}

function formatNumber(value) {
    return new Intl.NumberFormat('id-ID').format(Number(value || 0));
}

function StatusBadge({ status }) {
    const tone = status === 'Success' ? 'success' : status === 'Pending' ? 'warning' : 'danger';
    return <span className={`rh-badge rh-badge--${tone}`}>{status}</span>;
}

export default function Dashboard({ meta, live, sandbox, metrics, recent_orders }) {
    return (
        <ResellerLayout meta={meta} headerTitle="Overview">
            <Head title="Reseller Dashboard" />

            <section style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px', marginBottom: '32px' }}>
                <article className="rh-card">
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0, fontSize: '1.2rem', color: '#fff' }}>Live Integration</h3>
                            <span className={`rh-badge rh-badge--${live?.is_active ? 'success' : 'danger'}`}>
                                {live?.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        {live?.endpoint && (
                            <div style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)' }}>
                                Endpoint: <code style={{ color: 'var(--accent-primary)' }}>{live.endpoint}</code>
                            </div>
                        )}
                        <div style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)' }}>
                            IP Whitelist: <strong style={{ color: '#fff' }}>{live?.allowed_ips?.length || 0}</strong> / 20 IPs Configured
                        </div>
                    </div>
                </article>

                <article className="rh-card">
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <h3 style={{ margin: 0, fontSize: '1.2rem', color: '#fff' }}>Sandbox Integration</h3>
                            <span className={`rh-badge rh-badge--${sandbox?.is_active ? 'success' : 'warning'}`}>
                                {sandbox?.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        {sandbox?.endpoint && (
                            <div style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)' }}>
                                Endpoint: <code style={{ color: 'var(--accent-primary)' }}>{sandbox.endpoint}</code>
                            </div>
                        )}
                        <div style={{ fontSize: '0.85rem', color: 'var(--on-surface-variant)' }}>
                            Sandbox Key Hint: <strong style={{ color: '#fff' }}>{sandbox?.api_key_hint || 'Belum diatur'}</strong>
                        </div>
                    </div>
                </article>
            </section>

            <section className="rh-card" style={{ marginBottom: '32px' }}>
                <h2 style={{ fontSize: '1.2rem', marginBottom: '24px', color: '#fff' }}>Performa Hari Ini</h2>
                <div className="rh-stat-grid">
                    <div>
                        <div className="rh-stat-value">{formatNumber(metrics?.orders_today || 0)}</div>
                        <div className="rh-stat-label">Total Transaksi</div>
                    </div>
                    <div>
                        <div className="rh-stat-value" style={{ color: 'var(--accent-primary)' }}>{formatRupiah(metrics?.revenue_today || 0)}</div>
                        <div className="rh-stat-label">Est. Revenue</div>
                    </div>
                    <div>
                        <div className="rh-stat-value" style={{ color: 'var(--accent-success)' }}>{metrics?.success_rate || 0}%</div>
                        <div className="rh-stat-label">Success Rate</div>
                    </div>
                    <div>
                        <div className="rh-stat-value" style={{ color: 'var(--accent-danger)' }}>{formatNumber(metrics?.failed_pending_today || 0)}</div>
                        <div className="rh-stat-label">Gagal / Pending</div>
                    </div>
                </div>
            </section>

            <section className="rh-card">
                <h2 style={{ fontSize: '1.2rem', marginBottom: '24px', color: '#fff' }}>Riwayat Transaksi H2H Terbaru</h2>
                <div className="rh-table-container">
                    {recent_orders && recent_orders.length > 0 ? (
                        <table className="rh-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Provider ID</th>
                                    <th>Layanan</th>
                                    <th>Harga</th>
                                    <th>Tanggal</th>
                                    <th>Environment</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recent_orders.map((item) => (
                                    <tr key={item.id}>
                                        <td style={{ fontFamily: 'var(--font-heading)', fontWeight: 500 }}>{item.order_id}</td>
                                        <td style={{ color: 'var(--on-surface-variant)' }}>{item.provider_order_id || '-'}</td>
                                        <td>{item.layanan}</td>
                                        <td style={{ fontFamily: 'var(--font-heading)', fontWeight: 500 }}>{formatRupiah(item.harga)}</td>
                                        <td style={{ color: 'var(--on-surface-variant)' }}>{new Date(item.created_at).toLocaleString('id-ID')}</td>
                                        <td>
                                            <span style={{ fontSize: '0.75rem', padding: '2px 6px', borderRadius: '4px', background: 'rgba(255,255,255,0.1)' }}>
                                                {(item.reseller_integration?.mode === 'sandbox' || item.is_sandbox) ? 'SANDBOX' : 'LIVE'}
                                            </span>
                                        </td>
                                        <td><StatusBadge status={item.status} /></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <div style={{ textAlign: 'center', padding: '32px 0', color: 'var(--on-surface-variant)' }}>
                            Belum ada transaksi melalui H2H API hari ini.
                        </div>
                    )}
                </div>
            </section>
        </ResellerLayout>
    );
}

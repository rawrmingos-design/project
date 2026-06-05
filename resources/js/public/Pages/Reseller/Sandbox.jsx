import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import ResellerLayout from '../../Layouts/ResellerLayout';

export default function Sandbox({ is_sandbox_active, recent_orders = [] }) {
    const webhookForm = useForm({});
    const simulateForm = useForm({
        invoice: '',
        status: 'Success'
    });

    const fireWebhook = (e) => {
        e.preventDefault();
        webhookForm.post('/id/reseller/callbacks/test', {
            preserveScroll: true,
        });
    };

    const submitSimulate = (e) => {
        e.preventDefault();
        simulateForm.post('/id/reseller/sandbox/simulate', {
            preserveScroll: true,
            onSuccess: () => simulateForm.reset('invoice', 'status')
        });
    };

    return (
        <ResellerLayout headerTitle="Sandbox Environment">
            <Head title="Sandbox Guide" />

            {!is_sandbox_active && (
                <article className="rh-alert rh-alert--warning" style={{ background: 'rgba(239, 68, 68, 0.1)', borderColor: 'rgba(239, 68, 68, 0.2)' }}>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" style={{ flexShrink: 0, color: 'var(--accent-danger)' }}>
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <div>
                        <h2 style={{ margin: '0 0 8px', color: 'var(--accent-danger)', fontSize: '1rem' }}>Sandbox Inactive</h2>
                        <p style={{ margin: 0, color: 'rgba(255,255,255,0.8)', fontSize: '14px' }}>
                            You do not have an active Sandbox integration. Contact an administrator to enable it for your account.
                        </p>
                    </div>
                </article>
            )}

            <section style={{ display: 'grid', gridTemplateColumns: '1fr', gap: '24px' }}>
                <article className="rh-card">
                    <h3 style={{ margin: '0 0 16px', color: 'var(--primary)', fontSize: '1.2rem' }}>1. Understand the Sandbox Environment</h3>
                    <p style={{ color: 'var(--on-surface-variant)', marginBottom: '16px', lineHeight: 1.6 }}>
                        The Sandbox environment allows you to test your integration without spending real balance. 
                        It simulates provider responses and order status updates locally.
                    </p>
                    <ul style={{ color: '#fff', paddingLeft: '20px', lineHeight: 1.8 }}>
                        <li>Use the <code style={{ background: 'rgba(255,255,255,0.1)', padding: '2px 6px', borderRadius: '4px', fontFamily: 'var(--font-mono, monospace)', color: 'var(--accent-primary)' }}>/api/v1/sandbox/*</code> endpoints.</li>
                        <li>Pass your <strong>Sandbox API Key</strong> as the Bearer Token.</li>
                    </ul>
                </article>

                {/* NEW: Sandbox Order Simulator */}
                <article className="rh-card">
                    <h3 style={{ margin: '0 0 16px', color: 'var(--primary)', fontSize: '1.2rem' }}>2. Order Status Simulator</h3>
                    <p style={{ color: 'var(--on-surface-variant)', marginBottom: '16px', lineHeight: 1.6 }}>
                        Ubah status pesanan sandbox Anda secara manual untuk menguji bagaimana sistem Anda merespons perubahan status.
                    </p>

                    <form onSubmit={submitSimulate} style={{ background: 'rgba(0,0,0,0.2)', border: '1px solid rgba(255,255,255,0.05)', padding: '20px', borderRadius: 'var(--radius-md)', display: 'flex', flexDirection: 'column', gap: '16px' }}>
                        <div>
                            <label style={{ display: 'block', color: 'rgba(255,255,255,0.7)', fontSize: '14px', marginBottom: '8px' }}>
                                Pilih Pesanan Sandbox Terakhir (atau input manual)
                            </label>
                            {recent_orders.length > 0 ? (
                                <select 
                                    className="rh-input" 
                                    style={{ width: '100%', marginBottom: '8px' }}
                                    onChange={(e) => simulateForm.setData('invoice', e.target.value)}
                                    value={simulateForm.data.invoice}
                                >
                                    <option value="" disabled style={{ background: '#191f31', color: '#fff' }}>-- Pilih Invoice --</option>
                                    {recent_orders.map(order => (
                                        <option key={order.order_id} value={order.order_id} style={{ background: '#191f31', color: '#fff' }}>
                                            {order.order_id} - {order.layanan} ({order.status})
                                        </option>
                                    ))}
                                </select>
                            ) : (
                                <p style={{ fontSize: '0.85rem', color: 'var(--accent-warning)', margin: '0 0 8px' }}>Belum ada pesanan Sandbox.</p>
                            )}
                            <input
                                type="text"
                                className="rh-input"
                                placeholder="Input manual Order ID (opsional jika pilih di atas)"
                                value={simulateForm.data.invoice}
                                onChange={e => simulateForm.setData('invoice', e.target.value)}
                                style={{ width: '100%', colorScheme: 'dark' }}
                            />
                            {simulateForm.errors.invoice && <div style={{ color: 'var(--accent-danger)', fontSize: '0.8rem', marginTop: '4px' }}>{simulateForm.errors.invoice}</div>}
                        </div>

                        <div>
                            <label style={{ display: 'block', color: 'rgba(255,255,255,0.7)', fontSize: '14px', marginBottom: '8px' }}>Target Status</label>
                            <select
                                className="rh-input"
                                value={simulateForm.data.status}
                                onChange={e => simulateForm.setData('status', e.target.value)}
                                style={{ width: '100%', colorScheme: 'dark' }}
                            >
                                <option value="Pending" style={{ background: '#191f31', color: '#fff' }}>Pending</option>
                                <option value="Processing" style={{ background: '#191f31', color: '#fff' }}>Processing</option>
                                <option value="Success" style={{ background: '#191f31', color: '#fff' }}>Success</option>
                                <option value="Failed" style={{ background: '#191f31', color: '#fff' }}>Failed</option>
                                <option value="Cancelled" style={{ background: '#191f31', color: '#fff' }}>Cancelled</option>
                            </select>
                        </div>

                        <button type="submit" className="rh-button rh-button--primary" disabled={simulateForm.processing} style={{ alignSelf: 'flex-start' }}>
                            {simulateForm.processing ? 'Memproses...' : 'Simulasi Status'}
                        </button>
                    </form>
                </article>

                <article className="rh-card">
                    <h3 style={{ margin: '0 0 16px', color: 'var(--primary)', fontSize: '1.2rem' }}>3. Testing Callbacks (Webhooks)</h3>
                    <p style={{ color: 'var(--on-surface-variant)', marginBottom: '16px', lineHeight: 1.6 }}>
                        When an order status changes in Sandbox, we will fire a webhook to your configured Live webhook URL, 
                        but the payload will include <code style={{ background: 'rgba(255,255,255,0.1)', padding: '2px 6px', borderRadius: '4px', fontFamily: 'var(--font-mono, monospace)', color: 'var(--accent-primary)' }}>"mode": "sandbox"</code>.
                    </p>
                    
                    <form onSubmit={fireWebhook} style={{ background: 'rgba(0,0,0,0.2)', border: '1px solid rgba(255,255,255,0.05)', padding: '20px', borderRadius: 'var(--radius-md)' }}>
                        <h4 style={{ margin: '0 0 12px', color: 'var(--accent-secondary)' }}>Interactive Webhook Testing</h4>
                        <p style={{ color: 'rgba(255,255,255,0.8)', fontSize: '14px', marginBottom: '16px' }}>
                            Kirimkan payload tiruan (*dummy*) ke endpoint Webhook URL Anda untuk memverifikasi apakah server Anda merespons dengan benar.
                        </p>
                        <button type="submit" className="rh-button rh-button--secondary" disabled={webhookForm.processing}>
                            {webhookForm.processing ? 'Mengirim...' : 'Kirim Test Webhook'}
                        </button>
                    </form>
                </article>
            </section>
        </ResellerLayout>
    );
}

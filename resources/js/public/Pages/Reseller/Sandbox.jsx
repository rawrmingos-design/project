import React from 'react';
import { Head } from '@inertiajs/react';
import ResellerLayout from '../../Layouts/ResellerLayout';

export default function Sandbox({ is_sandbox_active }) {
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

                <article className="rh-card">
                    <h3 style={{ margin: '0 0 16px', color: 'var(--primary)', fontSize: '1.2rem' }}>2. Testing Callbacks (Webhooks)</h3>
                    <p style={{ color: 'var(--on-surface-variant)', marginBottom: '16px', lineHeight: 1.6 }}>
                        When an order status changes in Sandbox, we will fire a webhook to your configured Live webhook URL, 
                        but the payload will include <code style={{ background: 'rgba(255,255,255,0.1)', padding: '2px 6px', borderRadius: '4px', fontFamily: 'var(--font-mono, monospace)', color: 'var(--accent-primary)' }}>"mode": "sandbox"</code>.
                    </p>
                    <div style={{ background: 'rgba(0,0,0,0.2)', border: '1px solid rgba(255,255,255,0.05)', padding: '16px', borderRadius: 'var(--radius-md)' }}>
                        <p style={{ color: '#fff', margin: 0 }}>
                            <strong style={{ color: 'var(--accent-secondary)' }}>Interactive Webhook Testing:</strong><br/>
                            Currently under development. Soon, you will be able to manually trigger test payloads to your endpoint from this page.
                        </p>
                    </div>
                </article>
            </section>
        </ResellerLayout>
    );
}

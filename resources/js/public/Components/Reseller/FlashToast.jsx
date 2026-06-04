import React, { useEffect, useState, useCallback } from 'react';
import { usePage } from '@inertiajs/react';

/**
 * FlashToast — Automatically reads flash messages from Inertia shared props
 * and renders a dismissable, auto-expiring toast notification.
 *
 * Usage: Mount once in ResellerLayout. No props needed.
 * Controllers set messages via: redirect()->with('success', '...') etc.
 *
 * Variants: success, error, info
 * Auto-dismiss: 4 seconds
 */
export default function FlashToast() {
    const { flash } = usePage().props;
    const [toast, setToast] = useState(null);
    const [visible, setVisible] = useState(false);
    const [exiting, setExiting] = useState(false);

    const dismiss = useCallback(() => {
        setExiting(true);
        setTimeout(() => {
            setVisible(false);
            setExiting(false);
            setToast(null);
        }, 300); // match transition duration
    }, []);

    useEffect(() => {
        if (!flash) return;

        const message = flash.success || flash.error || flash.info;
        const type    = flash.success ? 'success' : flash.error ? 'error' : flash.info ? 'info' : null;

        if (!message || !type) return;

        setToast({ message, type });
        setVisible(true);
        setExiting(false);

        const timer = setTimeout(() => dismiss(), 4000);
        return () => clearTimeout(timer);
    }, [flash?.success, flash?.error, flash?.info]);

    if (!visible || !toast) return null;

    const configs = {
        success: {
            bg: 'rgba(16, 185, 129, 0.15)',
            border: 'rgba(16, 185, 129, 0.4)',
            accent: '#10b981',
            icon: 'check_circle',
            label: 'Berhasil',
        },
        error: {
            bg: 'rgba(239, 68, 68, 0.15)',
            border: 'rgba(239, 68, 68, 0.4)',
            accent: '#ef4444',
            icon: 'error',
            label: 'Gagal',
        },
        info: {
            bg: 'rgba(59, 130, 246, 0.15)',
            border: 'rgba(59, 130, 246, 0.4)',
            accent: '#3b82f6',
            icon: 'info',
            label: 'Info',
        },
    };

    const cfg = configs[toast.type] ?? configs.info;

    return (
        <div
            style={{
                position: 'fixed',
                bottom: '24px',
                right: '24px',
                zIndex: 9999,
                maxWidth: '400px',
                width: 'calc(100vw - 48px)',
                transform: exiting ? 'translateY(12px)' : 'translateY(0)',
                opacity: exiting ? 0 : 1,
                transition: 'transform 0.3s ease, opacity 0.3s ease',
                animation: !exiting ? 'flashToastSlideIn 0.3s ease' : undefined,
            }}
        >
            <style>{`
                @keyframes flashToastSlideIn {
                    from { transform: translateY(16px); opacity: 0; }
                    to   { transform: translateY(0);    opacity: 1; }
                }
            `}</style>

            <div
                style={{
                    background: cfg.bg,
                    border: `1px solid ${cfg.border}`,
                    borderLeft: `4px solid ${cfg.accent}`,
                    borderRadius: '12px',
                    backdropFilter: 'blur(12px)',
                    WebkitBackdropFilter: 'blur(12px)',
                    padding: '14px 16px',
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: '12px',
                    boxShadow: '0 8px 32px rgba(0,0,0,0.4)',
                }}
                role="alert"
                aria-live="polite"
            >
                {/* Icon */}
                <span
                    className="material-symbols-outlined"
                    style={{
                        color: cfg.accent,
                        fontSize: '20px',
                        flexShrink: 0,
                        marginTop: '1px',
                        fontVariationSettings: "'FILL' 1",
                    }}
                >
                    {cfg.icon}
                </span>

                {/* Message */}
                <div style={{ flex: 1, minWidth: 0 }}>
                    <p
                        style={{
                            margin: 0,
                            color: '#fff',
                            fontSize: '0.875rem',
                            lineHeight: '1.5',
                            wordBreak: 'break-word',
                        }}
                    >
                        {toast.message}
                    </p>
                </div>

                {/* Dismiss button */}
                <button
                    onClick={dismiss}
                    aria-label="Tutup notifikasi"
                    style={{
                        background: 'none',
                        border: 'none',
                        cursor: 'pointer',
                        padding: '0',
                        flexShrink: 0,
                        color: 'rgba(255,255,255,0.5)',
                        lineHeight: 1,
                        marginTop: '1px',
                    }}
                >
                    <span className="material-symbols-outlined" style={{ fontSize: '18px' }}>
                        close
                    </span>
                </button>
            </div>
        </div>
    );
}

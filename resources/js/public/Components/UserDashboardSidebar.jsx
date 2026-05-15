import React from 'react';
import { Link, usePage } from '@inertiajs/react';

function SidebarIcon({ type }) {
    switch (type) {
        case 'dashboard':
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1.4" stroke="currentColor" strokeWidth="1.5" />
                    <rect x="14" y="3" width="7" height="7" rx="1.4" stroke="currentColor" strokeWidth="1.5" />
                    <rect x="3" y="14" width="7" height="7" rx="1.4" stroke="currentColor" strokeWidth="1.5" />
                    <rect x="14" y="14" width="7" height="7" rx="1.4" stroke="currentColor" strokeWidth="1.5" />
                </svg>
            );
        case 'transactions':
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 7v5l3 2" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                    <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.5" />
                </svg>
            );
        case 'mutation':
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m17 8 3 3-3 3" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="M20 11H9a4 4 0 0 0-4 4v1" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="m7 16-3-3 3-3" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="M4 13h11a4 4 0 0 0 4-4V8" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            );
        case 'affiliate':
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 14a5 5 0 1 0-5-5 5 5 0 0 0 5 5Z" stroke="currentColor" strokeWidth="1.5" />
                    <path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                </svg>
            );
        case 'logout':
            return (
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M14 8V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-3" stroke="currentColor" strokeWidth="1.5" />
                    <path d="M20 12H9m8-4 4 4-4 4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            );
        default:
            return null;
    }
}

function normalizePath(url) {
    const rawUrl = typeof url === 'string' ? url : '/';
    const [pathOnly] = rawUrl.split('?');
    if (!pathOnly) {
        return '/';
    }
    return pathOnly.startsWith('/') ? pathOnly : `/${pathOnly}`;
}

function isActiveItem(key, currentPath) {
    if (/^\/id\/settings(?:\/|$)/i.test(currentPath) && key === 'dashboard') {
        return true;
    }

    const patterns = {
        dashboard: /^\/id\/dashboard\/?$/i,
        transactions: /^\/id\/dashboard\/history(?:\/|$)/i,
        mutation: /^\/id\/deposit\/history(?:\/|$)/i,
        affiliate: /^\/id\/(?:affiliate|withdrawal)(?:\/|$)/i,
    };

    return patterns[key] ? patterns[key].test(currentPath) : false;
}

export default function UserDashboardSidebar({ links = {} }) {
    const page = usePage();
    const currentPath = normalizePath(page?.url);

    const sidebarItems = [
        { key: 'dashboard', label: 'Dashboard', href: links.dashboard || '/id/dashboard', icon: 'dashboard' },
        { key: 'transactions', label: 'Riwayat Transaksi', href: links.transactions || '/id/dashboard/history', icon: 'transactions' },
        { key: 'mutation', label: 'Riwayat Deposit', href: links.mutation || '/id/deposit/history', icon: 'mutation' },
    ];

    if (typeof links.affiliate === 'string' && links.affiliate.trim() !== '') {
        sidebarItems.push({ key: 'affiliate', label: 'Afiliasi', href: links.affiliate || '/id/affiliate', icon: 'affiliate' });
    }

    return (
        <aside className="public-dashboard-side">
            <nav className="public-dashboard-side__nav" aria-label="Menu dashboard">
                {sidebarItems.map((item) => (
                    <Link
                        key={item.key}
                        href={item.href}
                        className={`public-dashboard-side__link ${isActiveItem(item.key, currentPath) ? 'is-active' : ''}`}
                    >
                        <span className="public-dashboard-side__icon"><SidebarIcon type={item.icon} /></span>
                        <span className="public-dashboard-side__label">{item.label}</span>
                    </Link>
                ))}

                <Link href="/id/logout" method="post" as="button" className="public-dashboard-side__link public-dashboard-side__link--logout">
                    <span className="public-dashboard-side__icon"><SidebarIcon type="logout" /></span>
                    <span className="public-dashboard-side__label">Keluar</span>
                </Link>
            </nav>
        </aside>
    );
}

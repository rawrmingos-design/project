import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import '../../../css/reseller-panel.css';
import DepositModal from '../Components/Reseller/DepositModal';
import NotificationDropdown from '../Components/Reseller/NotificationDropdown';
import FlashToast from '../Components/Reseller/FlashToast';

export default function ResellerLayout({ children, meta, headerTitle = "Overview" }) {
    const { url, props } = usePage();
    // authUser is injected by HandleInertiaRequests with twoFactorEnabled
    const authUser = props.authUser;
    const is2faEnabled = authUser?.twoFactorEnabled ?? false;
    const user = authUser || props.auth?.user;
    const userName = user?.name || user?.username || "Reseller";
    
    // Check if we are in live or sandbox mode globally
    const isSandboxPage = (url || '').includes('/sandbox');
    const badgeText = isSandboxPage ? 'SANDBOX' : 'LIVE';
    const badgeTone = isSandboxPage ? 'warning' : 'success';

    const [isDepositModalOpen, setIsDepositModalOpen] = useState(false);
    const [isProfileDropdownOpen, setIsProfileDropdownOpen] = useState(false);
    const [isMobileSidebarOpen, setIsMobileSidebarOpen] = useState(false);

    // Reseller Sidebar items mapping to Material Symbols
    const sidebarItems = [
        { key: 'dashboard',    label: 'Dashboard',      href: '/id/reseller',             icon: 'dashboard' },
        { key: 'credentials',  label: 'Credentials',    href: '/id/reseller/credentials', icon: 'vpn_key' },
        { key: 'orders',       label: 'Order History',  href: '/id/reseller/orders',      icon: 'receipt_long' },
        { key: 'deposits',     label: 'Riwayat Deposit',href: '/id/reseller/deposits',    icon: 'payments' },
        { key: 'callbacks',    label: 'Callback Logs',  href: '/id/reseller/callbacks',   icon: 'history_toggle_off' },
        { key: 'sandbox',      label: 'Sandbox',        href: '/id/reseller/sandbox',     icon: 'biotech' },
    ];

    const isActive = (href) => {
        if (href === '/id/reseller') {
            return url === '/id/reseller';
        }
        return url.startsWith(href);
    };

    const siteConfig = props.siteConfig || {};
    const faviconPath = siteConfig.favicon && !siteConfig.favicon.startsWith('http') && !siteConfig.favicon.startsWith('data:') 
        ? `/${siteConfig.favicon.replace(/^\//, '')}` 
        : siteConfig.favicon;

    return (
        <>
            <Head>
                {meta?.title && <title>{meta.title}</title>}
                <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
                <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
                {faviconPath && <link rel="icon" href={faviconPath} />}
                {faviconPath && <link rel="shortcut icon" href={faviconPath} />}
            </Head>

            <div className="reseller-theme">
                
                {/* Mobile Sidebar Overlay */}
                <div 
                    className={`rh-sidebar-overlay ${isMobileSidebarOpen ? 'is-open' : ''}`}
                    onClick={() => setIsMobileSidebarOpen(false)}
                />

                {/* 1. Fixed Sidebar adapted from Stitch dashboard-overview.html */}
                <aside className={`rh-sidebar ${isMobileSidebarOpen ? 'is-open' : ''}`}>
                    <div className="rh-sidebar-header">
                        <div className="rh-sidebar-logo">
                            <span className="material-symbols-outlined" style={{ fontVariationSettings: "'FILL' 1", fontSize: '20px' }}>dataset</span>
                        </div>
                        <h2 className="rh-sidebar-title">Reseller Hub</h2>
                    </div>
                    
                    <nav className="rh-sidebar-nav">
                        {sidebarItems.map((item) => (
                            <Link
                                key={item.key}
                                href={item.href}
                                className={`rh-sidebar-link ${isActive(item.href) ? 'is-active' : ''}`}
                                onClick={() => setIsMobileSidebarOpen(false)}
                            >
                                <span className="material-symbols-outlined rh-sidebar-icon" style={{ fontVariationSettings: isActive(item.href) ? "'FILL' 1" : "'FILL' 0" }}>
                                    {item.icon}
                                </span>
                                <span>{item.label}</span>
                            </Link>
                        ))}
                    </nav>
                    

                </aside>

                {/* 2. Custom Top Navigation Header adapted from Stitch */}
                <header className="rh-top-nav">
                    <div className="rh-top-nav-inner">
                        <div className="rh-nav-left">
                            {/* Hamburger Menu for Mobile */}
                            <button 
                                className="rh-hamburger"
                                onClick={() => setIsMobileSidebarOpen(true)}
                                aria-label="Open menu"
                            >
                                <span className="material-symbols-outlined">menu</span>
                            </button>

                            <div className="rh-breadcrumb hidden md:flex">
                                <Link href="/id/dashboard" style={{ textDecoration: 'none' }}>Main</Link>
                                <span className="material-symbols-outlined" style={{ fontSize: '14px' }}>chevron_right</span>
                                <span className="text-on-surface">Dashboard</span>
                            </div>
                            <h2 className="rh-nav-title">{headerTitle}</h2>
                            <span className={`rh-badge rh-badge--${badgeTone}`} style={{ fontSize: '10px' }}>
                                {badgeText}
                            </span>
                        </div>

                        <div className="rh-nav-right">
                            <div className="rh-user-actions">
                                <NotificationDropdown userId={user?.id} />

                                <button onClick={() => setIsDepositModalOpen(true)} className="rh-button rh-button--primary" style={{ padding: '8px 16px', border: 'none', cursor: 'pointer' }}>
                                    Add Credits
                                </button>

                                <div style={{ position: 'relative', zIndex: 50 }}>
                                    <div 
                                        onClick={() => setIsProfileDropdownOpen(!isProfileDropdownOpen)}
                                        style={{ width: '32px', height: '32px', borderRadius: '50%', background: 'var(--surface-container-high)', border: '1px solid var(--primary)', overflow: 'hidden', marginLeft: '8px', cursor: 'pointer' }}
                                    >
                                        <img 
                                            src={`https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=0D8ABC&color=fff`} 
                                            alt="User Avatar" 
                                            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                        />
                                    </div>

                                    {isProfileDropdownOpen && (
                                        <>
                                            <div 
                                                style={{ position: 'fixed', inset: 0, zIndex: 40 }} 
                                                onClick={() => setIsProfileDropdownOpen(false)}
                                            />
                                            <div className="rh-profile-dropdown">
                                                <div className="rh-profile-header">
                                                    <strong>{userName}</strong>
                                                    <span>Reseller</span>
                                                </div>
                                                <div className="rh-profile-menu">
                                                    <Link href="/id/reseller/settings" className="rh-profile-item">
                                                        <span className="material-symbols-outlined">settings</span>
                                                        Settings
                                                    </Link>
                                                    <Link href="/id/logout" method="post" as="button" className="rh-profile-item text-danger" style={{ width: '100%', textAlign: 'left', background: 'none', border: 'none' }}>
                                                        <span className="material-symbols-outlined">logout</span>
                                                        Logout
                                                    </Link>
                                                </div>
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                {/* 3. Main Content Area */}
                <main className="rh-main-content">
                    {/* Abstract Background Element */}
                    <div className="rh-bg-effect"></div>

                    <div className="rh-container">
                        {!is2faEnabled && (
                            <article className="rh-alert rh-alert--warning" style={{ marginBottom: '24px' }}>
                                <span className="material-symbols-outlined" style={{ flexShrink: 0, color: '#f59e0b' }}>warning</span>
                                <div>
                                    <h3 style={{ margin: '0 0 8px', color: '#f59e0b', fontSize: '1rem' }}>Security Required</h3>
                                    <p style={{ margin: 0, color: 'rgba(255,255,255,0.8)', fontSize: '14px' }}>
                                        Your account does not have Two-Factor Authentication enabled. You must activate 2FA in
                                        {' '}
                                        <a href="/id/reseller/settings" style={{ textDecoration: 'underline' }}>Settings</a>
                                        {' '}
                                        before you can use key rotation.
                                    </p>
                                </div>
                            </article>
                        )}
                        
                        {children}
                    </div>
                </main>
            </div>

            {/* Deposit Modal */}
            <DepositModal isOpen={isDepositModalOpen} onClose={() => setIsDepositModalOpen(false)} />

            {/* Flash Toast — reads flash.success / flash.error / flash.info from Inertia shared props */}
            <FlashToast />
        </>
    );
}

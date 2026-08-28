import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';

function NavIcon({ children }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {children}
        </svg>
    );
}

function SocialDrawerIcon() {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M7 17 17 7" />
            <path d="M9 7h8v8" />
            <path d="M17 13v4a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h4" />
        </svg>
    );
}

function iconFor(type) {
    switch (type) {
        case 'home':
            return (
                <NavIcon>
                    <path d="M16.5209 6.87109H8.47891C5.67599 6.87109 3.91895 8.8558 3.91895 11.6636V16.206C3.91895 19.0148 5.66724 20.9985 8.47891 20.9985H16.5199C19.3316 20.9985 21.0818 19.0148 21.0818 16.206V11.6636C21.0818 8.8558 19.3316 6.87109 16.5209 6.87109Z" />
                    <path d="M9.38477 11.1445C9.45871 11.8684 9.79338 12.5173 10.275 13.0096C10.8509 13.5748 11.639 13.928 12.5019 13.928C14.116 13.928 15.443 12.7119 15.6191 11.1445" opacity="0.45" />
                    <path d="M16.3702 6.87115C16.3702 4.7337 14.6375 3 12.4991 3C10.3616 3 8.62891 4.7337 8.62891 6.87115" opacity="0.45" />
                </NavIcon>
            );
        case 'search':
            return (
                <NavIcon>
                    <path d="M21.129 10.3728L21.1106 8.05801C21.1038 5.1517 19.2891 3.10258 16.3799 3.10939L8.05695 3.12982C5.15647 3.13664 3.34282 5.19353 3.34963 8.10082L3.36812 15.9421C3.37493 18.8485 5.18955 20.8976 8.09879 20.8908L12.0121 20.8713" />
                    <path d="M19.6179 18.5669C18.3705 19.8143 16.3477 19.8143 15.1003 18.5669C13.852 17.3195 13.852 15.2967 15.1003 14.0493C16.3477 12.8019 18.3705 12.8019 19.6179 14.0493C20.8653 15.2967 20.8653 17.3195 19.6179 18.5669Z" />
                    <path d="M19.6172 18.5669L21.3483 20.2978" />
                    <path d="M15.0331 3.11035L15.0497 9.69846L12.2523 8.78677L9.43747 9.715L9.42969 3.13468" opacity="0.45" />
                </NavIcon>
            );
        case 'search-simple':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-3.5-3.5" />
                </svg>
            );
        case 'close':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            );
        case 'menu':
            return (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <path d="M4 7h16" />
                    <path d="M4 12h16" />
                    <path d="M4 17h16" />
                </svg>
            );
        case 'list':
            return <NavIcon><path d="M8.25 6.75h12" /><path d="M8.25 12h12" /><path d="M8.25 17.25h12" /><path d="M3.75 6.75h.01" /><path d="M3.75 12h.01" /><path d="M3.75 17.25h.01" /></NavIcon>;
        case 'leaderboard':
            return (
                <NavIcon>
                    <path d="M9.67637 14.6445H5.44301C4.66172 14.6445 4.02832 15.2779 4.02832 16.0592V19.5852C4.02832 20.3636 4.66464 20.9999 5.44301 20.9999H19.5578C20.3361 20.9999 20.9725 20.3636 20.9725 19.5852V17.9419C20.9725 17.1606 20.3391 16.5272 19.5578 16.5272H15.3244" />
                    <path d="M12.8887 3.2372L13.5357 4.53124C13.5999 4.6587 13.7225 4.74627 13.8646 4.7667L15.3133 4.97492C15.5478 5.00605 15.7132 5.22108 15.6821 5.45653C15.6694 5.55188 15.6247 5.6414 15.5546 5.70853L14.5067 6.71555C14.4036 6.81284 14.3569 6.95587 14.3812 7.09597L14.6293 8.51747C14.6673 8.75488 14.5057 8.97768 14.2683 9.01563C14.1759 9.0312 14.0796 9.01466 13.9959 8.97087L12.7019 8.3005C12.5744 8.23434 12.4236 8.23434 12.2961 8.3005L11.0011 8.97185C10.789 9.08374 10.5254 9.00298 10.4135 8.79087C10.3687 8.7072 10.3531 8.61088 10.3677 8.51747L10.6158 7.09695C10.6402 6.95684 10.5935 6.81381 10.4903 6.71555L9.44148 5.7095C9.27121 5.54507 9.26635 5.27362 9.43078 5.10237C9.49791 5.03329 9.58742 4.98756 9.68277 4.97492L11.1315 4.7667C11.2736 4.74724 11.3962 4.6587 11.4604 4.53124L12.1093 3.2372C12.2203 3.02218 12.4859 2.93753 12.7009 3.04942C12.7817 3.09126 12.8468 3.15645 12.8887 3.2372Z" opacity="0.45" />
                    <path d="M15.3238 20.9994V13.0006C15.3238 12.2193 14.6904 11.5859 13.9091 11.5859H11.0905C10.3121 11.5859 9.67578 12.2223 9.67578 13.0006V20.9994" />
                </NavIcon>
            );
        case 'article':
            return (
                <NavIcon>
                    <path d="M11.3527 14.964V14.9737" />
                    <path d="M5.67464 13.5456C7.91836 13.9455 11.3861 14.7706 15.2236 16.4237C16.8349 17.1175 18.6339 15.9693 18.6359 14.215L18.6456 6.75025C18.6495 4.0609 16.8524 2.90985 15.2382 3.60068C11.3939 5.24406 7.92128 6.06137 5.67659 6.45446C4.22586 6.70841 3.50292 7.97427 3.50098 9.44738L3.5 10.5459C3.49806 12.0219 4.22099 13.2868 5.67464 13.5456Z" />
                    <path d="M18.6367 13.3437C20.2558 13.1015 21.4983 11.7062 21.5002 10.019C21.5022 8.3338 20.2636 6.93464 18.6455 6.6875" opacity="0.45" />
                    <path d="M11.3534 14.9741C11.5052 16.0823 11.799 18.2423 11.837 18.7824C11.9031 19.5685 11.4857 20.2321 10.8134 20.488C10.1313 20.7527 9.25952 20.5162 8.82362 19.9003C8.6154 19.6259 8.47334 19.2941 8.36923 18.9624C7.89538 17.4562 7.26099 15.3059 6.79688 13.7617" opacity="0.45" />
                    <path d="M7.87791 6.03516L7.87305 9.7588" opacity="0.45" />
                </NavIcon>
            );
        case 'megaphone':
            return iconFor('article');
        case 'calculator':
            return (
                <NavIcon viewBox="0 0 24 25">
                    <path d="M16.9668 20.4388V20.0928C16.9668 18.8498 15.9588 17.8418 14.7148 17.8418H8.84482C7.60082 17.8418 6.5918 18.8498 6.5918 20.0928V20.4388" />
                    <path d="M4.64062 20.4395H18.9186M16.9666 20.4395H6.59161H16.9666Z" />
                    <path fillRule="evenodd" clipRule="evenodd" d="M3 14.2511L5.66699 8.78711L8.36499 14.2511C6.79899 16.4041 4.016 16.2261 3 14.2511Z" />
                    <path d="M8.00015 13.5137H3.36914" />
                    <path fillRule="evenodd" clipRule="evenodd" d="M15.6348 12.1925L18.3018 6.72852L20.9998 12.1925C19.4338 14.3445 16.6508 14.1675 15.6348 12.1925Z" />
                    <path d="M20.6339 11.4551H16.002" />
                    <path fillRule="evenodd" clipRule="evenodd" d="M13.1943 5.54684C13.1943 4.76384 12.5613 4.13086 11.7783 4.13086C10.9963 4.13086 10.3633 4.76384 10.3633 5.54684C10.3633 6.32784 10.9963 6.96185 11.7783 6.96185C12.5613 6.96185 13.1943 6.32784 13.1943 5.54684Z" />
                    <path d="M10.3871 5.77795L3.28906 6.96097M20.2711 4.12695L13.1731 5.30997L20.2711 4.12695Z" />
                    <path d="M11.7793 17.8399V6.96289" />
                </NavIcon>
            );
        case 'dashboard':
            return <NavIcon><rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" /></NavIcon>;
        case 'wallet':
            return <NavIcon><path d="M4 8.5A2.5 2.5 0 0 1 6.5 6H18a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6.5A2.5 2.5 0 0 1 4 15.5v-7Z" /><path d="M15.5 12h.01" /></NavIcon>;
        case 'logout':
            return <NavIcon><path d="M14.75 12H2.75" /><path d="m11.82 9.08 2.93 2.92-2.93 2.92" /><path d="M7.25 7.5c.33-3.58 1.67-4.88 7-4.88 7.1 0 7.1 2.31 7.1 9.25s0 9.25-7.1 9.25c-5.33 0-6.67-1.3-7-4.88" opacity="0.45" /></NavIcon>;
        case 'user':
            return <NavIcon><path d="M17.98 18.73A7.49 7.49 0 0 0 12 15.75a7.49 7.49 0 0 0-5.98 2.98" /><path d="M18 18.73A9 9 0 1 0 6 18.73 8.97 8.97 0 0 0 12 21a8.97 8.97 0 0 0 6-2.27Z" opacity="0.45" /><path d="M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></NavIcon>;
        case 'login':
            return (
                <NavIcon>
                    <path d="M14.791 12.1207H2.75" />
                    <path d="M11.8643 9.20471L14.7923 12.1207L11.8643 15.0367" />
                    <path d="M7.25879 7.62988C7.58879 4.04988 8.92879 2.74988 14.2588 2.74988C21.3598 2.74988 21.3598 5.05988 21.3598 11.9999C21.3598 18.9399 21.3598 21.2499 14.2588 21.2499C8.92879 21.2499 7.58879 19.9499 7.25879 16.3699" opacity="0.45" />
                </NavIcon>
            );
        case 'register':
            return <NavIcon><path d="M7.88 13.2c-3.84 0-7.13.58-7.13 2.91 0 2.33 3.26 2.93 7.13 2.93 3.84 0 7.12-.58 7.12-2.9 0-2.33-3.26-2.94-7.12-2.94Z" /><path d="M7.88 9.89a4.57 4.57 0 1 0 0-9.14 4.57 4.57 0 0 0 0 9.14Z" opacity="0.45" /><path d="M17.2 6.67v4" /><path d="M19.25 8.67h-4.1" /></NavIcon>;
        case 'chevron':
            return <NavIcon><path d="m8 10 4 4 4-4" /></NavIcon>;
        default:
            return null;
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
}

function normalizePath(url) {
    const [path] = String(url || '').split(/[?#]/);
    const trimmed = path.replace(/\/+$/, '');
    return trimmed || '/';
}

export default function Navbar() {
    const page = usePage();
    const { siteConfig, authUser, theme } = page.props;
    const safeSiteConfig = siteConfig || {};
    const currentUrl = page.url || '';
    const activeThemeKey = theme?.key || 'default';
    const isBangjeffTheme = activeThemeKey === 'bangjeff';
    const isStorefrontModernTheme = isBangjeffTheme || activeThemeKey === 'istanatopup';
    const [menuOpen, setMenuOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [legacyResultsHtml, setLegacyResultsHtml] = useState('');
    const [searchItems, setSearchItems] = useState([]);
    const [searchLoading, setSearchLoading] = useState(false);
    const [searchRequested, setSearchRequested] = useState(false);
    const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
    const [accountMenuOpen, setAccountMenuOpen] = useState(false);
    const accountMenuRef = useRef(null);
    const calculatorMenuItems = useMemo(() => ([
        {
            label: 'Win Rate',
            href: '/id/calculator/winrate',
            description: 'Digunakan untuk menghitung total jumlah match yang harus ditempuh untuk mencapai target win rate yang diinginkan.',
        },
        {
            label: 'Magic Wheel',
            href: '/id/calculator/magic-wheel',
            description: 'Digunakan untuk mengetahui total maksimal diamond yang dibutuhkan untuk mendapatkan skin Legends.',
        },
        {
            label: 'Zodiac',
            href: '/id/calculator/zodiac',
            description: 'Digunakan untuk mengetahui total diamond maksimal yang dibutuhkan untuk mendapatkan skin Zodiacs.',
        },
    ]), []);

    const handleLogout = () => {
        setMenuOpen(false);
        setAccountMenuOpen(false);

        router.post('/id/logout', {}, {
            preserveScroll: true,
        });
    };

    const mainLinks = useMemo(() => {
        if (isStorefrontModernTheme) {
            return [
                { label: 'Topup', href: '/id', icon: 'home' },
                { label: 'Cek Transaksi', href: '/id/invoices', icon: 'search' },
                { label: 'Leaderboard', href: '/id/leaderboard', icon: 'leaderboard' },
                { label: 'Artikel', href: '/id/artikel', icon: 'article' },
                { label: 'Kalkulator', href: '/id/calculator/winrate', icon: 'calculator', children: calculatorMenuItems },
            ];
        }

        return [
            { label: 'Beranda', href: '/id', icon: 'home' },
            { label: 'Cek Transaksi', href: '/id/invoices', icon: 'search' },
            { label: 'Daftar Harga', href: '/id/price-list', icon: 'list' },
            { label: 'Leaderboard', href: '/id/leaderboard', icon: 'leaderboard' },
            { label: 'Kalkulator', href: '/id/calculator/winrate', icon: 'calculator', children: calculatorMenuItems },
        ];
    }, [isStorefrontModernTheme, calculatorMenuItems]);

    const accountLinks = authUser ? [
        { label: 'Dashboard', href: '/id/dashboard', icon: 'dashboard' },
        { label: 'Riwayat Transaksi', href: '/id/dashboard/history', icon: 'list' },
        { label: 'Riwayat Deposit', href: '/id/deposit/history', icon: 'wallet' },
        ...(authUser?.canShowAffiliate ? [{ label: 'Afiliasi', href: '/id/affiliate', icon: 'user' }] : []),
        { label: 'Pengaturan', href: '/id/settings', icon: 'user' },
    ] : [];
    const drawerSocialLinks = [
        { key: 'whatsapp', label: 'WhatsApp', href: safeSiteConfig?.socials?.whatsapp },
        { key: 'instagram', label: 'Instagram', href: safeSiteConfig?.socials?.instagram },
        { key: 'tiktok', label: 'TikTok', href: safeSiteConfig?.socials?.tiktok },
        { key: 'facebook', label: 'Facebook', href: safeSiteConfig?.socials?.facebook },
        { key: 'youtube', label: 'YouTube', href: safeSiteConfig?.socials?.youtube },
    ].filter((item) => item.href);

    const isActive = (href) => {
        const currentPath = normalizePath(currentUrl);
        const targetPath = normalizePath(href);

        if (targetPath === '/id') {
            return currentPath === '/id' || currentPath === '/';
        }

        if (targetPath.startsWith('/id/calculator/')) {
            return currentPath.startsWith('/id/calculator/');
        }

        return currentPath === targetPath || currentPath.startsWith(`${targetPath}/`);
    };

    useEffect(() => {
        const handleNavigationStart = () => {
            setMenuOpen(false);
            setMobileSearchOpen(false);
        };

        router.on('navigate', handleNavigationStart);

        return () => {
            router.off('navigate', handleNavigationStart);
        };
    }, []);

    useEffect(() => {
        if (!menuOpen) {
            return undefined;
        }

        document.body.classList.add('public-body-lock');
        return () => document.body.classList.remove('public-body-lock');
    }, [menuOpen]);

    useEffect(() => {
        const trimmedQuery = query.trim();

        if (trimmedQuery.length < 2) {
            setLegacyResultsHtml('');
            setSearchItems([]);
            setSearchLoading(false);
            setSearchRequested(false);
            return undefined;
        }

        const controller = new AbortController();
        const timeout = window.setTimeout(async () => {
            setSearchLoading(true);
            setSearchRequested(false);

            try {
                if (isStorefrontModernTheme) {
                    const response = await fetch(`/id/search/products?q=${encodeURIComponent(trimmedQuery)}`, {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        setSearchItems([]);
                        setSearchRequested(true);
                        return;
                    }

                    const payload = await response.json();
                    setSearchItems(Array.isArray(payload?.items) ? payload.items : []);
                    setSearchRequested(true);
                    return;
                }

                const response = await fetch('/id/cari/index', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        Accept: 'text/html',
                    },
                    body: JSON.stringify({ data: trimmedQuery }),
                    signal: controller.signal,
                });

                if (!response.ok) {
                    setLegacyResultsHtml('');
                    return;
                }

                setLegacyResultsHtml(await response.text());
            } catch (error) {
                if (error.name !== 'AbortError') {
                    if (isStorefrontModernTheme) {
                        setSearchItems([]);
                        setSearchRequested(true);
                    } else {
                        setLegacyResultsHtml('');
                    }
                }
            } finally {
                setSearchLoading(false);
            }
        }, 220);

        return () => {
            controller.abort();
            window.clearTimeout(timeout);
        };
    }, [isStorefrontModernTheme, query]);

    const showBangjeffSearchDropdown = isStorefrontModernTheme && query.trim().length >= 2 && (searchLoading || searchRequested);
    const showLegacySearchDropdown = !isStorefrontModernTheme && Boolean(legacyResultsHtml);
    const hasSearchQuery = query.trim().length > 0;

    const clearSearch = () => {
        setQuery('');
        setLegacyResultsHtml('');
        setSearchItems([]);
        setSearchLoading(false);
        setSearchRequested(false);
    };

    useEffect(() => {
        if (!accountMenuOpen) {
            return undefined;
        }

        const handlePointerDown = (event) => {
            if (!accountMenuRef.current) {
                return;
            }

            if (!accountMenuRef.current.contains(event.target)) {
                setAccountMenuOpen(false);
            }
        };

        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setAccountMenuOpen(false);
            }
        };

        document.addEventListener('mousedown', handlePointerDown);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handlePointerDown);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [accountMenuOpen]);

    const displayName = authUser?.name || authUser?.username || 'Member';
    const displayInitial = String(displayName).trim().charAt(0).toUpperCase() || 'M';
    const pointBalance = Number(authUser?.pointBalance || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fallbackAvatar = `https://ui-avatars.com/api/?color=FFFFFF&background=50a7ff&name=${encodeURIComponent(displayName)}`;
    const authAvatar = typeof authUser?.avatar === 'string' ? authUser.avatar.trim() : '';
    const [accountAvatarSrc, setAccountAvatarSrc] = useState(authAvatar || fallbackAvatar);

    useEffect(() => {
        setAccountAvatarSrc(authAvatar || fallbackAvatar);
    }, [authAvatar, fallbackAvatar]);

    return (
        <>
            <header className="public-navbar public-navbar--storefront">
                <div className="public-navbar__top">
                    <div className="public-navbar__inner public-navbar__inner--top">
                        <Link href="/id" className="public-brand public-brand--storefront">
                            <img src={safeSiteConfig.logoHeader || '/assets/logo/01KGSN7TWDAQXP947X0GH07TDE.gif'} alt={safeSiteConfig.name || 'Logo'} className="public-brand__logo" />
                        </Link>

                        <div className="public-search public-search--desktop">
                            <input
                                type="text"
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder="Cari Game atau Voucher"
                                aria-label="Cari produk"
                            />
                            <span className="public-search__icon">{iconFor(activeThemeKey === 'bangjeff' ? 'search-simple' : 'search')}</span>
                            <button
                                type="button"
                                className={`public-search__clear ${hasSearchQuery ? 'is-visible' : ''}`}
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={clearSearch}
                                aria-label="Hapus pencarian"
                                tabIndex={hasSearchQuery ? 0 : -1}
                            >
                                {iconFor('close')}
                            </button>
                            {showLegacySearchDropdown ? (
                                <ul className="public-search__results" dangerouslySetInnerHTML={{ __html: legacyResultsHtml }} />
                            ) : null}
                            {showBangjeffSearchDropdown ? (
                                <div className="public-search__results public-search__results--bangjeff" role="listbox" aria-label="Hasil pencarian produk">
                                    {searchLoading ? (
                                        <div className="public-search__status">Mencari produk...</div>
                                    ) : null}

                                    {!searchLoading && searchItems.length ? (
                                        <div className="public-search__list">
                                            {searchItems.map((item) => (
                                                <a key={item.slug} href={`/id/${item.slug}`} className="public-search-item" role="option" aria-selected="false">
                                                    <img src={item.thumbnail} alt={item.name} loading="lazy" decoding="async" />
                                                    <span className="public-search-item__copy">
                                                        <strong>{item.name}</strong>
                                                        <small>{item.subtitle}</small>
                                                    </span>
                                                </a>
                                            ))}
                                        </div>
                                    ) : null}

                                    {!searchLoading && searchRequested && !searchItems.length ? (
                                        <div className="public-search__empty">
                                            <strong>Produk yang dicari tidak ditemukan</strong>
                                            <span>Pastikan nama produk yang dicari sudah benar</span>
                                        </div>
                                    ) : null}
                                </div>
                            ) : null}
                        </div>

                        <div className="public-navbar__top-actions">
                            <button
                                type="button"
                                className="public-mobile-icon"
                                onClick={() => setMobileSearchOpen((current) => !current)}
                                aria-label="Buka pencarian"
                            >
                                {iconFor(isStorefrontModernTheme ? 'search-simple' : 'search')}
                            </button>

                            <button type="button" className="public-locale-pill" aria-label="Bahasa Indonesia">
                                <span className="public-locale-pill__flag" />
                                {isStorefrontModernTheme ? <span className="public-locale-pill__text">ID / IDR</span> : null}
                            </button>

                            {isBangjeffTheme && authUser ? (
                                <div ref={accountMenuRef} className={`public-navbar__account-menu ${accountMenuOpen ? 'is-open' : ''}`}>
                                    <button
                                        type="button"
                                        className="public-navbar__account-trigger"
                                        aria-haspopup="menu"
                                        aria-expanded={accountMenuOpen}
                                        onClick={() => setAccountMenuOpen((current) => !current)}
                                    >
                                        <span className="public-navbar__avatar">
                                            {accountAvatarSrc ? (
                                                <img
                                                    src={accountAvatarSrc}
                                                    alt={displayName}
                                                    className="public-navbar__avatar-image"
                                                    onError={() => setAccountAvatarSrc(fallbackAvatar)}
                                                />
                                            ) : (
                                                <span className="public-navbar__avatar-fallback">{displayInitial}</span>
                                            )}
                                        </span>
                                    </button>
                                    {accountMenuOpen ? (
                                        <div className="public-navbar__account-dropdown" role="menu">
                                            <div className="public-navbar__account-copy">
                                                <small>Telah masuk sebagai</small>
                                                <strong>{displayName}</strong>
                                            </div>
                                            <div className="public-navbar__account-balance">
                                                <span className="public-navbar__account-balance-dot" aria-hidden="true" />
                                                <strong>{pointBalance}</strong>
                                                <small>Koin</small>
                                            </div>
                                            {accountLinks.map((item) => (
                                                <Link
                                                    key={item.href}
                                                    href={item.href}
                                                    className={`public-navbar__account-link ${isActive(item.href) ? 'is-active' : ''}`}
                                                    role="menuitem"
                                                    onClick={() => setAccountMenuOpen(false)}
                                                >
                                                    <span className="public-navbar__account-link-icon">{iconFor(item.icon)}</span>
                                                    <span>{item.label}</span>
                                                </Link>
                                            ))}
                                            <button type="button" className="public-navbar__account-link public-navbar__account-link--danger" role="menuitem" onClick={handleLogout}>
                                                <span className="public-navbar__account-link-icon">{iconFor('logout')}</span>
                                                <span>Keluar</span>
                                            </button>
                                        </div>
                                    ) : null}
                                </div>
                            ) : null}

                            <button
                                type="button"
                                className={`public-mobile-icon ${isBangjeffTheme ? '' : 'public-mobile-icon--accent'}`.trim()}
                                onClick={() => setMenuOpen(true)}
                                aria-label="Buka menu"
                            >
                                {isBangjeffTheme ? (
                                    iconFor('menu')
                                ) : (
                                    <>
                                        <span />
                                        <span />
                                        <span />
                                    </>
                                )}
                            </button>
                        </div>
                    </div>

                    {mobileSearchOpen ? (
                        <div className="public-navbar__inner public-navbar__mobile-search">
                            <div className="public-search public-search--mobile">
                                <input
                                    type="text"
                                    value={query}
                                    onChange={(event) => setQuery(event.target.value)}
                                    placeholder="Cari Game atau Voucher"
                                    aria-label="Cari produk"
                                />
                                <span className="public-search__icon">{iconFor(isStorefrontModernTheme ? 'search-simple' : 'search')}</span>
                                <button
                                    type="button"
                                    className={`public-search__clear ${hasSearchQuery ? 'is-visible' : ''}`}
                                    onMouseDown={(event) => event.preventDefault()}
                                    onClick={clearSearch}
                                    aria-label="Hapus pencarian"
                                    tabIndex={hasSearchQuery ? 0 : -1}
                                >
                                    {iconFor('close')}
                                </button>
                                {showLegacySearchDropdown ? (
                                    <ul className="public-search__results" dangerouslySetInnerHTML={{ __html: legacyResultsHtml }} />
                                ) : null}
                                {showBangjeffSearchDropdown ? (
                                    <div className="public-search__results public-search__results--bangjeff" role="listbox" aria-label="Hasil pencarian produk">
                                        {searchLoading ? (
                                            <div className="public-search__status">Mencari produk...</div>
                                        ) : null}

                                        {!searchLoading && searchItems.length ? (
                                            <div className="public-search__list">
                                                {searchItems.map((item) => (
                                                    <a key={item.slug} href={`/id/${item.slug}`} className="public-search-item" role="option" aria-selected="false">
                                                        <img src={item.thumbnail} alt={item.name} loading="lazy" decoding="async" />
                                                        <span className="public-search-item__copy">
                                                            <strong>{item.name}</strong>
                                                            <small>{item.subtitle}</small>
                                                        </span>
                                                    </a>
                                                ))}
                                            </div>
                                        ) : null}

                                        {!searchLoading && searchRequested && !searchItems.length ? (
                                            <div className="public-search__empty">
                                                <strong>Produk yang dicari tidak ditemukan</strong>
                                                <span>Pastikan nama produk yang dicari sudah benar</span>
                                            </div>
                                        ) : null}
                                    </div>
                                ) : null}
                            </div>
                        </div>
                    ) : null}
                </div>

                <div className="public-navbar__bottom">
                    <div className="public-navbar__inner public-navbar__inner--bottom">
                        <nav className="public-navbar__links public-navbar__links--storefront">
                            {mainLinks.map((item) => {
                                const hasChildren = Array.isArray(item.children) && item.children.length > 0;
                                const parentActive = isActive(item.href) || (hasChildren && item.children.some((child) => isActive(child.href)));

                                if (hasChildren && isBangjeffTheme) {
                                    return (
                                        <div key={item.label} className={`public-navbar__item public-navbar__item--has-menu ${parentActive ? 'is-active' : ''}`}>
                                            <Link href={item.href} className={`public-navbar__link public-navbar__link--has-menu ${parentActive ? 'is-active' : ''}`}>
                                                <span className="public-navbar__link-icon">{iconFor(item.icon)}</span>
                                                <span>{item.label}</span>
                                            </Link>

                                            <div className="public-navbar__submenu" role="menu" aria-label="Menu kalkulator">
                                                {item.children.map((child) => (
                                                    <Link
                                                        key={child.href}
                                                        href={child.href}
                                                        role="menuitem"
                                                        className={`public-navbar__submenu-link ${isActive(child.href) ? 'is-active' : ''}`}
                                                    >
                                                        <span className="public-navbar__submenu-icon">{iconFor('calculator')}</span>
                                                        <span className="public-navbar__submenu-copy">
                                                            <strong>{child.label}</strong>
                                                            <small>{child.description}</small>
                                                        </span>
                                                    </Link>
                                                ))}
                                            </div>
                                        </div>
                                    );
                                }

                                return (
                                    <Link key={item.label} href={item.href} className={`public-navbar__link ${parentActive ? 'is-active' : ''}`}>
                                        <span className="public-navbar__link-icon">{iconFor(item.icon)}</span>
                                        <span>{item.label}</span>
                                    </Link>
                                );
                            })}
                        </nav>

                        <div className="public-navbar__bottom-actions">
                            {authUser ? (
                                !isBangjeffTheme ? (
                                    <Link href="/id/dashboard" className="public-navbar__compact-account public-navbar__compact-account--storefront">
                                        <span className="public-navbar__compact-account-main">
                                            <span className="public-account-pill__icon">{iconFor('user')}</span>
                                            <span>{authUser.username}</span>
                                            <span className="public-navbar__compact-chevron">{iconFor('chevron')}</span>
                                        </span>
                                        <small>{formatCurrency(authUser.balance)}</small>
                                    </Link>
                                ) : null
                            ) : (
                                <>
                                    <a href="/id/sign-in" className="public-navbar__text-link public-navbar__text-link--storefront">
                                        <span className="public-navbar__text-link-icon">{iconFor('login')}</span>
                                        <span>Masuk</span>
                                    </a>
                                    <a href="/id/sign-up" className="public-navbar__text-link public-navbar__text-link--storefront">
                                        <span className="public-navbar__text-link-icon">{iconFor('register')}</span>
                                        <span>Daftar</span>
                                    </a>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </header>

            <div className={`public-drawer-backdrop ${menuOpen ? 'is-open' : ''}`} onClick={() => setMenuOpen(false)} />
            <aside className={`public-drawer ${menuOpen ? 'is-open' : ''}`} aria-hidden={!menuOpen}>
                <div className="public-drawer__header">
                    <Link href="/id" className="public-brand public-brand--storefront" onClick={() => setMenuOpen(false)}>
                        <img src={safeSiteConfig.logoHeader || '/assets/logo/01KGSN7TWDAQXP947X0GH07TDE.gif'} alt={safeSiteConfig.name || 'Logo'} className="public-brand__logo" />
                    </Link>
                    <button type="button" className="public-drawer__close" onClick={() => setMenuOpen(false)} aria-label="Tutup menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div className="public-drawer__section public-drawer__section--menu">
                    {mainLinks.map((item) => {
                        const hasChildren = Array.isArray(item.children) && item.children.length > 0;
                        const parentActive = isActive(item.href) || (hasChildren && item.children.some((child) => isActive(child.href)));

                        if (hasChildren) {
                            return (
                                <div key={item.label} className="public-drawer__submenu-group">
                                    <Link href={item.href} className={`public-drawer__link ${parentActive ? 'is-active' : ''}`} onClick={() => setMenuOpen(false)}>
                                        <span>{item.label}</span>
                                        <span className="public-drawer__icon">{iconFor(item.icon)}</span>
                                    </Link>
                                    <div className="public-drawer__submenu-items">
                                        {item.children.map((child) => (
                                            <Link
                                                key={child.href}
                                                href={child.href}
                                                className={`public-drawer__submenu-link ${isActive(child.href) ? 'is-active' : ''}`}
                                                onClick={() => setMenuOpen(false)}
                                            >
                                                <span>{child.label}</span>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            );
                        }

                        return (
                            <Link key={item.label} href={item.href} className={`public-drawer__link ${parentActive ? 'is-active' : ''}`} onClick={() => setMenuOpen(false)}>
                                <span>{item.label}</span>
                                <span className="public-drawer__icon">{iconFor(item.icon)}</span>
                            </Link>
                        );
                    })}
                </div>

                {authUser ? (
                    <div className="public-drawer__section public-drawer__section--account">
                        <div className="public-drawer__account-card">
                            <span className="public-drawer__account-avatar">
                                {accountAvatarSrc ? (
                                    <img
                                        src={accountAvatarSrc}
                                        alt={displayName}
                                        className="public-drawer__account-avatar-image"
                                        onError={() => setAccountAvatarSrc(fallbackAvatar)}
                                    />
                                ) : (
                                    <span className="public-drawer__account-avatar-fallback">{displayInitial}</span>
                                )}
                            </span>
                            <div className="public-drawer__account-copy">
                                <strong>{displayName}</strong>
                                <small>{pointBalance} Koin</small>
                            </div>
                        </div>
                        {accountLinks.map((item) => (
                            <Link key={item.label} href={item.href} className={`public-drawer__link ${isActive(item.href) ? 'is-active' : ''}`} onClick={() => setMenuOpen(false)}>
                                <span>{item.label}</span>
                                <span className="public-drawer__icon">{iconFor(item.icon)}</span>
                            </Link>
                        ))}
                        <button type="button" className="public-drawer__link public-drawer__link--danger" onClick={handleLogout}>
                            <span>Keluar</span>
                            <span className="public-drawer__icon">{iconFor('logout')}</span>
                        </button>
                    </div>
                ) : (
                    <div className="public-drawer__section public-drawer__section--account">
                        <a href="/id/sign-in" className="public-drawer__link" onClick={() => setMenuOpen(false)}>
                            <span>Masuk</span>
                            <span className="public-drawer__icon">{iconFor('login')}</span>
                        </a>
                        <a href="/id/sign-up" className="public-drawer__link" onClick={() => setMenuOpen(false)}>
                            <span>Daftar</span>
                            <span className="public-drawer__icon">{iconFor('register')}</span>
                        </a>
                    </div>
                )}

                {drawerSocialLinks.length ? (
                    <div className="public-drawer__section public-drawer__section--social">
                        {drawerSocialLinks.map((item) => (
                            <a key={item.key} href={item.href} className="public-drawer__link" target="_blank" rel="noreferrer" onClick={() => setMenuOpen(false)}>
                                <span>{item.label}</span>
                                <span className="public-drawer__icon"><SocialDrawerIcon /></span>
                            </a>
                        ))}
                    </div>
                ) : null}
            </aside>
        </>
    );
}

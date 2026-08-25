import React, { useLayoutEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';

function FooterSeoDescription({ html }) {
    const contentRef = useRef(null);
    const [expanded, setExpanded] = useState(false);
    const [collapsible, setCollapsible] = useState(false);
    const collapsedHeight = 132;

    useLayoutEffect(() => {
        const content = contentRef.current;

        if (!content) {
            return undefined;
        }

        const updateCollapsible = () => {
            setCollapsible(content.scrollHeight > collapsedHeight);
        };

        setExpanded(false);
        updateCollapsible();

        window.addEventListener('resize', updateCollapsible);
        window.setTimeout(updateCollapsible, 250);

        return () => window.removeEventListener('resize', updateCollapsible);
    }, [html]);

    if (!html) {
        return null;
    }

    return (
        <section className={`public-footer__seo ${collapsible ? 'is-collapsible' : ''} ${expanded ? 'is-expanded' : ''}`.trim()}>
            <div
                ref={contentRef}
                className="public-footer__seo-content"
                dangerouslySetInnerHTML={{ __html: html }}
            />
            {collapsible ? (
                <button
                    type="button"
                    className="public-footer__seo-toggle"
                    aria-expanded={expanded}
                    onClick={() => setExpanded((value) => !value)}
                >
                    {expanded ? 'Tutup' : 'Baca selengkapnya'}
                </button>
            ) : null}
        </section>
    );
}

export default function Footer() {
    const { siteConfig, theme } = usePage().props;
    const year = new Date().getFullYear();
    const [hasFooterVisual, setHasFooterVisual] = useState(Boolean(siteConfig.logoFooter));
    const isBangjeff = theme?.key === 'bangjeff';
    const isIstanaTopup = theme?.key === 'istanatopup';
    const useImageFooterVisual = hasFooterVisual;
    const footerBrandLogo = siteConfig.assetAudit?.logoHeader?.path || siteConfig.logoHeader || siteConfig.favicon || '/assets/logo/favicon.webp';

    const socialLinks = [
        {
            key: 'whatsapp',
            label: 'WhatsApp',
            href: siteConfig.socials?.whatsapp,
            icon: (
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 4.2a7.8 7.8 0 0 0-6.7 11.8L4 20l4.1-1.1A7.8 7.8 0 1 0 12 4.2Z" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="M9.5 9.2c.2-.4.4-.4.6-.4h.5c.1 0 .3 0 .4.3l.8 1.8c.1.2.1.4 0 .5l-.3.4c-.1.1-.2.3-.1.4.3.6.8 1.1 1.4 1.5.1.1.3.1.4 0l.5-.4c.1-.1.3-.1.5 0l1.6.7c.3.1.3.3.3.4v.5c0 .2 0 .5-.4.7-.4.2-1 .3-1.4.2-1.2-.3-2.4-1.1-3.5-2.3-1.1-1.2-1.8-2.4-2-3.5-.1-.5 0-1 .2-1.4Z" fill="currentColor" />
                </svg>
            ),
        },
        {
            key: 'instagram',
            label: 'Instagram',
            href: siteConfig.socials?.instagram,
            icon: (
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3.5" y="3.5" width="17" height="17" rx="4.5" fill="none" stroke="currentColor" strokeWidth="1.8" />
                    <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" strokeWidth="1.8" />
                    <circle cx="17.1" cy="6.9" r="1.1" fill="currentColor" />
                </svg>
            ),
        },
        {
            key: 'tiktok',
            label: 'TikTok',
            href: siteConfig.socials?.tiktok,
            icon: (
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M13.5 4v9.1a3.6 3.6 0 1 1-3-3.55" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="M13.5 4c.8 2 2.2 3.2 4.5 3.5" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            ),
        },
        {
            key: 'facebook',
            label: 'Facebook',
            href: siteConfig.socials?.facebook,
            icon: (
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M14 8h2.6V4.8H14c-2.5 0-4 1.5-4 4.2V11H7.5v3.4H10V20h3.3v-5.6h2.8L16.6 11h-3.3V9.4c0-.9.4-1.4 1.4-1.4Z" fill="currentColor" />
                </svg>
            ),
        },
        {
            key: 'youtube',
            label: 'YouTube',
            href: siteConfig.socials?.youtube,
            icon: (
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20.4 7.2a2.8 2.8 0 0 0-2-2C16.7 4.7 12 4.7 12 4.7s-4.7 0-6.4.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 3.1 12c0 1.6.2 3.2.5 4.8a2.8 2.8 0 0 0 2 2c1.7.5 6.4.5 6.4.5s4.7 0 6.4-.5a2.8 2.8 0 0 0 2-2c.4-1.6.5-3.2.5-4.8 0-1.6-.1-3.2-.5-4.8Z" fill="none" stroke="currentColor" strokeWidth="1.6" />
                    <path d="m10 9 5 3-5 3V9Z" fill="currentColor" />
                </svg>
            ),
        },
    ].filter((item) => item.href);

    const footerColumns = Array.isArray(siteConfig.footerColumns) ? siteConfig.footerColumns : [];

    return (
        <>
            <div
                className={`public-footer__visual ${isIstanaTopup ? 'public-footer__visual--reference' : useImageFooterVisual ? 'public-footer__visual--image' : 'public-footer__visual--wave'}`}
                aria-hidden="true"
            >
                {isIstanaTopup ? (
                    <svg className="public-footer__wave-svg" viewBox="0 0 1440 70" preserveAspectRatio="none">
                        <path
                            fill="currentColor"
                            d="M0,70 L0,40 C240,0 480,0 720,35 C960,70 1200,70 1440,30 L1440,70 Z"
                        />
                    </svg>
                ) : useImageFooterVisual ? (
                    <img
                        src={siteConfig.logoFooter}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        onError={() => setHasFooterVisual(false)}
                    />
                ) : (
                    <>
                        <div className="public-footer__wave public-footer__wave--back">
                            <svg viewBox="0 0 1440 220" preserveAspectRatio="none">
                                <path
                                    fill="#343438"
                                    d="M0,96L34.3,112C68.6,128,137,160,206,154.7C274.3,149,343,107,411,90.7C480,75,549,85,617,112C685.7,139,754,181,823,181.3C891.4,181,960,139,1029,128C1097.1,117,1166,139,1234,133.3C1302.9,128,1371,96,1406,80L1440,64L1440,221L1406,221C1371,221,1303,221,1234,221C1166,221,1097,221,1029,221C960,221,891,221,823,221C754,221,686,221,617,221C549,221,480,221,411,221C343,221,274,221,206,221C137,221,69,221,34,221L0,221Z"
                                />
                            </svg>
                        </div>
                        <div className="public-footer__wave public-footer__wave--front">
                            <svg viewBox="0 0 1440 220" preserveAspectRatio="none">
                                <path
                                    fill="#28282a"
                                    d="M0,136L34.3,152C68.6,168,137,200,206,194.7C274.3,189,343,147,411,136C480,125,549,147,617,170.7C685.7,195,754,221,823,216C891.4,211,960,173,1029,168C1097.1,163,1166,189,1234,184C1302.9,179,1371,141,1406,122L1440,104L1440,221L1406,221C1371,221,1303,221,1234,221C1166,221,1097,221,1029,221C960,221,891,221,823,221C754,221,686,221,617,221C549,221,480,221,411,221C343,221,274,221,206,221C137,221,69,221,34,221L0,221Z"
                                />
                            </svg>
                        </div>
                    </>
                )}
            </div>

            <footer className="public-footer public-footer--storefront">
                <div className="public-footer__inner public-footer__inner--storefront">
                    {!isIstanaTopup ? <FooterSeoDescription html={siteConfig.footerDescriptionHtml} /> : null}

                    <div className={`public-footer__layout ${isBangjeff || isIstanaTopup ? 'public-footer__layout--bangjeff' : ''}`}>
                        {isBangjeff || isIstanaTopup ? (
                            <div className={`public-footer__intro public-footer__intro--bangjeff ${isIstanaTopup ? 'public-footer__intro--istanatopup' : ''}`}>
                                <div className="public-footer__brand-block">
                                    <img src={footerBrandLogo} alt={`${siteConfig.name} logo`} className="public-footer__brand-logo" />
                                    {isIstanaTopup ? (
                                        <p className="public-footer__brand-description">
                                            No #1 supplier top up game &amp; voucher terlaris, murah, aman legal 100% buka 24 jam dengan channel pembayaran terlengkap Indonesia.
                                        </p>
                                    ) : null}
                                </div>

                                {socialLinks.length ? (
                                    <div className="public-footer__social-row" aria-label="Social links">
                                        {socialLinks.map((item) => (
                                            <a key={item.key} href={item.href} target="_blank" rel="noreferrer" className={`public-footer__social-link public-footer__social-link--${item.key}`} aria-label={item.label}>
                                                {item.icon}
                                            </a>
                                        ))}
                                    </div>
                                ) : null}

                                {isIstanaTopup ? (
                                    <div className="public-footer__app">
                                        <h5>Download {(siteConfig.appName || siteConfig.name || 'Game Top-Up').toUpperCase()} Mobile App on:</h5>
                                        <button
                                            type="button"
                                            className="public-footer__app-badge"
                                            onClick={async () => {
                                                const promptEvent = window.__istPwaInstallPrompt;
                                                if (promptEvent) {
                                                    window.__istPwaInstallPrompt = null;
                                                    await promptEvent.prompt();
                                                } else {
                                                    window.location.href = '/site.webmanifest';
                                                }
                                            }}
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                                <rect x="6" y="2" width="12" height="20" rx="2.5" />
                                                <path d="M11 18.5h2" />
                                            </svg>
                                            <span>
                                                <small>PWA siap install</small>
                                                Install Aplikasi Web
                                            </span>
                                        </button>
                                    </div>
                                ) : null}
                            </div>
                        ) : null}

                    <div className={`public-footer__grid ${isBangjeff ? 'public-footer__grid--bangjeff' : ''} ${isIstanaTopup ? 'public-footer__grid--istanatopup' : ''}`}>
                            {footerColumns.map((column) => (
                                <div key={column.title} className="public-footer__column">
                                    <h3>{column.title}</h3>
                                    <ul>
                                        {column.items.map((item) => (
                                            <li key={`${column.title}-${item.label}`}>
                                                <a href={item.href} target={item.href?.startsWith('http') ? '_blank' : undefined} rel={item.href?.startsWith('http') ? 'noreferrer' : undefined}>
                                                    {item.label}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="public-footer__bottom">
                        <p>© {year} {siteConfig.name}. All rights reserved.</p>
                        {!isBangjeff ? <p>{siteConfig.name} - Top up game murah & legal 24 jam.</p> : null}
                    </div>
                </div>
            </footer>

            {siteConfig.socials?.whatsapp ? (
                <a
                    href={siteConfig.socials.whatsapp}
                    className="public-cs-float"
                    target="_blank"
                    rel="noreferrer"
                >
                    <span className="public-cs-float__icon">🎧</span>
                    <span>CS {siteConfig.name}</span>
                </a>
            ) : null}
        </>
    );
}

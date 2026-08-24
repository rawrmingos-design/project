import React, { useMemo } from 'react';
import { Head, usePage } from '@inertiajs/react';
import Navbar from '../Components/Navbar';
import Footer from '../Components/Footer';
import LiveSalesToast from '../Components/LiveSalesToast';
import { resolveTheme } from '../themeRegistry';
import '../../css/public-theme-istanatopup.css';

export default function PublicLayout({ children, meta = {}, mainClassName = '' }) {
    const { siteConfig, theme, featureFlags } = usePage().props;
    const activeTheme = resolveTheme(theme?.key);
    const palette = activeTheme.tokens.colors || siteConfig.colors;
    const shouldRenderLiveSalesToast = featureFlags?.liveSalesEnabled;
    const normalizeCanonicalUrl = (url) => {
        if (typeof window === 'undefined') {
            return url || '';
        }

        try {
            const parsed = new URL(url || window.location.href, window.location.origin);
            parsed.protocol = 'https:';
            parsed.hostname = parsed.hostname.replace(/^www\./i, '');
            parsed.search = '';
            parsed.hash = '';

            return parsed.toString();
        } catch {
            return window.location.href.replace(/^http:/i, 'https:').replace('://www.', '://').split(/[?#]/)[0];
        }
    };
    const canonicalUrl = normalizeCanonicalUrl(meta.canonical);
    const schemaJson = useMemo(() => {
        if (!meta?.schemaMarkup) {
            return null;
        }

        if (typeof meta.schemaMarkup === 'string') {
            const raw = meta.schemaMarkup.trim();
            if (!raw) {
                return null;
            }

            try {
                JSON.parse(raw);
                return raw;
            } catch {
                return null;
            }
        }

        if (typeof meta.schemaMarkup === 'object') {
            try {
                return JSON.stringify(meta.schemaMarkup);
            } catch {
                return null;
            }
        }

        return null;
    }, [meta?.schemaMarkup]);

    const themeStyle = {
        '--public-color-primary': palette.primary,
        '--public-color-secondary': palette.secondary,
        '--public-color-accent': palette.accent,
        '--public-color-highlight': palette.highlight,
        '--public-radius-shell': activeTheme.tokens.radius,
        '--public-shell-width': activeTheme.tokens.shellMaxWidth,
        '--public-card-shadow': activeTheme.tokens.cardShadow,
        '--public-font-family': activeTheme.tokens.font || 'inherit',
    };

    return (
        <>
            <Head>
                <title>{meta.title || siteConfig.name}</title>
                <meta name="description" content={meta.description || siteConfig.description} />
                <meta name="keywords" content={meta.keywords || siteConfig.keywords} />
                <meta name="robots" content={meta.robots || 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'} />
                <link rel="canonical" href={canonicalUrl} />
                <meta property="og:title" content={meta.title || siteConfig.name} />
                <meta property="og:description" content={meta.description || siteConfig.description} />
                <meta property="og:url" content={canonicalUrl} />
                <meta property="og:image" content={meta.image || siteConfig.favicon} />
                <meta property="og:type" content="website" />
                <meta name="twitter:card" content={meta.twitterCard || (meta.image || siteConfig.favicon ? 'summary_large_image' : 'summary')} />
                <meta name="twitter:title" content={meta.title || siteConfig.name} />
                <meta name="twitter:description" content={meta.description || siteConfig.description} />
                <meta name="twitter:image" content={meta.image || siteConfig.favicon} />
                <meta name="theme-color" content={palette.accent} />
                <link rel="icon" href={siteConfig.favicon} />
                {schemaJson ? (
                    <script type="application/ld+json">{schemaJson}</script>
                ) : null}
            </Head>

            <div className={`public-app public-app--${theme?.key || 'default'}`} style={themeStyle}>
                <Navbar />
                <main className={`public-main ${mainClassName}`.trim()}>{children}</main>
                <Footer />
                {shouldRenderLiveSalesToast ? <LiveSalesToast enabled fallbackImage={siteConfig.favicon} /> : null}
            </div>
        </>
    );
}

import React from 'react';
import { usePage } from '@inertiajs/react';
import Navbar from '../Components/Navbar';
import Footer from '../Components/Footer';
import LiveSalesToast from '../Components/LiveSalesToast';
import { resolveTheme } from '../themeRegistry';
import SeoHead from '../Components/SeoHead';
import '../../../css/public-app.css';
import '../../../css/public-theme-istanatopup.css';

export default function PublicLayout({ children, meta = {}, mainClassName = '', rootClassName = '' }) {
    const { siteConfig, theme, featureFlags } = usePage().props;
    const activeTheme = resolveTheme(theme?.key);
    // Theme tokens are defaults; setting_webs colors remain the runtime source of truth.
    const palette = {
        ...(activeTheme.tokens.colors || {}),
        ...(siteConfig.colors || {}),
    };
    const shouldRenderLiveSalesToast = featureFlags?.liveSalesEnabled;

    const themeStyle = {
        '--public-color-primary': palette.primary,
        '--public-color-secondary': palette.secondary,
        '--public-color-accent': palette.accent,
        '--public-color-highlight': palette.highlight,
        '--ist-bg': activeTheme.tokens.background || '#121212',
        '--ist-surface': palette.secondary || activeTheme.tokens.surface || '#1A1A1A',
        '--ist-surface-alt': activeTheme.tokens.surfaceAlt || palette.secondary || '#1F1F1F',
        '--ist-border': activeTheme.tokens.border || '#2F2F2F',
        '--ist-text': '#FFFFFF',
        '--ist-text-soft': '#F5F5F5',
        '--ist-muted': activeTheme.tokens.textMuted || '#9C9C9C',
        '--public-radius-shell': activeTheme.tokens.radius,
        '--public-shell-width': activeTheme.tokens.shellMaxWidth,
        '--public-card-shadow': activeTheme.tokens.cardShadow,
        '--public-font-family': activeTheme.tokens.font || 'inherit',
    };

    return (
        <>
            <SeoHead meta={meta} />
            <div className={`public-app public-app--${theme?.key || 'default'} ${rootClassName}`.trim()} style={themeStyle}>
                <Navbar />
                <main className={`public-main ${mainClassName}`.trim()}>{children}</main>
                <Footer />
                {shouldRenderLiveSalesToast ? <LiveSalesToast enabled fallbackImage={siteConfig.favicon} /> : null}
            </div>
        </>
    );
}

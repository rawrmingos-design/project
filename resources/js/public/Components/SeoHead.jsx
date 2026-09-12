import React, { useMemo } from 'react';
import { Head, usePage } from '@inertiajs/react';

function absoluteUrl(value, fallback = '') {
    if (!value) return fallback;
    try {
        return new URL(value, typeof window === 'undefined' ? 'https://localhost' : window.location.origin).toString();
    } catch {
        return fallback;
    }
}

function canonicalUrl(value) {
    const fallback = typeof window === 'undefined' ? '' : window.location.href;
    const resolved = absoluteUrl(value, fallback);
    if (!resolved) return '';

    try {
        const url = new URL(resolved);
        url.protocol = 'https:';
        url.hostname = url.hostname.replace(/^www\./i, '');
        url.search = '';
        url.hash = '';
        return url.toString();
    } catch {
        return resolved.split(/[?#]/)[0];
    }
}

function serializeSchema(schemaMarkup) {
    if (!schemaMarkup) return null;

    if (typeof schemaMarkup === 'string') {
        const raw = schemaMarkup.trim();
        if (!raw) return null;
        try {
            JSON.parse(raw);
            return raw;
        } catch {
            return null;
        }
    }

    if (typeof schemaMarkup === 'object') {
        try {
            return JSON.stringify(schemaMarkup);
        } catch {
            return null;
        }
    }

    return null;
}

export default function SeoHead({ meta = {} }) {
    const { siteConfig = {}, seoDefaults = {}, theme } = usePage().props;
    const resolved = {
        title: meta.title || seoDefaults.title || siteConfig.name || 'ISTANATOPUP',
        description: meta.description || seoDefaults.description || siteConfig.description || '',
        keywords: meta.keywords || seoDefaults.keywords || siteConfig.keywords || '',
        canonical: canonicalUrl(meta.canonical || seoDefaults.canonical),
        image: absoluteUrl(meta.image || seoDefaults.image || siteConfig.favicon),
        robots: meta.robots || seoDefaults.robots || 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
        author: meta.author || siteConfig.name || '',
        ogTitle: meta.ogTitle || meta.title || seoDefaults.title || siteConfig.name || '',
        ogDescription: meta.ogDescription || meta.description || seoDefaults.description || siteConfig.description || '',
        ogType: meta.ogType || 'website',
        twitterCard: meta.twitterCard || (meta.image || seoDefaults.image || siteConfig.favicon ? 'summary_large_image' : 'summary'),
        imageAlt: meta.imageAlt || meta.title || siteConfig.name || '',
    };
    const schemaJson = useMemo(() => {
        if (resolved.robots.startsWith('noindex')) return null;

        const provided = serializeSchema(meta.schemaMarkup || seoDefaults.schemaMarkup);
        if (provided) return provided;

        const siteName = siteConfig.name || resolved.author;
        const siteUrl = canonicalUrl('/id');
        const sameAs = Object.values(siteConfig.socials || {}).filter((url) => /^https?:\/\//i.test(url || ''));
        return JSON.stringify([
            {
                '@context': 'https://schema.org',
                '@type': 'WebSite',
                name: siteName,
                url: siteUrl,
                inLanguage: 'id-ID',
                potentialAction: {
                    '@type': 'SearchAction',
                    target: `${siteUrl.replace(/\/$/, '')}/search/products?q={search_term_string}`,
                    'query-input': 'required name=search_term_string',
                },
            },
            {
                '@context': 'https://schema.org',
                '@type': 'Organization',
                name: siteName,
                url: siteUrl,
                ...(resolved.image ? { logo: resolved.image } : {}),
                sameAs,
            },
        ]);
    }, [meta.schemaMarkup, seoDefaults.schemaMarkup, resolved.author, resolved.image, siteConfig.name, siteConfig.socials]);
    const socialImage = resolved.image || undefined;

    return (
        <Head>
            <title head-key="title">{resolved.title}</title>
            {resolved.description ? <meta head-key="description" name="description" content={resolved.description} /> : null}
            {resolved.keywords ? <meta head-key="keywords" name="keywords" content={resolved.keywords} /> : null}
            <meta head-key="author" name="author" content={resolved.author} />
            <meta head-key="robots" name="robots" content={resolved.robots} />
            {resolved.canonical ? <link head-key="canonical" rel="canonical" href={resolved.canonical} /> : null}
            <meta head-key="og:title" property="og:title" content={resolved.ogTitle} />
            <meta head-key="og:description" property="og:description" content={resolved.ogDescription} />
            <meta head-key="og:type" property="og:type" content={resolved.ogType} />
            <meta head-key="og:locale" property="og:locale" content="id_ID" />
            <meta head-key="og:site_name" property="og:site_name" content={siteConfig.name || resolved.author} />
            {resolved.canonical ? <meta head-key="og:url" property="og:url" content={resolved.canonical} /> : null}
            {socialImage ? <meta head-key="og:image" property="og:image" content={socialImage} /> : null}
            {socialImage ? <meta head-key="og:image:alt" property="og:image:alt" content={resolved.imageAlt} /> : null}
            <meta head-key="twitter:card" name="twitter:card" content={resolved.twitterCard} />
            <meta head-key="twitter:title" name="twitter:title" content={resolved.ogTitle} />
            <meta head-key="twitter:description" name="twitter:description" content={resolved.ogDescription} />
            {socialImage ? <meta head-key="twitter:image" name="twitter:image" content={socialImage} /> : null}
            {socialImage ? <meta head-key="twitter:image:alt" name="twitter:image:alt" content={resolved.imageAlt} /> : null}
            <meta head-key="theme-color" name="theme-color" content={siteConfig.colors?.accent || '#F97316'} />
            {theme?.key === 'istanatopup' ? (
                <>
                    <link rel="preconnect" href="https://fonts.googleapis.com" />
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                </>
            ) : null}
            {schemaJson ? <script head-key="json-ld" type="application/ld+json">{schemaJson}</script> : null}
        </Head>
    );
}

export { absoluteUrl, canonicalUrl, serializeSchema };

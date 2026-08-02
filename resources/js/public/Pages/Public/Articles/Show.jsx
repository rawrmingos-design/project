import React, { useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';

const shareIcons = {
    facebook: <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14.2 8.02h2.25V4.24A29.3 29.3 0 0 0 13.18 4c-3.23 0-5.44 1.98-5.44 5.6v3.13H4.1v4.23h3.64V24h4.45v-7.04h3.48l.55-4.23h-4.03V10c0-1.22.34-1.98 2.01-1.98Z" /></svg>,
    twitter: <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18.9 2.25h3.68l-8.04 9.2L24 21.75h-7.42l-5.8-7.59-6.65 7.59H.45l8.6-9.84L0 2.25h7.6l5.25 6.94 6.05-6.94Zm-1.29 17.68h2.04L6.5 3.98H4.31l13.3 15.95Z" /></svg>,
    whatsapp: <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.52 3.48A11.79 11.79 0 0 0 12.1 0C5.55 0 .23 5.32.23 11.86c0 2.09.55 4.13 1.58 5.93L.13 24l6.36-1.67a11.88 11.88 0 0 0 5.61 1.43h.01c6.54 0 11.86-5.32 11.86-11.86 0-3.17-1.23-6.15-3.45-8.42ZM12.1 21.75h-.01a9.84 9.84 0 0 1-5.02-1.38l-.36-.22-3.77.99 1-3.67-.24-.38a9.82 9.82 0 0 1-1.51-5.23c0-5.45 4.44-9.89 9.91-9.89 2.65 0 5.13 1.03 7 2.9a9.82 9.82 0 0 1 2.9 7.02c0 5.46-4.44 9.86-9.9 9.86Zm5.43-7.39c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.08-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z" /></svg>,
    telegram: <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M23.48 2.2c.33-1.36-.5-1.9-1.43-1.55L1.2 8.69C-.22 9.24-.2 10.03.94 10.38l5.35 1.67L18.67 4.3c.58-.35 1.12-.16.68.23l-10.02 9.04-.38 5.68c.55 0 .8-.25 1.11-.55l2.67-2.6 5.55 4.1c1.02.56 1.75.27 2-.95l3.2-17.05Z" /></svg>,
    link: <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3.9 12a5 5 0 0 1 5-5h4v2h-4a3 3 0 1 0 0 6h4v2h-4a5 5 0 0 1-5-5Zm6.1 1h4v-2h-4v2Zm1-4h4a3 3 0 1 1 0 6h-4v2h4a5 5 0 1 0 0-10h-4v2Z" /></svg>,
    check: <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9.55 17.7 3.9 12.05l1.42-1.42 4.23 4.23 9.13-9.13 1.42 1.42L9.55 17.7Z" /></svg>,
};

function ArticleMeta({ article, modern = false }) {
    return (
        <div className={`public-article-detail-card__meta${modern ? ' public-article-modern__meta' : ''}`}>
            <span>{article.publishedAtLabel}</span>
            <span>{article.views} {modern ? 'Reads' : 'Views'}</span>
            <span>Admin</span>
        </div>
    );
}

function ArticleBody({ content }) {
    return <div className="public-article-content" dangerouslySetInnerHTML={{ __html: content || '' }} />;
}

function ShareControls({ shareData }) {
    const [copyLabel, setCopyLabel] = useState('Copy Link');

    const handleCopyLink = async () => {
        const markCopied = () => {
            setCopyLabel('Tersalin');
            window.setTimeout(() => setCopyLabel('Copy Link'), 1600);
        };

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(shareData.articleUrl);
                markCopied();
                return;
            } catch {
                // Clipboard access can be denied by the browser.
            }
        }

        window.prompt('Salin link artikel:', shareData.articleUrl);
    };

    return (
        <footer className="public-article-share">
            <h3>Bagikan Artikel Ini</h3>
            <div className="public-article-share__buttons">
                <a href={shareData.facebook} target="_blank" rel="noopener noreferrer" aria-label="Bagikan artikel ke Facebook" title="Facebook">{shareIcons.facebook}</a>
                <a href={shareData.twitter} target="_blank" rel="noopener noreferrer" aria-label="Bagikan artikel ke Twitter" title="Twitter/X">{shareIcons.twitter}</a>
                <a href={shareData.whatsapp} target="_blank" rel="noopener noreferrer" aria-label="Bagikan artikel ke WhatsApp" title="WhatsApp">{shareIcons.whatsapp}</a>
                <a href={shareData.telegram} target="_blank" rel="noopener noreferrer" aria-label="Bagikan artikel ke Telegram" title="Telegram">{shareIcons.telegram}</a>
                <button type="button" onClick={handleCopyLink} aria-label="Salin link artikel" title={copyLabel}>{copyLabel === 'Tersalin' ? shareIcons.check : shareIcons.link}</button>
            </div>
        </footer>
    );
}

function RelatedArticles({ articles, modern = false }) {
    if (!articles.length) {
        return null;
    }

    return (
        <aside className={modern ? 'public-article-modern__related' : 'public-article-detail-sidebar'}>
            <div className="public-article-related">
                <h2>Baca Juga</h2>
                <div className="public-article-related__grid">
                    {articles.map((item) => (
                        <Link key={item.id} href={`/id/artikel/${item.slug}`} className="public-article-related__item">
                            <img src={item.thumbnail} alt={item.title} />
                            <div><strong>{item.title}</strong><span>{item.publishedAtLabel}</span></div>
                        </Link>
                    ))}
                </div>
            </div>
        </aside>
    );
}

function Breadcrumb({ article }) {
    return <nav className="public-article-breadcrumb" aria-label="Breadcrumb"><Link href="/id">Home</Link><span>/</span><Link href="/id/artikel">Artikel</Link><span>/</span><strong>{article.title}</strong></nav>;
}

function DefaultArticle({ article, recentArticles, shareData }) {
    return (
        <div className="public-shell public-article-show">
            <Breadcrumb article={article} />
            <div className="public-article-detail-layout">
                <div className="public-article-detail-main">
                    <article className="public-article-detail-card">
                        <header className="public-article-detail-card__header">
                            <div className="public-article-detail-card__tags"><span>News</span>{article.keywords?.toLowerCase().includes('promo') ? <span className="is-promo">Promo</span> : null}</div>
                            <h1>{article.title}</h1>
                            <ArticleMeta article={article} />
                        </header>
                        <div className="public-article-detail-card__cover"><img src={article.thumbnail} alt={article.title} /></div>
                        <ArticleBody content={article.content} />
                        <ShareControls shareData={shareData} />
                    </article>
                </div>
                <RelatedArticles articles={recentArticles} />
            </div>
        </div>
    );
}

function ModernArticle({ article, recentArticles, shareData }) {
    const keywords = article.keywords?.split(',').map((keyword) => keyword.trim()).filter(Boolean) || [];

    return (
        <div className="public-article-modern">
            <section className="public-article-modern__hero" style={{ backgroundImage: `url("${article.thumbnail}")` }}>
                <div className="public-article-modern__hero-overlay" />
                <div className="public-shell public-article-modern__hero-content">
                    <span className="public-article-modern__eyebrow">Featured Article</span>
                    <h1>{article.title}</h1>
                    <ArticleMeta article={article} modern />
                </div>
            </section>
            <div className="public-shell public-article-modern__body-layout">
                <article className="public-article-modern__content-card">
                    {article.metaDescription ? <p className="public-article-modern__intro">{article.metaDescription}</p> : null}
                    <ArticleBody content={article.content} />
                    <div className="public-article-modern__footer">
                        {keywords.length ? <div className="public-article-modern__keywords">{keywords.map((keyword) => <span key={keyword}>#{keyword}</span>)}</div> : null}
                        <ShareControls shareData={shareData} />
                    </div>
                </article>
                <RelatedArticles articles={recentArticles} modern />
            </div>
        </div>
    );
}

export default function ArticleShow({ meta, article, recentArticles = [] }) {
    const shareData = useMemo(() => {
        const articleUrl = typeof window !== 'undefined' ? window.location.href : meta?.canonical || `/id/artikel/${article.slug}`;
        const shareText = `${article.title}${meta?.siteName ? ` - ${meta.siteName}` : ''}`;
        const encodedUrl = encodeURIComponent(articleUrl);
        const encodedText = encodeURIComponent(shareText);

        return {
            articleUrl,
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}&quote=${encodedText}`,
            twitter: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedText}`,
            whatsapp: `https://wa.me/?text=${encodedText}%20${encodedUrl}`,
            telegram: `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`,
        };
    }, [article.slug, article.title, meta?.canonical, meta?.siteName]);

    const articleStyle = {
        '--article-primary': article.primaryColor || 'var(--public-color-primary)',
        '--article-secondary': article.secondaryColor || 'var(--public-color-secondary)',
    };

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className={`public-article-page public-article-page--show public-article-page--${article.layout}`} style={articleStyle}>
                {article.layout === 'modern'
                    ? <ModernArticle article={article} recentArticles={recentArticles} shareData={shareData} />
                    : <DefaultArticle article={article} recentArticles={recentArticles} shareData={shareData} />}
            </section>
        </PublicLayout>
    );
}

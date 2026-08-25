import React, { useEffect, useMemo, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import HeroBanner from '../../Components/HeroBanner';
import ProductCard from '../../Components/ProductCard';
import HomepagePopup from '../../Components/HomepagePopup';

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
}

function getCountdownParts(expiresAt) {
    if (!expiresAt) {
        return null;
    }

    const diff = new Date(expiresAt).getTime() - Date.now();

    if (diff <= 0) {
        return { hours: '00', minutes: '00', seconds: '00' };
    }

    const totalSeconds = Math.floor(diff / 1000);
    const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
    const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
    const seconds = String(totalSeconds % 60).padStart(2, '0');

    return { hours, minutes, seconds };
}

function formatArticleDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(date).replace(/\//g, '.');
}

export default function Home({ meta, banners, popup, featuredCategories, categoryTabs, flashsale, articles, paymentMethods }) {
    const { featureFlags, siteConfig, theme } = usePage().props;
    const [activeCategoryTab, setActiveCategoryTab] = useState(0);
    const activeThemeKey = theme?.key || 'default';
    const flashsaleItems = Array.isArray(flashsale) ? flashsale : [];
    const articleItems = Array.isArray(articles) ? articles : [];
    const activeGroup = categoryTabs[activeCategoryTab] || categoryTabs[0] || null;
    const flashsaleMarqueeItems = useMemo(
        () => (flashsaleItems.length ? [...flashsaleItems, ...flashsaleItems] : []),
        [flashsaleItems],
    );
    const flashsaleDeadline = useMemo(() => {
        const dates = flashsaleItems
            .map((item) => item.expiresAt)
            .filter(Boolean)
            .map((value) => new Date(value).getTime())
            .filter((value) => Number.isFinite(value) && value > Date.now());

        if (!dates.length) {
            return null;
        }

        return new Date(Math.min(...dates)).toISOString();
    }, [flashsaleItems]);

    const [flashsaleCountdown, setFlashsaleCountdown] = useState(() => getCountdownParts(flashsaleDeadline));

    useEffect(() => {
        setFlashsaleCountdown(getCountdownParts(flashsaleDeadline));

        if (!flashsaleDeadline) {
            return undefined;
        }

        const interval = window.setInterval(() => {
            setFlashsaleCountdown(getCountdownParts(flashsaleDeadline));
        }, 1000);

        return () => window.clearInterval(interval);
    }, [flashsaleDeadline]);

    useEffect(() => {
        if (!categoryTabs.length) {
            return;
        }

        if (activeCategoryTab >= categoryTabs.length) {
            setActiveCategoryTab(0);
        }
    }, [activeCategoryTab, categoryTabs]);

    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <div className="hero-storefront__bleed">
                <HeroBanner banners={banners} />
            </div>
            <div className="public-shell">
                {activeThemeKey === 'istanatopup' && siteConfig?.name ? (
                    <div className="ist-site-intro">
                        <h1>{siteConfig.name}</h1>
                        <p>{siteConfig.description}</p>
                    </div>
                ) : null}
                {flashsaleItems.length ? (
                    <section className="public-section public-section--storefront public-section--flashsale">
                        <div className="storefront-heading storefront-heading--flashsale">
                            <div>
                                <div className="flashsale-heading__row">
                                    <h2 className="storefront-heading__title">
                                        <span className="storefront-heading__icon">⚡</span>
                                        FLASH SALE
                                    </h2>
                                    {flashsaleCountdown ? (
                                        <div className="flashsale-heading__countdown" aria-label="Countdown flash sale">
                                            <span>{flashsaleCountdown.hours}</span>
                                            <span>{flashsaleCountdown.minutes}</span>
                                            <span>{flashsaleCountdown.seconds}</span>
                                        </div>
                                    ) : null}
                                </div>
                                <p className="storefront-heading__subtitle">Pesan sekarang! Persediaan terbatas.</p>
                            </div>
                        </div>

                        <div className="flashsale-rail flashsale-rail--marquee" role="list">
                            <div className="flashsale-rail__track">
                                {flashsaleMarqueeItems.map((item, index) => {
                                    const discountPercent = item.flashPrice && item.price > item.flashPrice
                                        ? Math.round(((item.price - item.flashPrice) / item.price) * 100)
                                        : null;
                                    const savingsAmount = item.flashPrice && item.price > item.flashPrice
                                        ? item.price - item.flashPrice
                                        : 0;

                                    return (
                                        <Link key={`${item.id}-${index}`} href={`/id/${item.slug}`} className="flashsale-card flashsale-card--bangjeff" role="listitem">
                                            <div className="flashsale-card__content">
                                                <div className="flashsale-card__headline">
                                                    <strong>{item.title}</strong>
                                                    {discountPercent ? <span className="flashsale-card__discount">-{discountPercent}%</span> : null}
                                                </div>
                                                <span className="flashsale-card__game">{item.category}</span>

                                                <div className="flashsale-card__summary">
                                                    <div className="flashsale-card__thumb flashsale-card__thumb--compact">
                                                        <img src={item.productLogo || item.thumbnail} alt={item.title} />
                                                    </div>

                                                    <div className="flashsale-card__details">
                                                        <div className="flashsale-card__price flashsale-card__price--bangjeff">
                                                            <span>{formatCurrency(item.flashPrice || item.price)}</span>
                                                            {item.flashPrice ? <small>{formatCurrency(item.price)}</small> : null}
                                                        </div>

                                                        <div className="flashsale-card__stock">
                                                            <div className="flashsale-card__progress">
                                                                <span style={{ width: `${Math.max(10, Math.min(100, item.stock || 0))}%` }} />
                                                            </div>
                                                            <small>
                                                                <span>Stok tersedia</span>
                                                                <strong>{item.stock}</strong>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="flashsale-card__footer">
                                                    <span className="flashsale-card__footer-pill flashsale-card__footer-pill--accent">
                                                        {discountPercent ? `-Rp ${new Intl.NumberFormat('id-ID').format(savingsAmount)}` : 'Promo aktif'}
                                                    </span>
                                                    <span className="flashsale-card__footer-pill">Flash sale</span>
                                                </div>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    </section>
                ) : null}

                <section className="public-section public-section--storefront public-section--popular">
                    <div className="storefront-heading">
                        <div>
                            <h2 className="storefront-heading__title">
                                <span className="storefront-heading__icon">✨</span>
                                {activeThemeKey === 'istanatopup' ? 'FAVORIT' : activeThemeKey === 'bangjeff' ? 'TRENDING' : 'POPULER!'}
                            </h2>
                            <p className="storefront-heading__subtitle">
                                {activeThemeKey === 'bangjeff'
                                    ? 'Berikut adalah beberapa produk yang paling populer saat ini.'
                                    : `Beberapa produk yang paling populer saat ini di ${siteConfig.name}.`}
                            </p>
                        </div>
                    </div>

                    <div className="product-grid product-grid--storefront product-grid--popular-storefront ist-hgrid">
                        {featuredCategories.map((item) => (
                            <Link key={item.id} href={`/id/${item.slug}`} className="ist-hcard">
                                <span className="ist-hcard__art">
                                    <img src={item.productLogo || item.thumbnail} alt={item.name} loading="lazy" />
                                </span>
                                <span className="hc-txt">
                                    <b>{item.name}</b>
                                    <span>{item.subtitle}</span>
                                </span>
                            </Link>
                        ))}
                    </div>
                </section>

                {activeThemeKey === 'istanatopup' && categoryTabs.length ? (
                    <section className="public-section public-section--storefront public-section--trending">
                        <div className="storefront-heading">
                            <div>
                                <h2 className="storefront-heading__title">
                                    <span className="storefront-heading__icon">🔥</span>
                                    TRENDING
                                </h2>
                                <p className="storefront-heading__subtitle">
                                    Produk yang sedang banyak dicari pemain.
                                </p>
                            </div>
                        </div>

                        <div className="product-grid product-grid--storefront product-grid--trending-storefront ist-hgrid">
                            {(categoryTabs[0]?.items ?? []).slice(0, 8).map((item) => (
                                <Link key={`trend-${item.id}`} href={`/id/${item.slug}`} className="ist-hcard">
                                    <span className="ist-hcard__art">
                                        <img src={item.productLogo || item.thumbnail} alt={item.name} loading="lazy" />
                                    </span>
                                    <span className="hc-txt">
                                        <b>{item.name}</b>
                                        <span>{item.subtitle}</span>
                                    </span>
                                </Link>
                            ))}
                        </div>
                    </section>
                ) : null}

                <section className="public-section public-section--storefront public-section--tabs">
                    <div className="storefront-tabs">
                        <div className="storefront-tabs__nav">
                            {categoryTabs.map((group, index) => (
                                <button
                                    key={group.id}
                                    type="button"
                                    className={index === activeCategoryTab ? 'is-active' : ''}
                                    onClick={() => setActiveCategoryTab(index)}
                                >
                                    {group.name}
                                </button>
                            ))}
                        </div>
                    </div>

                    {categoryTabs.length ? (
                        <div className="category-tabs category-tabs--storefront">
                            {activeGroup ? (
                                <div className="category-tabs__group">
                                    <div className="product-grid product-grid--storefront product-grid--poster-storefront">
                                        {activeGroup.items.map((item) => (
                                            <ProductCard key={item.id} item={item} variant="poster" showPrice={false} />
                                        ))}
                                    </div>
                                </div>
                            ) : null}
                        </div>
                    ) : (
                        <div className="empty-card">Belum ada kelompok kategori untuk ditampilkan.</div>
                    )}
                </section>

                <section className="public-section public-section--journal">
                    <div className="journal-heading journal-heading--bangjeff">
                        <div className="journal-heading__copy">
                            <h2>ARTIKEL TERBARU &amp; BERITA GAME</h2>
                            <p>
                                Dapatkan informasi terbaru seputar dunia game! Temukan panduan lengkap untuk meningkatkan pengalaman bermain,
                                serta berita terkini mengenai promo, update top-up, dan komunitas gamer.
                            </p>
                        </div>
                    </div>

                    <div className="article-rail article-rail--bangjeff-news">
                        <div className="article-grid article-grid--journal article-grid--bangjeff-news">
                            {articleItems.map((article) => (
                                <article key={article.id} className="article-card article-card--journal">
                                    <Link href={`/id/artikel/${article.slug}`} className="article-card__image-link">
                                        <img src={article.thumbnail || '/assets/logo/favicon.webp'} alt={article.title} />
                                        <div className="article-card__overlay">
                                            <strong>{article.title}</strong>
                                        </div>
                                    </Link>
                                    <div className="article-card__body article-card__body--journal">
                                        <div className="article-card__meta">
                                            <span>Redaksi</span>
                                        </div>
                                        <p>{article.title}</p>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </div>

                    <div className="journal-actions">
                        <Link href="/id/artikel" className="journal-actions__button">Lihat Semua Artikel</Link>
                    </div>
                </section>
            </div>

            <HomepagePopup popup={popup} enabled={featureFlags?.homePopupEnabled} />
        </PublicLayout>
    );
}

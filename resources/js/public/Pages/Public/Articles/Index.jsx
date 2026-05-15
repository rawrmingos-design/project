import React from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';

function formatDateLabel(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

export default function ArticlesIndex({ meta, featured, articles = [], pagination }) {
    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-article-page public-article-page--index">
                {featured ? (
                    <div className="public-article-hero" style={{ backgroundImage: `url('${featured.thumbnail}')` }}>
                        <div className="public-article-hero__overlay" />
                        <div className="public-shell public-article-hero__content">
                            <span className="public-article-hero__tag">Featured News</span>
                            <h1>
                                <Link href={`/id/artikel/${featured.slug}`}>{featured.title}</Link>
                            </h1>
                            <div className="public-article-hero__meta">
                                <span>{featured.publishedAtLabel || formatDateLabel(featured.publishedAt)}</span>
                                <span>{featured.views} Views</span>
                            </div>
                            <p>{featured.metaDescription || featured.excerpt}</p>
                        </div>
                    </div>
                ) : (
                    <div className="public-shell public-article-empty-hero">
                        <h1>Berita &amp; Artikel</h1>
                        <p>Update terbaru seputar dunia game dan esports.</p>
                    </div>
                )}

                <div className="public-shell public-article-section">
                    <header className="public-article-section__header">
                        <h2>Artikel Terbaru</h2>
                        <p>Dapatkan update terbaru seputar dunia game, event, promo, dan panduan top up.</p>
                    </header>

                    {articles.length ? (
                        <div className="public-article-grid">
                            {articles.map((article) => (
                                <Link key={article.id} href={`/id/artikel/${article.slug}`} className="public-article-card">
                                    <div className="public-article-card__thumb">
                                        <img src={article.thumbnail} alt={article.title} />
                                        <div className="public-article-card__thumb-gradient" />
                                        <div className="public-article-card__thumb-meta">
                                            <span>{article.publishedAgo || formatDateLabel(article.publishedAt)}</span>
                                            <span>{article.views} Views</span>
                                        </div>
                                    </div>
                                    <div className="public-article-card__body">
                                        <h3>{article.title}</h3>
                                        <p>{article.excerpt}</p>
                                        <span className="public-article-card__cta">Baca Selengkapnya</span>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    ) : (
                        <div className="public-article-empty-state">
                            <strong>Belum ada artikel aktif.</strong>
                            <span>Silakan tambahkan artikel baru dari panel admin.</span>
                        </div>
                    )}

                    {pagination?.lastPage > 1 ? (
                        <div className="public-article-pagination">
                            <Link
                                href={pagination.prevPageUrl || '#'}
                                className={`public-article-pagination__button ${pagination.prevPageUrl ? '' : 'is-disabled'}`}
                                preserveScroll
                            >
                                Sebelumnya
                            </Link>
                            <span>
                                Halaman {pagination.currentPage} dari {pagination.lastPage}
                            </span>
                            <Link
                                href={pagination.nextPageUrl || '#'}
                                className={`public-article-pagination__button ${pagination.nextPageUrl ? '' : 'is-disabled'}`}
                                preserveScroll
                            >
                                Berikutnya
                            </Link>
                        </div>
                    ) : null}
                </div>
            </section>
        </PublicLayout>
    );
}

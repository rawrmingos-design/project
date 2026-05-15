import React from 'react';
import { Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';

export default function ArticleShow({ meta, article, recentArticles = [] }) {
    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-article-page public-article-page--show">
                <div className="public-shell public-article-show">
                    <nav className="public-article-breadcrumb">
                        <Link href="/id">Home</Link>
                        <span>/</span>
                        <Link href="/id/artikel">Artikel</Link>
                        <span>/</span>
                        <strong>{article.title}</strong>
                    </nav>

                    <div className="public-article-detail-layout">
                        <div className="public-article-detail-main">
                            <article className="public-article-detail-card">
                                <header className="public-article-detail-card__header">
                                    <div className="public-article-detail-card__tags">
                                        <span>News</span>
                                        {article.keywords?.toLowerCase().includes('promo') ? <span className="is-promo">Promo</span> : null}
                                    </div>
                                    <h1>{article.title}</h1>
                                    <div className="public-article-detail-card__meta">
                                        <span>{article.publishedAtLabel}</span>
                                        <span>{article.views} Views</span>
                                        <span>Admin</span>
                                    </div>
                                </header>

                                <div className="public-article-detail-card__cover">
                                    <img src={article.thumbnail} alt={article.title} />
                                </div>

                                <div
                                    className="public-article-content"
                                    dangerouslySetInnerHTML={{ __html: article.content || '' }}
                                />

                                <footer className="public-article-share">
                                    <h3>Bagikan Artikel Ini</h3>
                                    <div className="public-article-share__buttons">
                                        <button type="button">Facebook</button>
                                        <button type="button">Twitter</button>
                                        <button type="button">WhatsApp</button>
                                        <button type="button">Copy Link</button>
                                    </div>
                                </footer>
                            </article>
                        </div>

                        {recentArticles.length ? (
                            <aside className="public-article-detail-sidebar">
                                <div className="public-article-related">
                                    <h2>Baca Juga</h2>
                                    <div className="public-article-related__grid">
                                        {recentArticles.map((item) => (
                                            <Link key={item.id} href={`/id/artikel/${item.slug}`} className="public-article-related__item">
                                                <img src={item.thumbnail} alt={item.title} />
                                                <div>
                                                    <strong>{item.title}</strong>
                                                    <span>{item.publishedAtLabel}</span>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            </aside>
                        ) : null}
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}

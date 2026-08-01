import React from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

function Stars({ value }) {
    const stars = Math.max(0, Math.min(5, Number(value || 0)));

    return (
        <span className="public-review-card__stars" aria-label={`${stars} dari 5 bintang`}>
            {[1, 2, 3, 4, 5].map((star) => <span key={star} aria-hidden="true">{star <= stars ? '★' : '☆'}</span>)}
        </span>
    );
}

export default function Reviews({ meta, reviews = [] }) {
    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-information-page">
                <div className="public-shell">
                    <header className="public-information-page__hero">
                        <p className="public-information-page__eyebrow">Testimoni</p>
                        <h1>Ulasan Pelanggan</h1>
                        <p>Terima kasih kepada pelanggan yang telah memberi ulasan dan peringkat.</p>
                    </header>

                    {reviews.length ? (
                        <div className="public-reviews-grid">
                            {reviews.map((review) => (
                                <article key={review.id} className="public-review-card">
                                    {review.categoryName ? <p className="public-review-card__category">{review.categoryName}</p> : null}
                                    <p className="public-review-card__comment">“{review.comment || '-'}”</p>
                                    <div className="public-review-card__footer">
                                        <div>
                                            <strong>{review.displayName || 'Guest'}</strong>
                                            {review.productName ? <small>{review.productName}</small> : null}
                                        </div>
                                        <div className="public-review-card__rating">
                                            <Stars value={review.stars} />
                                            {review.createdAt ? <small>{review.createdAt}</small> : null}
                                        </div>
                                    </div>
                                </article>
                            ))}
                        </div>
                    ) : <p className="public-information-empty">Belum ada ulasan untuk ditampilkan.</p>}
                </div>
            </section>
        </PublicLayout>
    );
}

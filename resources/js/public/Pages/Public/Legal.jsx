import React from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function Legal({ meta, legal }) {
    return (
        <PublicLayout meta={meta} mainClassName="public-main--hero-bleed">
            <section className="public-legal-page">
                <div className="public-shell">
                    <header className="public-legal-hero">
                        {legal?.badge ? <span className="public-legal-hero__badge">{legal.badge}</span> : null}
                        <h1>{legal?.title || 'Dokumen Legal'}</h1>
                        {legal?.subtitle ? <p>{legal.subtitle}</p> : null}
                        {legal?.updatedAt ? (
                            <small className="public-legal-hero__updated">Diperbarui: {legal.updatedAt}</small>
                        ) : null}
                    </header>

                    <article className="public-legal-card">
                        <div
                            className="public-legal-prose"
                            dangerouslySetInnerHTML={{ __html: legal?.contentHtml || '<p>Konten belum tersedia.</p>' }}
                        />
                    </article>
                </div>
            </section>
        </PublicLayout>
    );
}


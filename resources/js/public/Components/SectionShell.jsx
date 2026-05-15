import React from 'react';

export default function SectionShell({ title, subtitle, actions = null, children }) {
    return (
        <section className="public-section">
            {(title || subtitle || actions) && (
                <div className="public-section__header">
                    <div>
                        {title ? <h2 className="public-section__title">{title}</h2> : null}
                        {subtitle ? <p className="public-section__subtitle">{subtitle}</p> : null}
                    </div>
                    {actions ? <div className="public-section__actions">{actions}</div> : null}
                </div>
            )}
            {children}
        </section>
    );
}

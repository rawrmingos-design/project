import React from 'react';
import { Link, usePage } from '@inertiajs/react';

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
}

export default function ProductCard({ item, compact = false, onClick = null, isActive = false, variant = 'default', showPrice = true }) {
    const { theme } = usePage().props;
    const activeThemeKey = theme?.key || 'default';

    if (variant === 'poster') {
        const posterBody = (
            <>
                <div className="product-card__poster-media">
                    {activeThemeKey === 'bangjeff' ? (
                        <>
                            <span className="product-card__poster-badge product-card__poster-badge--left" aria-hidden="true">⛓</span>
                        </>
                    ) : null}
                    <img src={item.productLogo || item.thumbnail || '/assets/logo/favicon.webp'} alt={item.name} />
                </div>
                <div className="product-card__poster-body">
                    <strong>{item.name}</strong>
                    {item.subtitle ? <span>{item.subtitle}</span> : null}
                </div>
            </>
        );

        if (onClick) {
            return (
                <button type="button" className={`product-card product-card--poster ${isActive ? 'is-active' : ''}`} onClick={onClick}>
                    {posterBody}
                </button>
            );
        }

        return (
            <Link href={`/id/${item.slug}`} className="product-card product-card--poster">
                {posterBody}
            </Link>
        );
    }

    const body = (
        <>
            <div className="product-card__media">
                <img src={item.productLogo || item.thumbnail || '/assets/logo/favicon.webp'} alt={item.name} />
            </div>
            <div className="product-card__content">
                <strong>{item.name}</strong>
                {item.subtitle ? <span>{item.subtitle}</span> : null}
                {showPrice && typeof item.price === 'number' ? (
                    <div className="product-card__price">
                        {item.isFlashSale && item.flashPrice ? (
                            <>
                                <span>{formatCurrency(item.flashPrice)}</span>
                                <small>{formatCurrency(item.price)}</small>
                            </>
                        ) : (
                            <span>{formatCurrency(item.price)}</span>
                        )}
                    </div>
                ) : null}
            </div>
        </>
    );

    if (onClick) {
        return (
            <button type="button" className={`product-card product-card--${variant} ${compact ? 'product-card--compact' : ''} ${isActive ? 'is-active' : ''}`} onClick={onClick}>
                {body}
            </button>
        );
    }

    return (
        <Link href={`/id/${item.slug}`} className={`product-card product-card--${variant} ${compact ? 'product-card--compact' : ''}`}>
            {body}
        </Link>
    );
}

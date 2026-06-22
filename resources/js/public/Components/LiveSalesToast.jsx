import React, { useEffect, useMemo, useState } from 'react';

const DISPLAY_DURATION_MS = 3500;
const NEXT_TOAST_DELAY_MS = 2200;
const REFRESH_INTERVAL_MS = 300000;

export default function LiveSalesToast({ enabled = true, fallbackImage = '/assets/logo/favicon.webp' }) {
    const [items, setItems] = useState([]);
    const [activeItem, setActiveItem] = useState(null);
    const [isVisible, setIsVisible] = useState(false);

    const safeFallbackImage = fallbackImage || '/assets/logo/favicon.webp';
    const activeImage = activeItem?.image || safeFallbackImage;
    const activeAlt = activeItem?.item || 'Item';

    const hasItems = useMemo(() => items.length > 0, [items]);

    useEffect(() => {
        if (!enabled) {
            setItems([]);
            setActiveItem(null);
            setIsVisible(false);
            return undefined;
        }

        const fetchItems = async () => {
            try {
                const response = await fetch('/api/recent-purchases', {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                setItems(Array.isArray(payload) ? payload : []);
            } catch (error) {
                console.warn('Live sales toast fetch failed', error);
            }
        };

        fetchItems();
        const refresh = window.setInterval(fetchItems, REFRESH_INTERVAL_MS);

        return () => window.clearInterval(refresh);
    }, [enabled]);

    useEffect(() => {
        if (!enabled || !hasItems) {
            setActiveItem(null);
            setIsVisible(false);
            return undefined;
        }

        let showTimer = null;
        let hideTimer = null;
        let cancelled = false;

        const showNextToast = (delay = NEXT_TOAST_DELAY_MS) => {
            window.clearTimeout(showTimer);
            showTimer = window.setTimeout(() => {
                if (cancelled || !items.length) {
                    return;
                }

                const nextItem = items[Math.floor(Math.random() * items.length)];
                setActiveItem(nextItem);
                setIsVisible(true);

                window.clearTimeout(hideTimer);
                hideTimer = window.setTimeout(() => {
                    setIsVisible(false);
                    showNextToast();
                }, DISPLAY_DURATION_MS);
            }, delay);
        };

        showNextToast(900);

        return () => {
            cancelled = true;
            window.clearTimeout(showTimer);
            window.clearTimeout(hideTimer);
        };
    }, [enabled, hasItems, items]);

    const handleClose = () => {
        setIsVisible(false);
    };

    if (!enabled || !activeItem) {
        return null;
    }

    return (
        <aside className={`live-sales-toast ${isVisible ? 'live-sales-toast--visible' : 'live-sales-toast--hidden'}`} aria-live="polite">
            <div className="live-sales-toast__media">
                <img src={activeImage} alt={activeAlt} loading="lazy" />
                <span className="live-sales-toast__check" aria-hidden="true">✓</span>
            </div>
            <div className="live-sales-toast__content">
                <small>Baru saja membeli</small>
                <strong>{activeItem.item || 'Item'}</strong>
                <span>{activeItem.name || 'Seseorang'} · {activeItem.time_ago || 'Baru saja'}</span>
            </div>
            <button type="button" className="live-sales-toast__close" onClick={handleClose} aria-label="Tutup live sales">
                ×
            </button>
        </aside>
    );
}

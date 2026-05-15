import React, { useEffect, useMemo, useState } from 'react';

export default function LiveSalesToast({ enabled = true }) {
    const [items, setItems] = useState([]);
    const [activeIndex, setActiveIndex] = useState(0);
    const activeItem = useMemo(() => items[activeIndex] ?? null, [items, activeIndex]);

    useEffect(() => {
        if (!enabled) {
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
                if (Array.isArray(payload) && payload.length > 0) {
                    setItems(payload);
                    setActiveIndex(0);
                }
            } catch (error) {
                console.warn('Live sales toast fetch failed', error);
            }
        };

        fetchItems();
        const refresh = window.setInterval(fetchItems, 300000);

        return () => window.clearInterval(refresh);
    }, [enabled]);

    useEffect(() => {
        if (!enabled || items.length <= 1) {
            return undefined;
        }

        const interval = window.setInterval(() => {
            setActiveIndex((current) => (current + 1) % items.length);
        }, 6000);

        return () => window.clearInterval(interval);
    }, [enabled, items]);

    if (!enabled || !activeItem) {
        return null;
    }

    return (
        <aside className="live-sales-toast">
            <img src={activeItem.image || '/assets/logo/favicon.webp'} alt={activeItem.item} />
            <div>
                <small>Baru saja membeli</small>
                <strong>{activeItem.item}</strong>
                <span>{activeItem.name} · {activeItem.time_ago}</span>
            </div>
        </aside>
    );
}

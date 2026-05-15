import React, { useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { createPortal } from 'react-dom';

export default function HomepagePopup({ popup, enabled = true }) {
    const { theme } = usePage().props;
    const [open, setOpen] = useState(false);
    const [mounted, setMounted] = useState(false);
    const [imageFailed, setImageFailed] = useState(false);
    const popupThemeClass = theme?.key === 'bangjeff' ? 'homepage-popup--bangjeff' : '';
    const popupTitle = String(popup?.title || '').trim();
    const popupDescription = String(popup?.description || '').trim();
    const popupDescriptionText = popupDescription
        .replace(/<br\s*\/?>/gi, ' ')
        .replace(/<[^>]+>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    const canShowImage = Boolean(popup?.image) && !imageFailed;
    const hasBodyContent = popupTitle.length > 0 || popupDescriptionText.length > 0;
    const hasRenderableContent = canShowImage || hasBodyContent;

    const portalRoot = useMemo(() => {
        if (typeof document === 'undefined') {
            return null;
        }

        let root = document.getElementById('headlessui-portal-root');

        if (!root) {
            root = document.createElement('div');
            root.id = 'headlessui-portal-root';
            document.body.appendChild(root);
        }

        return root;
    }, []);

    useEffect(() => {
        setMounted(true);
    }, []);

    useEffect(() => {
        setImageFailed(false);
    }, [popup?.id, popup?.image]);

    useEffect(() => {
        if (!enabled || !popup) {
            return undefined;
        }

        const timer = window.setTimeout(() => setOpen(true), 2200);
        return () => window.clearTimeout(timer);
    }, [enabled, popup]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const previousOverflow = document.body.style.overflow;
        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', handleEscape);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', handleEscape);
        };
    }, [open]);

    useEffect(() => {
        if (!open || hasRenderableContent) {
            return;
        }

        setOpen(false);
    }, [hasRenderableContent, open]);

    if (!enabled || !popup || !open || !mounted || !portalRoot || !hasRenderableContent) {
        return null;
    }

    return createPortal(
        <div className="homepage-popup" aria-hidden={open ? 'false' : 'true'}>
            <div className="homepage-popup__backdrop" onClick={() => setOpen(false)} />
            <div className="homepage-popup__viewport">
                <div
                    className={`homepage-popup__panel ${popupThemeClass}`.trim()}
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="homepage-popup-title"
                >
                    <button type="button" className="homepage-popup__close" onClick={() => setOpen(false)} aria-label="Tutup popup">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                            <path d="M18 6L6 18" />
                            <path d="M6 6L18 18" />
                        </svg>
                    </button>
                    {canShowImage ? (
                        <div className="homepage-popup__media">
                            <img src={popup.image} alt={popupTitle || 'Popup'} onError={() => setImageFailed(true)} />
                        </div>
                    ) : null}
                    {hasBodyContent ? (
                        <div className="homepage-popup__body">
                            <h3 id="homepage-popup-title">{popupTitle || 'Info Penting'}</h3>
                            {popupDescription ? <div dangerouslySetInnerHTML={{ __html: popupDescription }} /> : null}
                        </div>
                    ) : null}
                </div>
            </div>
        </div>,
        portalRoot,
    );
}

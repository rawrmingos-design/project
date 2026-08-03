import React, { useEffect, useMemo, useState, useRef, useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import { createPortal } from 'react-dom';

export default function HomepagePopup({ popup, enabled = true }) {
    const { theme } = usePage().props;
    const [open, setOpen] = useState(false);
    const [mounted, setMounted] = useState(false);
    const [imageFailed, setImageFailed] = useState(false);
    const popupRef = useRef(null);
    const previousFocusRef = useRef(null);

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

    const storageKey = popup?.id ? `hidePopup_${popup.id}` : 'hidePopup_default';

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

    const handleClose = useCallback(() => {
        setOpen(false);
        try {
            if (window.localStorage) {
                window.localStorage.setItem(storageKey, 'true');
            }
        } catch (e) {
            // Ignore quota/private mode errors
        }
    }, [storageKey]);

    useEffect(() => {
        if (!enabled || !popup) {
            return undefined;
        }

        let isHidden = false;
        try {
            if (window.localStorage && window.localStorage.getItem(storageKey) === 'true') {
                isHidden = true;
            }
        } catch (e) {
            // Ignore quota/private mode errors
        }

        if (isHidden) {
            return undefined;
        }

        const timer = window.setTimeout(() => setOpen(true), 500);
        return () => window.clearTimeout(timer);
    }, [enabled, popup, storageKey]);

    useEffect(() => {
        if (!open) {
            if (previousFocusRef.current && typeof previousFocusRef.current.focus === 'function') {
                previousFocusRef.current.focus();
                previousFocusRef.current = null;
            }
            return undefined;
        }

        previousFocusRef.current = document.activeElement;

        const previousOverflow = document.body.style.overflow;

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                handleClose();
                return;
            }

            if (event.key === 'Tab') {
                if (!popupRef.current) return;

                const focusableElements = popupRef.current.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );

                if (focusableElements.length === 0) {
                    event.preventDefault();
                    return;
                }

                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];

                if (event.shiftKey && document.activeElement === firstElement) {
                    lastElement.focus();
                    event.preventDefault();
                } else if (!event.shiftKey && document.activeElement === lastElement) {
                    firstElement.focus();
                    event.preventDefault();
                }
            }
        };

        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', handleKeyDown);

        if (popupRef.current) {
            const focusableElements = popupRef.current.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            } else {
                popupRef.current.focus();
            }
        }

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [open, handleClose]);

    useEffect(() => {
        if (!open || hasRenderableContent) {
            return;
        }

        setOpen(false);
    }, [hasRenderableContent, open]);

    if (!enabled || !popup || !open || !mounted || !portalRoot || !hasRenderableContent) {
        return null;
    }

    const modalAriaProps = {
        role: "dialog",
        "aria-modal": "true",
        "aria-hidden": open ? 'false' : 'true'
    };

    if (hasBodyContent) {
        modalAriaProps["aria-labelledby"] = "homepage-popup-title";
    } else {
        modalAriaProps["aria-label"] = "Informasi Promo";
    }

    return createPortal(
        <div className="homepage-popup" {...modalAriaProps}>
            <div className="homepage-popup__backdrop" onClick={handleClose} />
            <div className="homepage-popup__viewport">
                <div
                    className={`homepage-popup__panel ${popupThemeClass}`.trim()}
                    ref={popupRef}
                    tabIndex="-1"
                >
                    <button type="button" className="homepage-popup__close" onClick={handleClose} aria-label="Tutup popup">
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

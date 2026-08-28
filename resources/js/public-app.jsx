import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import './bootstrap';
createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./public/Pages/**/*.jsx', { eager: true });
        return pages[`./public/Pages/${name}.jsx`];
    },
    setup({ el, App, props }) {
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            window.__istPwaInstallPrompt = event;
        });
        const root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: {
        color: '#f59e0b',
    },
});

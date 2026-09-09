import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import './bootstrap';
createInertiaApp({
    resolve: async (name) => {
        const pages = import.meta.glob('./public/Pages/**/*.jsx');
        const page = pages[`./public/Pages/${name}.jsx`];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return page();
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

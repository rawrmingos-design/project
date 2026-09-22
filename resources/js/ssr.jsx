import React from 'react';
import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';

createServer((page) => createInertiaApp({
    page,
    render: ReactDOMServer.renderToString,
    title: (title) => (title ? `${title}` : 'ISTANATOPUP'),
    resolve: async (name) => {
        const pages = import.meta.glob('./public/Pages/**/*.jsx');
        const page = pages[`./public/Pages/${name}.jsx`];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return page();
    },
    setup: ({ App, props }) => <App {...props} />,
}));

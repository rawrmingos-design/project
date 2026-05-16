import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/public-app.css', 'resources/js/public-app.jsx'],
            refresh: true,
        }),
        react(),
    ],
});

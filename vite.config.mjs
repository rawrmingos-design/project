import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // CSS didaftarkan sebagai entry tersendiri supaya bisa di-link
                // langsung dari <head>. Kalau hanya di-import dari JS, HTML SSR
                // sempat tampil tanpa stylesheet (FOUC / halaman terlihat rusak).
                'resources/css/public-app.css',
                'resources/css/public-theme-istanatopup.css',
                'resources/js/public-app.jsx',
                'resources/js/realtime.js',
            ],
            ssr: 'resources/js/ssr.jsx',
            refresh: true,
        }),
        react(),
    ],
    ssr: {
        // Bundle dependensi runtime (react, react-dom/server, @inertiajs) ke
        // dalam bundle SSR supaya server render tidak butuh node_modules di
        // image produksi.
        noExternal: true,
    },
});

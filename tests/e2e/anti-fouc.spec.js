const { test, expect } = require('@playwright/test');

/**
 * Bukti tidak ada lagi FOUC ("halaman terlihat rusak" sesaat):
 * HTML awal harus membawa <link rel="stylesheet"> untuk CSS aplikasi, dan
 * splash anti-FOUC harus ada lalu hilang setelah React siap.
 *
 * Sebelum perbaikan, CSS hanya di-import dari JS sehingga HTML awal dirender
 * tanpa stylesheet sama sekali (hanya <script type="module">).
 */

function cssLinksFromHtml(html) {
    const links = [];
    const re = /<link[^>]*>/gi;
    let match;

    while ((match = re.exec(html)) !== null) {
        const tag = match[0];
        if (/rel=["']stylesheet["']/i.test(tag) && /\.css/i.test(tag)) {
            links.push(tag);
        }
    }

    return links;
}

test.describe('anti-FOUC: CSS & splash di HTML awal', () => {
    test('HTML awal membawa stylesheet aplikasi (Tailwind/theme)', async ({ request }) => {
        const response = await request.get('/id', {
            headers: { Accept: 'text/html' },
            failOnStatusCode: false,
        });
        const html = await response.text();
        const css = cssLinksFromHtml(html);

        // Harus ada CSS hasil build Vite (bukan hanya font/CDN/vendor legacy).
        const viteCss = css.filter((tag) => /\/build\/assets\/[^"]*\.css/i.test(tag));
        expect(viteCss.length, `CSS Vite tidak di-link. link ditemukan: ${JSON.stringify(css)}`)
            .toBeGreaterThan(0);

        // Tailwind (public-app.css) harus hadir supaya layout tidak "rusak".
        expect(
            viteCss.some((tag) => /public-app-[A-Za-z0-9_-]+\.css/.test(tag)),
            'public-app.css (Tailwind) tidak ada di HTML awal'
        ).toBe(true);
    });

    test('CSS ter-link di <head> tanpa menunggu JavaScript', async ({ request }) => {
        const response = await request.get('/id', {
            headers: { Accept: 'text/html' },
            failOnStatusCode: false,
        });
        const html = await response.text();

        const head = html.split('</head>')[0] ?? '';
        const headCss = cssLinksFromHtml(head).filter((tag) => /\/build\/assets\/[^"]*\.css/i.test(tag));

        expect(headCss.length, 'Stylesheet Vite harus berada di dalam <head>').toBeGreaterThan(0);
    });

    test('splash anti-FOUC ada di HTML awal dan punya jaring pengaman', async ({ request }) => {
        const response = await request.get('/id', {
            headers: { Accept: 'text/html' },
            failOnStatusCode: false,
        });
        const html = await response.text();

        expect(html).toContain('ist-boot-splash');
        // Splash harus di-hide otomatis; tanpa timer ini halaman bisa tertutup selamanya.
        expect(html).toMatch(/setTimeout\(hideBootSplash/);
    });

    test('splash hilang setelah halaman dimuat di browser', async ({ page }) => {
        await page.goto('/id', { waitUntil: 'domcontentloaded' });

        // Splash boleh tampil sesaat, tapi tidak boleh permanen.
        const splash = page.locator('#ist-boot-splash');
        await expect(splash).toBeHidden({ timeout: 15000 });
    });
});

const { test, expect } = require('@playwright/test');

/**
 * Bukti bahwa tema Inertia mengirim konten ke HTML awal (server-side), bukan
 * hanya shell kosong yang diisi JavaScript. Uji ini membaca HTML mentah
 * (tanpa eksekusi JS) memakai request context.
 */

async function rawHtml(request, path) {
    const response = await request.get(path);
    expect(response.ok()).toBeTruthy();
    return response.text();
}

function visibleText(html) {
    return html
        .replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi, ' ')
        .replace(/<[^>]+>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

test.describe('SSR content in initial HTML', () => {
    test('homepage ships storefront content in the raw HTML', async ({ request }) => {
        const html = await rawHtml(request, '/id');
        const text = visibleText(html);

        // Shell kosong sebelum SSR hanya berisi puluhan karakter.
        expect(text.length).toBeGreaterThan(1000);

        // Nama game dari fixture E2E harus sudah ada tanpa menunggu JS.
        expect(text).toContain('E2E Game');
    });

    test('order page ships product and price content in the raw HTML', async ({ request }) => {
        const html = await rawHtml(request, '/id/e2e-game');
        const text = visibleText(html);

        expect(text.length).toBeGreaterThan(1000);
        expect(text).toMatch(/E2E Game/i);
        // Harga fixture (Rp) harus terlihat crawler tanpa JS.
        expect(text).toContain('Rp');
    });

    test('raw HTML keeps exactly one of each critical SEO tag', async ({ request }) => {
        const html = await rawHtml(request, '/id');

        const counts = {
            title: (html.match(/<title\b/gi) || []).length,
            canonical: (html.match(/rel="canonical"/gi) || []).length,
            description: (html.match(/name="description"/gi) || []).length,
            robots: (html.match(/name="robots"/gi) || []).length,
            jsonLd: (html.match(/application\/ld\+json/gi) || []).length,
        };

        expect(counts.title).toBe(1);
        expect(counts.canonical).toBe(1);
        expect(counts.description).toBe(1);
        expect(counts.robots).toBe(1);
        expect(counts.jsonLd).toBe(1);
    });

    test('raw HTML is server-rendered, not an empty client-only shell', async ({ request }) => {
        const html = await rawHtml(request, '/id');

        // Inertia menandai body hasil SSR dengan data-server-rendered; shell
        // client-only hanya berisi <div id="app"></div> kosong.
        expect(html).toContain('data-server-rendered="true"');

        // Ukur seluruh region aplikasi (marker sampai </body>), bukan jendela
        // panjang tetap: layout tema berbeda-beda sehingga offset tetap tidak
        // bisa dibandingkan antar tema.
        const appStart = html.indexOf('data-server-rendered="true"');
        const appEnd = html.indexOf('</body>', appStart);
        const appMarkup = html.slice(appStart, appEnd === -1 ? undefined : appEnd);

        // Markup awal harus berisi konten nyata, bukan wadah kosong.
        expect(visibleText(appMarkup).length).toBeGreaterThan(500);
    });
});

const { test, expect } = require('@playwright/test');

const ARTICLE_SLUG = 'e2e-long-sticky-sidebar';

/**
 * Sidebar "Baca Juga" harus "nyangkut" saat halaman di-scroll.
 *
 * Akar masalah yang dijaga spec ini:
 *   `.public-article-related` sudah `position: sticky`, TAPI induknya
 *   (`.public-article-detail-sidebar`, anak grid) hanya setinggi isinya karena
 *   layout grid memakai `align-items: start`. Elemen sticky tanpa ruang gerak
 *   ikut ter-scroll 1:1 — terukur di staging: top 225 -> 25 -> -175 saat
 *   scrollY 0 -> 200 -> 400.
 *
 * Perbaikannya `align-self: stretch` pada kolom sidebar (tema Blade `default`
 * sudah punya ini; tema React/Inertia yang tertinggal). Spec ini mengukur
 * POSISI di browser sungguhan, karena bugnya "CSS-nya ada tapi tidak bekerja".
 */
test.describe('Sidebar "Baca Juga" sticky di single artikel', () => {
    test('sidebar menahan posisi saat halaman di-scroll', async ({ page }) => {
        await page.goto(`/id/artikel/${ARTICLE_SLUG}`);

        const related = page.locator('.public-article-related');
        await expect(related).toBeVisible();

        // Prasyarat: kolom konten harus jauh lebih tinggi daripada sidebar,
        // kalau tidak elemen sticky memang tidak punya ruang gerak dan hasil
        // pengukuran scroll di bawah tidak bermakna.
        const geometry = await page.locator('.public-article-detail-layout').evaluate((layout) => {
            const aside = layout.querySelector('.public-article-detail-sidebar');

            return {
                layoutHeight: Math.round(layout.getBoundingClientRect().height),
                asideHeight: Math.round(aside.getBoundingClientRect().height),
            };
        });

        expect(geometry.layoutHeight).toBeGreaterThan(geometry.asideHeight + 300);

        const samples = await related.evaluate(async (el) => {
            const out = [];

            for (const y of [0, 300, 600, 900]) {
                window.scrollTo(0, y);
                await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
                out.push({
                    scrollY: Math.round(window.scrollY),
                    top: Math.round(el.getBoundingClientRect().top),
                });
            }

            window.scrollTo(0, 0);

            return out;
        });

        const [, first, second, third] = samples;

        expect(second.scrollY).toBeGreaterThan(0);

        // Selama masih di area sticky, posisi harus BERTAHAN (bukan ikut turun).
        expect(Math.abs(second.top - first.top)).toBeLessThanOrEqual(2);
        expect(Math.abs(third.top - second.top)).toBeLessThanOrEqual(2);

        // Dan memang "nyangkut" di atas viewport, bukan kebetulan sama.
        expect(third.top).toBeLessThan(first.scrollY);
    });
});

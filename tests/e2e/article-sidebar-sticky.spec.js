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
 *
 * Titik sampling dihitung dari geometri halaman (bukan angka mati) supaya
 * selalu jatuh DI DALAM rentang sticky — di luar rentang itu, elemen memang
 * boleh lepas dan pengukurannya jadi tidak bermakna.
 */
test.describe('Sidebar "Baca Juga" sticky di single artikel', () => {
    test('sidebar menahan posisi saat halaman di-scroll', async ({ page }) => {
        await page.goto(`/id/artikel/${ARTICLE_SLUG}`);

        const related = page.locator('.public-article-related');
        await expect(related).toBeVisible();

        const result = await related.evaluate(async (el) => {
            const aside = el.closest('.public-article-detail-sidebar');
            const main = document.querySelector('.public-article-detail-main');
            const stickyTop = parseFloat(getComputedStyle(el).top) || 0;

            const asideRect = aside.getBoundingClientRect();
            const relatedHeight = el.getBoundingClientRect().height;
            const mainHeight = main ? main.getBoundingClientRect().height : 0;

            // Scroll saat elemen mulai menempel.
            const stickStart = Math.max(0, Math.round(asideRect.top - stickyTop));

            // Scroll terjauh saat elemen masih boleh menempel: sampai dasar
            // kontaining bloknya (aside) lewat.
            const stickEnd = Math.round(asideRect.top + asideRect.height - relatedHeight - stickyTop);

            const sampleAt = async (y) => {
                window.scrollTo(0, y);
                await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

                return {
                    scrollY: Math.round(window.scrollY),
                    top: Math.round(el.getBoundingClientRect().top),
                };
            };

            const atRest = await sampleAt(0);
            const inside = [];

            // Tiga titik yang dijamin berada di tengah rentang sticky.
            for (const ratio of [0.25, 0.5, 0.75]) {
                inside.push(await sampleAt(Math.round(stickStart + (stickEnd - stickStart) * ratio)));
            }

            window.scrollTo(0, 0);

            return {
                stickyTop: Math.round(stickyTop),
                relatedHeight: Math.round(relatedHeight),
                asideHeight: Math.round(asideRect.height),
                mainHeight: Math.round(mainHeight),
                stickStart,
                stickEnd,
                atRest,
                inside,
            };
        });

        // Prasyarat: kolom konten jauh lebih tinggi daripada sidebar, kalau tidak
        // elemen sticky memang tidak punya ruang gerak dan hasilnya tidak bermakna.
        expect(result.mainHeight).toBeGreaterThan(result.relatedHeight + 300);

        // Dan harus ada rentang sticky yang cukup lebar untuk diukur.
        expect(result.stickEnd - result.stickStart).toBeGreaterThan(200);

        // Benar-benar ter-scroll, bukan permintaan scroll yang diabaikan.
        result.inside.forEach((sample) => {
            expect(sample.scrollY).toBeGreaterThan(0);
        });

        // Elemen terangkat dari posisi istirahatnya...
        expect(result.inside[0].top).toBeLessThan(result.atRest.top);

        // ...lalu BERTAHAN di ambang sticky (bukan ikut turun 1:1 bersama halaman).
        result.inside.forEach((sample) => {
            expect(Math.abs(sample.top - result.stickyTop)).toBeLessThanOrEqual(2);
        });
    });
});

const { test, expect } = require('@playwright/test');

/**
 * Geometri kartu artikel di halaman daftar (theme legacy `default`).
 *
 * Keluhan client: "card pada list artikelnya kurang rapi, mungkin dari segi
 * gambarnya, jangan sampai kena crop tapi tetap sesuai patternnya".
 *
 * Akar masalah yang diukur spec ini:
 *   Theme legacy TIDAK menjalankan build Tailwind. Markup memakai kelas
 *   Tailwind sedangkan asetnya 5 stylesheet portabel + blok <style> inline,
 *   sehingga kelas yang tidak ada KALAH DIAM-DIAM. Di produksi terukur:
 *     - `aspect-[16/9]` tidak ada -> bingkai `aspect-ratio: auto`, tingginya
 *       mengikuti rasio ASLI file. Thumbnail 515x916 (0,56) sampai 1600x640
 *       (2,50); tinggi kotak gambar melompat 153px -> 680px dan tinggi kartu
 *       457 / 742 / 894 px.
 *     - `from-black` + `opacity-60` tidak ada -> gradasi gelap tidak dirender
 *       (background-image: none) padahal tanggal/views berwarna putih.
 *     - `bg-cover` + `bg-center` tidak ada pada hero FEATURED ->
 *       background-size: auto, position 0% 0%.
 *
 * Spec ini mengukur hasil render di BROWSER SUNGGUHAN (computed style + posisi
 * layout), bukan sekadar keberadaan markup — karena bugnya justru "markup
 * benar tapi CSS-nya tidak ada".
 */
test.describe('Geometri kartu artikel (theme legacy)', () => {
    test('semua kotak gambar punya rasio seragam dan tinggi kartu sejajar', async ({ page }) => {
        await page.goto('/id/artikel');

        const grid = page.locator('.legacy-article-grid');
        await expect(grid).toBeVisible();

        // Pastikan lazy-load sudah jalan sebelum mengukur.
        await page.evaluate(async () => {
            document.querySelectorAll('.legacy-article-grid img').forEach((img) => {
                img.loading = 'eager';
            });
            window.scrollTo(0, document.body.scrollHeight);
            await new Promise((r) => setTimeout(r, 600));
            window.scrollTo(0, 0);
            await new Promise((r) => setTimeout(r, 400));
        });

        const metrics = await grid.evaluate((el) => {
            const cards = [...el.children];
            const media = cards.map((c) => c.querySelector('.legacy-article-card__media'));
            return {
                cardCount: cards.length,
                mediaHeights: media.map((m) => Math.round(m.getBoundingClientRect().height)),
                mediaRatios: media.map((m) => {
                    const r = m.getBoundingClientRect();
                    return +(r.width / r.height).toFixed(2);
                }),
                cardHeights: cards.map((c) => Math.round(c.getBoundingClientRect().height)),
                overflow: media.map((m) => getComputedStyle(m).overflow),
                aspectRatio: media.map((m) => getComputedStyle(m).aspectRatio),
            };
        });

        expect(metrics.cardCount, 'grid artikel tidak berisi kartu').toBeGreaterThan(0);

        // --- 1. bingkai dipatok 16:9, bukan mengikuti rasio asli file ---
        for (const ratio of metrics.mediaRatios) {
            expect(
                Math.abs(ratio - 16 / 9),
                `kotak gambar tidak berasio 16:9 (terukur ${ratio}) — tingginya masih mengikuti rasio asli file`,
            ).toBeLessThan(0.06);
        }

        // --- 2. tidak ada `aspect-ratio: auto` ---
        for (const value of metrics.aspectRatio) {
            expect(value, 'bingkai gambar tidak punya rasio tetap').not.toBe('auto');
        }

        // --- 3. gambar tidak boleh luber keluar bingkai ---
        for (const value of metrics.overflow) {
            expect(value, 'bingkai gambar tidak memotong luberan').toBe('hidden');
        }

        // --- 4. tinggi kotak gambar tidak lagi melompat-lompat ---
        // Sebelum diperbaiki selisihnya mencapai 527px (153 -> 680).
        const possibleHeights = new Set(metrics.mediaHeights);
        expect(
            possibleHeights.size,
            `tinggi kotak gambar masih beragam (${metrics.mediaHeights.join(', ')}) sehingga grid bergerigi`,
        ).toBe(1);

        // --- 5. semua kartu dalam satu baris sama tinggi ---
        // Baris berisi 3 kartu pada layar lebar. Tinggi kartu sebelumnya
        // 457 / 742 / 894 px sehingga tidak ada yang sejajar.
        const maxCard = Math.max(...metrics.cardHeights);
        const minCard = Math.min(...metrics.cardHeights);
        expect(
            maxCard - minCard,
            `tinggi kartu tidak seragam (${metrics.cardHeights.join(', ')})`,
        ).toBeLessThanOrEqual(2);
    });

    test('gambar lanskap memenuhi bingkai, gambar potret tampil utuh tanpa terpotong', async ({ page }) => {
        await page.goto('/id/artikel');

        const grid = page.locator('.legacy-article-grid');
        await expect(grid).toBeVisible();

        await page.evaluate(async () => {
            document.querySelectorAll('.legacy-article-grid img').forEach((img) => {
                img.loading = 'eager';
            });
            window.scrollTo(0, document.body.scrollHeight);
            await new Promise((r) => setTimeout(r, 600));
            window.scrollTo(0, 0);
            await new Promise((r) => setTimeout(r, 400));
        });

        const rows = await grid.evaluate((el) => {
            return [...el.querySelectorAll('.legacy-article-card__media img')].map((img) => {
                const cs = getComputedStyle(img);
                const natural = img.naturalWidth / img.naturalHeight;
                return {
                    src: decodeURIComponent((img.currentSrc || img.src || '').split('/').pop() || ''),
                    marked: img.getAttribute('data-fit'),
                    objectFit: cs.objectFit,
                    naturalRatio: +natural.toFixed(2),
                };
            });
        });

        expect(rows.length, 'tidak ada gambar kartu yang bisa diukur').toBeGreaterThan(0);

        let containSeen = 0;
        let coverSeen = 0;

        for (const row of rows) {
            // Penanda dari komponen harus konsisten dengan object-fit yang benar.
            if (row.marked === 'contain') {
                containSeen += 1;
                expect(
                    row.objectFit,
                    `gambar ${row.src} ditandai contain tapi dirender '${row.objectFit}' — akan terpotong`,
                ).toBe('contain');
                // contain hanya untuk gambar yang memang lebih tinggi dari bingkai
                expect(
                    row.naturalRatio,
                    `gambar ${row.src} (rasio ${row.naturalRatio}) tidak perlu contain`,
                ).toBeLessThan(1.0);
            } else {
                coverSeen += 1;
                expect(
                    row.objectFit,
                    `gambar ${row.src} tidak memakai cover sehingga menyisakan celah di bingkai`,
                ).toBe('cover');
            }
        }

        // Bukti bahwa kedua jalur benar-benar terpakai pada data seeder —
        // kalau hanya satu yang muncul, berarti deteksi rasio tidak bekerja.
        expect(coverSeen, 'tidak ada gambar lanskap yang diuji').toBeGreaterThan(0);
        expect(
            containSeen,
            'tidak ada gambar potret yang ditandai contain — deteksi rasio mungkin tidak jalan',
        ).toBeGreaterThan(0);
    });

    test('gradasi gelap di bawah gambar benar-benar dirender', async ({ page }) => {
        await page.goto('/id/artikel');

        const scrim = page.locator('.legacy-article-card__scrim').first();
        await expect(scrim).toBeAttached();

        const style = await scrim.evaluate((el) => {
            const cs = getComputedStyle(el);
            return {
                backgroundImage: cs.backgroundImage,
                position: cs.position,
                opacity: Number(cs.opacity),
                // gradasi harus benar-benar menutupi kotak gambar
                covers: (() => {
                    const media = el.parentElement.getBoundingClientRect();
                    const box = el.getBoundingClientRect();
                    return Math.abs(media.height - box.height) < 2 && Math.abs(media.width - box.width) < 2;
                })(),
            };
        });

        // Sebelum diperbaiki nilainya `none` — gradient-nya tidak pernah dirender.
        expect(style.backgroundImage, 'gradasi gelap tidak dirender').toContain('linear-gradient');
        expect(style.position, 'gradasi tidak menempel di atas gambar').toBe('absolute');
        expect(style.opacity, 'gradasi tidak terlihat').toBeGreaterThan(0);
        expect(style.covers, 'gradasi tidak menutupi seluruh kotak gambar').toBe(true);
    });

    test('gambar unggulan memenuhi kotaknya, bukan mentok di sudut', async ({ page }) => {
        await page.goto('/id/artikel');

        const featured = page.locator('.legacy-featured-media').first();

        // Hero hanya dirender kalau ada artikel unggulan; kalau tidak ada,
        // lewati supaya spec tetap relevan.
        if ((await featured.count()) === 0) {
            test.skip();
            return;
        }

        await expect(featured).toBeAttached();

        const style = await featured.evaluate((el) => {
            const cs = getComputedStyle(el);
            return {
                backgroundSize: cs.backgroundSize,
                backgroundPosition: cs.backgroundPosition,
                backgroundImage: cs.backgroundImage,
            };
        });

        expect(style.backgroundImage, 'gambar unggulan tidak diset').toContain('url(');
        expect(style.backgroundSize, 'gambar unggulan tidak dipaksa memenuhi kotak').toBe('cover');
        // `center` menjadi `50% 50%` saat dihitung.
        expect(
            ['center', '50% 50%'],
            `gambar unggulan tidak diposisikan di tengah (terukur ${style.backgroundPosition})`,
        ).toContain(style.backgroundPosition);
    });
});

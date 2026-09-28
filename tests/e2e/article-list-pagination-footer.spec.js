const { test, expect } = require('@playwright/test');

/**
 * Pagination + footer halaman daftar artikel (theme legacy `default`).
 *
 * Keluhan client di produksi:
 *   1. Tombol previous/next "kurang rapi" — label memuat entitas panah
 *      (`&laquo;`/`&raquo;`) padahal view sudah merender ikon panah SVG,
 *      sehingga tombol menampilkan PANAH DOBEL. Teksnya juga berbahasa
 *      Inggris pada situs berbahasa Indonesia.
 *   2. Bagian footer tidak ada sama sekali; halaman berakhir tepat di bawah
 *      pagination.
 *
 * Spec ini mengukur hasil render di BROWSER SUNGGUHAN (computed style +
 * posisi layout), bukan sekadar keberadaan markup.
 *
 * Catatan locale: `LanguageDetectMiddleware` menetapkan bahasa dari header
 * `Accept-Language`, jadi tiap skenario menetapkan locale-nya eksplisit
 * (`test.use({ locale })`) alih-alih mengandalkan default browser.
 */
test.describe('Pagination & footer halaman daftar artikel (theme legacy)', () => {
    test('tombol prev/next punya satu panah dan tidak menempel di ujung halaman', async ({ page }) => {
        await page.goto('/id/artikel');

        const nav = page.locator('nav.legacy-pagination');
        await expect(nav).toBeVisible();

        const prev = nav.locator('.legacy-pagination__item').first();
        const next = nav.locator('.legacy-pagination__item').last();

        // --- 1. tidak ada panah teks di samping ikon ---
        const navText = await nav.innerText();
        for (const entity of ['«', '»', '&laquo;', '&raquo;']) {
            expect(navText, 'tombol menampilkan panah dobel').not.toContain(entity);
        }

        // tiap tombol nav harus punya tepat satu ikon SVG
        await expect(prev.locator('svg')).toHaveCount(1);
        await expect(next.locator('svg')).toHaveCount(1);

        // --- 2. pagination tidak boleh menempel di ujung dokumen ---
        const gapBelow = await nav.evaluate((el) => {
            const bottom = el.getBoundingClientRect().bottom + window.scrollY;
            return Math.round(document.documentElement.scrollHeight - bottom);
        });
        expect(gapBelow, 'pagination menempel di ujung halaman tanpa jarak').toBeGreaterThan(0);
    });

    test('halaman daftar artikel merender footer', async ({ page }) => {
        await page.goto('/id/artikel');

        const footer = page.locator('footer').first();
        await expect(footer).toBeAttached();

        // Footer harus ada di bawah pagination, bukan menggantikannya.
        const boxes = await page.evaluate(() => {
            const pag = document.querySelector('nav.legacy-pagination');
            const foot = document.querySelector('footer');
            if (!pag || !foot) return null;
            return {
                pagBottom: pag.getBoundingClientRect().bottom + window.scrollY,
                footTop: foot.getBoundingClientRect().top + window.scrollY,
            };
        });

        expect(boxes, 'pagination atau footer tidak ditemukan').not.toBeNull();
        expect(boxes.footTop).toBeGreaterThanOrEqual(boxes.pagBottom);
    });

    test.describe('locale Indonesia', () => {
        test.use({ locale: 'id-ID' });

        test('ringkasan dan label berbahasa Indonesia', async ({ page }) => {
            await page.goto('/id/artikel');

            const nav = page.locator('nav.legacy-pagination');
            await expect(nav).toBeVisible();

            const summary = await nav.locator('.legacy-pagination__summary').innerText();
            expect(summary).toContain('Menampilkan');
            expect(summary).toContain('hasil');
            expect(summary).not.toContain('Showing');

            const next = nav.locator('.legacy-pagination__item').last();
            expect(await next.innerText()).toContain('Berikutnya');
        });
    });

    test.describe('locale Inggris', () => {
        test.use({ locale: 'en-US' });

        test('ringkasan dan label berbahasa Inggris tanpa entitas panah', async ({ page }) => {
            await page.goto('/id/artikel');

            const nav = page.locator('nav.legacy-pagination');
            await expect(nav).toBeVisible();

            const summary = await nav.locator('.legacy-pagination__summary').innerText();
            expect(summary).toContain('Showing');
            expect(summary).toContain('results');

            const next = nav.locator('.legacy-pagination__item').last();
            const nextText = await next.innerText();
            expect(nextText).toContain('Next');
            // Panah tetap hanya dari ikon, bukan dari label.
            expect(nextText).not.toContain('»');
        });
    });
});

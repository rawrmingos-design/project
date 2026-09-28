const { test, expect } = require('@playwright/test');

const DEFAULT_ARTICLE_SLUG = 'e2e-default-list-markers';

test.describe('Artikel layout default — marker list tidak hilang oleh preflight Tailwind', () => {
    test('bullet dan nomor tampil pada konten artikel layout default', async ({ page }) => {
        await page.goto(`/id/artikel/${DEFAULT_ARTICLE_SLUG}`);

        const ul = page.locator('.public-article-content ul').first();
        const ol = page.locator('.public-article-content ol').first();
        await expect(ul).toBeVisible();
        await expect(ol).toBeVisible();

        const ulStyle = await ul.evaluate((el) => {
            const cs = getComputedStyle(el);
            return { type: cs.listStyleType, image: cs.listStyleImage, position: cs.listStylePosition };
        });
        const olStyle = await ol.evaluate((el) => {
            const cs = getComputedStyle(el);
            return { type: cs.listStyleType };
        });

        // marker harus dikembalikan; `none` berarti preflight menang lagi
        expect(ulStyle.type).toBe('disc');
        expect(ulStyle.image).toBe('none');
        expect(olStyle.type).toBe('decimal');

        // ::marker harus benar-benar punya konten (bukan kosong karena `none`)
        const ulMarker = await ul.locator('li').first().evaluate((el) => {
            const cs = getComputedStyle(el, '::marker');
            return { content: cs.content, display: cs.display };
        });
        expect(ulMarker.content).not.toBe('none');
        expect(ulMarker.content.length).toBeGreaterThan(0);
    });
});

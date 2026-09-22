const { test, expect } = require('@playwright/test');

const ARTICLE_SLUG = 'e2e-faq-parity';

test.describe('FAQPage structured data parity', () => {
    test('article page exposes FAQPage alongside Article and BreadcrumbList', async ({ page }) => {
        await page.goto(`/id/artikel/${ARTICLE_SLUG}`);

        const schemas = await page.locator('script[type="application/ld+json"]')
            .evaluateAll((scripts) => scripts.flatMap((script) => {
                const parsed = JSON.parse(script.textContent);
                return Array.isArray(parsed) ? parsed : [parsed];
            }));

        const types = schemas.map((schema) => schema['@type']);
        expect(types).toEqual(expect.arrayContaining(['Article', 'BreadcrumbList', 'FAQPage']));

        const faq = schemas.find((schema) => schema['@type'] === 'FAQPage');
        expect(faq.mainEntity).toHaveLength(2);
        expect(faq.mainEntity[0]).toMatchObject({
            '@type': 'Question',
            name: 'Apa itu top up?',
        });
        expect(faq.mainEntity[0].acceptedAnswer).toMatchObject({
            '@type': 'Answer',
            text: 'Top up adalah pengisian ulang diamond atau voucher game.',
        });

        // Satu blok JSON-LD saja, sebagaimana kontrak app-inertia.blade.php.
        await expect(page.locator('script[type="application/ld+json"]')).toHaveCount(1);
    });

    test('article without a FAQ section omits FAQPage schema', async ({ page }) => {
        await page.goto('/id/artikel');

        const schemas = await page.locator('script[type="application/ld+json"]')
            .evaluateAll((scripts) => scripts.flatMap((script) => {
                const parsed = JSON.parse(script.textContent);
                return Array.isArray(parsed) ? parsed : [parsed];
            }));

        const types = schemas.map((schema) => schema['@type']);
        expect(types).toContain('CollectionPage');
        expect(types).not.toContain('FAQPage');
    });
});

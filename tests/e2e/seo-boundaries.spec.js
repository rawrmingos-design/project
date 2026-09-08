const { test, expect } = require('@playwright/test');

async function jsonLd(page) {
    return page.locator('script[type="application/ld+json"]').evaluateAll((scripts) => scripts.map((script) => JSON.parse(script.textContent)));
}

test.describe('SEO route boundaries', () => {
    test('homepage exposes canonical, indexable robots, and valid schema types', async ({ page }) => {
        await page.goto('/id');

        await expect(page.locator('html')).toHaveAttribute('lang', 'id');
        await expect(page.locator('meta[name="robots"]:not([data-inertia])')).toHaveAttribute('content', /index,follow/);
        await expect(page.locator('link[rel="canonical"][data-inertia]')).toHaveAttribute('href', /\/id$/);

        const schemas = await jsonLd(page);
        const types = schemas.flatMap((schema) => Array.isArray(schema) ? schema.map((item) => item['@type']) : [schema['@type']]);
        expect(types).toEqual(expect.arrayContaining(['WebSite', 'Organization', 'WebPage']));
    });

    test('public calculator remains indexable and appears in sitemap', async ({ page, request }) => {
        await page.goto('/id/calculator/winrate');
        await expect(page.locator('meta[name="robots"]:not([data-inertia])')).toHaveAttribute('content', /index,follow/);

        const sitemap = await request.get('/sitemap-main.xml');
        expect(sitemap.ok()).toBeTruthy();
        expect(await sitemap.text()).toContain('/id/calculator/winrate');
    });

    test('auth page is private and guest dashboard redirects to auth', async ({ page }) => {
        await page.goto('/id/sign-in');
        await expect(page.locator('meta[name="robots"]:not([data-inertia])')).toHaveAttribute('content', /noindex,nofollow,noarchive/);

        await page.goto('/id/dashboard');
        await expect(page).toHaveURL(/\/id\/sign-in/);
    });

    test('robots blocks private route families and advertises sitemap', async ({ request }) => {
        const response = await request.get('/robots.txt');
        expect(response.ok()).toBeTruthy();
        const body = await response.text();

        expect(body).toContain('Disallow: /id/sign-in');
        expect(body).toContain('Disallow: /id/dashboard');
        expect(body).toContain('Disallow: /id/invoices');
        expect(body).toContain('Sitemap: ');
    });
});

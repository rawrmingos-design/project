// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Public invoice detail', () => {
    test('resolves display invoice, keeps internal status key private, and omits missing media URLs', async ({ page }) => {
        const checkoutPosts = [];
        const brokenMedia = [];
        page.on('request', (request) => {
            if (request.method() === 'POST' && request.url().endsWith('/id')) {
                checkoutPosts.push(request.url());
            }
        });
        page.on('response', (response) => {
            if (response.url().includes('e2e-invoice-missing.webp') || response.url().includes('e2e-missing.webp')) {
                brokenMedia.push({ url: response.url(), status: response.status() });
            }
        });

        await page.goto('/id/invoices/E2E-INVOICE-INTERNAL-001_001', { waitUntil: 'domcontentloaded' });

        await expect(page.getByText('E2E-INVOICE-INTERNAL-001_001', { exact: true }).first()).toBeVisible();
        await expect(page.locator('meta[name="robots"][data-inertia]')).toHaveAttribute('content', 'noindex,nofollow,noarchive');
        await expect(page.locator('meta[name="robots"][data-inertia]')).toHaveCount(1);
        await expect(page.locator('link[rel="canonical"][data-inertia]')).toHaveCount(1);
        await expect(page.locator('.invoice-account-card__thumb-fallback')).toBeVisible();
        await expect(page.locator('img[src*="e2e-invoice-missing.webp"]')).toHaveCount(0);

        const pageData = JSON.parse(await page.locator('script[data-page]').textContent());
        expect(pageData.props.invoice.orderId).toBe('E2E-INVOICE-INTERNAL-001_001');
        expect(pageData.props.invoice.internalOrderId).toBe('E2E-INVOICE-INTERNAL-001');
        expect(pageData.props.invoice.thumbnail).toBeNull();
        expect(pageData.props.invoice.payment.methodImage).toBeNull();
        expect(checkoutPosts).toEqual([]);
        expect(brokenMedia).toEqual([]);
    });
});

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

    test('renders state-accurate hero copy, banner tone, and countdown chip for pending, failed, and lapsed invoices', async ({ page }) => {
        const checkoutPosts = [];
        page.on('request', (request) => {
            if (request.method() === 'POST' && request.url().endsWith('/id')) {
                checkoutPosts.push(request.url());
            }
        });

        await page.goto('/id/invoices/E2E-INVOICE-INTERNAL-001_001', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('.invoice-status-banner-react--pending')).toBeVisible();
        await expect(page.locator('.invoice-status-banner-react__title')).toHaveText('Harap lengkapi pembayaran.');
        await expect(page.locator('.invoice-countdown-chip--pending')).toHaveCount(1);

        await page.goto('/id/invoices/E2E-INVOICE-PAID-FAILED-001_001', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('.invoice-status-banner-react--failed')).toBeVisible();
        await expect(page.locator('.invoice-status-banner-react__title')).toHaveText('Pembayaran diterima, namun transaksi gagal.');
        await expect(page.locator('.invoice-countdown-chip--paid')).toHaveCount(1);

        await page.goto('/id/invoices/E2E-INVOICE-LAPSED-001_001', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('.invoice-status-banner-react--expired')).toBeVisible();
        await expect(page.locator('.invoice-status-banner-react__title')).toHaveText('Invoice sudah kedaluwarsa.');
        await expect(page.locator('.invoice-countdown-chip--expired')).toHaveCount(1);

        expect(checkoutPosts).toEqual([]);
    });

    test('renders the QRIS QR image inline through the self-hosted proxy', async ({ page }) => {
        const checkoutPosts = [];
        page.on('request', (request) => {
            if (request.method() === 'POST' && request.url().endsWith('/id')) {
                checkoutPosts.push(request.url());
            }
        });

        await page.goto('/id/invoices/E2E-INVOICE-QRIS-001_001', { waitUntil: 'domcontentloaded' });

        const qrImage = page.locator('.invoice-qr__image');
        await expect(qrImage).toBeVisible();
        await expect(page.getByRole('button', { name: /unduh kode qr/i })).toBeVisible();

        const src = (await qrImage.getAttribute('src')) ?? '';
        expect(src).toContain('/id/invoices/E2E-INVOICE-QRIS-001');
        expect(src).toContain('/payment-qr');
        expect(src).not.toContain('qrserver.com');

        await expect.poll(() => qrImage.evaluate((img) => img.complete && img.naturalWidth > 0)).toBe(true);

        expect(checkoutPosts).toEqual([]);
    });

    test('keeps the hero banner full-bleed with the istana page gutter aligned to the order shell', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('/id/invoices/E2E-INVOICE-INTERNAL-001_001', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('.invoice-status-banner-react')).toBeVisible();

        const geo = await page.evaluate(() => {
            const rect = (selector) => {
                const el = document.querySelector(selector);
                if (!el) return null;
                const r = el.getBoundingClientRect();
                return { x: Math.round(r.x), right: Math.round(r.right) };
            };
            return {
                viewportWidth: window.innerWidth,
                banner: rect('.invoice-status-banner-react'),
                page: rect('.invoice-page'),
                content: rect('.invoice-progress-section'),
                isIstana: Boolean(document.querySelector('.public-app--istanatopup')),
                scrollWidth: document.documentElement.scrollWidth,
            };
        });

        // The banner bleeds to both viewport edges (istanatopup + bangjeff paths).
        expect(geo.banner.x).toBe(0);
        expect(geo.banner.right).toBe(geo.viewportWidth);
        expect(geo.scrollWidth).toBeLessThanOrEqual(geo.viewportWidth);

        // The istana page mirrors the order shell: full-width section + 16px inner gutter.
        if (geo.isIstana) {
            expect(geo.page.x).toBe(0);
            expect(geo.page.right).toBe(geo.viewportWidth);
            expect(geo.content.x).toBe(16);
        }
    });
});

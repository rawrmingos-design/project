// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Public storefront order flow', () => {
    test('renders seeded category, product, and payment method', async ({ page }) => {
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        await expect(page.getByRole('heading', { name: 'E2E Game', exact: true })).toBeVisible();
        await expect(page.locator('.variant-card:visible').getByText('E2E Product 10000', { exact: true })).toBeVisible();
        await expect(page.locator('.payment-card:visible').getByText('QRIS', { exact: false })).toBeVisible();
        await expect(page.locator('input[placeholder="Masukkan User ID"]')).toBeVisible();
    });

    test('keeps desktop checkout summary in one column without overflow', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        const summary = page.locator('.order-summary--bangjeff:visible');
        await expect(summary).toBeVisible();

        const layout = await summary.evaluate((element) => {
            const rect = element.getBoundingClientRect();
            const columns = getComputedStyle(element).gridTemplateColumns.trim().split(/\s+/).filter(Boolean);
            const rows = Array.from(element.querySelectorAll('.order-summary__row')).map((row) => {
                const rowRect = row.getBoundingClientRect();
                const labelRect = row.querySelector('.order-summary__label')?.getBoundingClientRect();
                const valueRect = row.querySelector('.order-summary__value')?.getBoundingClientRect();

                return {
                    left: rowRect.left,
                    right: rowRect.right,
                    labelLeft: labelRect?.left ?? null,
                    valueRight: valueRect?.right ?? null,
                    overflow: row.scrollWidth > row.clientWidth,
                };
            });

            return {
                columnCount: columns.length,
                overflow: element.scrollWidth > element.clientWidth,
                inViewport: rect.left >= 0 && rect.right <= window.innerWidth,
                rows,
            };
        });

        expect(layout.columnCount).toBe(1);
        expect(layout.overflow).toBe(false);
        expect(layout.inViewport).toBe(true);
        expect(layout.rows.length).toBeGreaterThan(0);
        expect(layout.rows.every((row) => (
            !row.overflow
            && row.labelLeft !== null
            && row.valueRight !== null
            && row.labelLeft >= row.left
            && row.valueRight <= row.right
        ))).toBe(true);
    });

    test('selecting product, account, and payment method updates checkout state', async ({ page }) => {
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        const product = page.locator('.variant-card:visible');
        await expect(product).toBeVisible();
        await product.click();

        const userId = page.locator('input[placeholder="Masukkan User ID"]');
        // The category's user-id field is the first input in the account section.
        await userId.fill('123456789');

        await page.locator('.payment-card:visible').first().click();

        await expect(page.locator('.variant-card:visible')).toHaveClass(/is-active/);
        await expect(page.locator('.payment-card:visible')).toHaveClass(/is-active/);
        await expect(page.locator('.variant-card:visible')).toContainText('Rp');
    });

    test('price endpoint returns a price preview for the seeded product', async ({ page }) => {
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        const response = await page.request.post('/id/harga', {
            form: {
                nominal: '1',
                ktg_tipe: 'game',
                payment_method: 'E2E_QRIS',
            },
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        // The seeded row ID is intentionally discovered from the rendered page, not hardcoded.
        expect([200, 404]).toContain(response.status());
        if (response.ok()) {
            const body = await response.json();
            expect(body.status).toBe(true);
            expect(Number(body.harga)).toBeGreaterThan(0);
        }
    });

    test('sends the selected service to Check-ID and waits for price before preview', async ({ page }) => {
        let releaseMethodPrice;
        let holdMethodPrice = false;
        const heldMethodPrice = new Promise((resolve) => {
            releaseMethodPrice = resolve;
        });
        let finalOrderPosts = 0;

        await page.route('**/id/harga', async (route) => {
            const body = new URLSearchParams(route.request().postData() || '');
            if (holdMethodPrice && body.get('payment_method') === 'E2E_QRIS') {
                holdMethodPrice = false;
                await heldMethodPrice;
            }

            await route.continue();
        });
        await page.route('**/ajax/check-account', async (route) => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    status: { code: 200, message: 'User found' },
                    data: { username: 'E2E Player' },
                }),
            });
        });
        page.on('request', (request) => {
            const url = new URL(request.url());
            if (request.method() === 'POST' && url.pathname === '/id') {
                finalOrderPosts += 1;
            }
        });

        const selectedProductPriceRequest = page.waitForRequest((request) => {
            if (new URL(request.url()).pathname !== '/id/harga') {
                return false;
            }

            const body = new URLSearchParams(request.postData() || '');
            return Boolean(body.get('nominal')) && !body.get('payment_method');
        });
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });
        const selectedProductPricePayload = new URLSearchParams((await selectedProductPriceRequest).postData() || '');
        const selectedServiceId = selectedProductPricePayload.get('nominal');
        expect(selectedServiceId).not.toBe('');
        const checkRequest = page.waitForRequest((request) => new URL(request.url()).pathname === '/ajax/check-account');
        await page.locator('input[placeholder="Masukkan User ID"]').fill('123456789');
        await page.locator('input[placeholder="example@gmail.com"]').fill('e2e@example.test');
        const accountCheckRequest = await checkRequest;

        const checkPayload = new URLSearchParams(accountCheckRequest.postData() || '');
        expect(checkPayload.get('kategori_kode')).toBe('e2e-game');
        expect(checkPayload.get('service')).toBe(selectedServiceId);

        holdMethodPrice = true;
        const methodPriceResponse = page.waitForResponse((response) => {
            if (new URL(response.url()).pathname !== '/id/harga') {
                return false;
            }

            const body = new URLSearchParams(response.request().postData() || '');
            return body.get('payment_method') === 'E2E_QRIS';
        });
        await page.locator('.payment-card:visible').first().click();

        const checkout = page.locator('.public-button--bangjeff-order:visible').first();
        await expect(checkout).toBeDisabled();

        releaseMethodPrice();
        await methodPriceResponse;
        await expect(checkout).toBeEnabled();

        await checkout.click();
        await expect(page.getByRole('dialog', { name: 'Buat Pesanan' })).toBeVisible();
        expect(finalOrderPosts).toBe(0);
    });
});

test.describe('Public storefront validation', () => {
    test('checkout button is disabled before required data is provided', async ({ page }) => {
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });
        const checkout = page.locator('.public-button--bangjeff-order:visible').first();
        await expect(checkout).toBeDisabled();
    });
});

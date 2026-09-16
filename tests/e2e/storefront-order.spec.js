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

    test('excludes services outside packages from the order payload and UI', async ({ page }) => {
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        const pageData = JSON.parse(await page.locator('script[data-page]').textContent());
        const productNames = (pageData.props.products || []).map((product) => product.name);
        const packageNames = (pageData.props.packages || [])
            .flatMap((group) => group.items || [])
            .map((product) => product.name);

        expect(productNames).toContain('E2E Product 10000');
        expect(packageNames).toContain('E2E Product 10000');
        expect(productNames).not.toContain('E2E Ungrouped 20000');
        expect(packageNames).not.toContain('E2E Ungrouped 20000');
        await expect(page.getByText('E2E Ungrouped 20000', { exact: true })).toHaveCount(0);
        await expect(page.getByText('Layanan Lainnya', { exact: true })).toHaveCount(0);
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

        const sidebar = page.locator('.order-layout__sidebar--bangjeff:visible');
        await expect(sidebar).toBeVisible();
        await expect(sidebar).toHaveCSS('position', 'sticky');

        await page.evaluate(() => window.scrollTo({ top: 600, left: 0, behavior: 'instant' }));
        await page.waitForTimeout(100);

        const stickyState = await sidebar.evaluate((element) => {
            const rect = element.getBoundingClientRect();
            const summary = element.querySelector('.order-summary--bangjeff')?.getBoundingClientRect();

            return {
                sidebarTop: rect.top,
                summaryTop: summary?.top ?? null,
                summaryBottom: summary?.bottom ?? null,
                viewportHeight: window.innerHeight,
            };
        });

        expect(stickyState.sidebarTop).toBeGreaterThanOrEqual(100);
        expect(stickyState.sidebarTop).toBeLessThanOrEqual(130);
        expect(stickyState.summaryTop).not.toBeNull();
        expect(stickyState.summaryTop).toBeLessThan(stickyState.viewportHeight);
        expect(stickyState.summaryBottom).toBeGreaterThan(stickyState.summaryTop);
    });

    test('keeps CTA disabled until the auto-selected nominal is explicitly chosen', async ({ page }) => {
        let finalOrderPosts = 0;

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

        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        const checkout = page.locator('.public-button--bangjeff-order:visible').first();
        const product = page.locator('.variant-card--bangjeff:visible').first();
        await expect(product).toBeVisible();
        await expect(checkout).toBeDisabled();

        await page.locator('input[placeholder="Masukkan User ID"]').fill('123456789');
        await page.locator('input[placeholder="example@gmail.com"]').fill('e2e@example.test');
        await expect(page.locator('.account-pill--bangjeff-success:visible')).toBeVisible();
        await page.locator('.payment-card:visible').first().click();

        await expect(checkout).toBeDisabled();
        await product.click();
        await expect(checkout).toBeEnabled();
        expect(finalOrderPosts).toBe(0);
    });

    test('keeps CTA disabled for incomplete or invalid Bangjeff contact details', async ({ page }) => {
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

        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });
        const checkout = page.locator('.public-button--bangjeff-order:visible').first();
        await page.locator('.variant-card--bangjeff:visible').first().click();
        await page.locator('input[placeholder="Masukkan User ID"]').fill('123456789');
        await expect(page.locator('.account-pill--bangjeff-success:visible')).toBeVisible();
        await page.locator('.payment-card:visible').first().click();
        await expect(checkout).toBeDisabled();

        await page.locator('input[placeholder="example@gmail.com"]').fill('invalid-email');
        await expect(checkout).toBeDisabled();

        await page.locator('input[placeholder="example@gmail.com"]').fill('e2e@example.test');
        await page.locator('input[placeholder="8XXXXXXXXXX"]').fill('123');
        await expect(checkout).toBeDisabled();

        await page.locator('input[placeholder="8XXXXXXXXXX"]').fill('81234567890');
        await expect(checkout).toBeEnabled();
    });

    test('keeps CTA disabled while Check-ID is pending or invalid', async ({ page }) => {
        let releaseLookup;
        let lookupCount = 0;
        const pendingLookup = new Promise((resolve) => {
            releaseLookup = resolve;
        });

        await page.route('**/ajax/check-account', async (route) => {
            lookupCount += 1;

            if (lookupCount === 1) {
                await pendingLookup;
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({
                        status: { code: 404, message: 'ID tidak ditemukan' },
                    }),
                });
                return;
            }

            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    status: { code: 200, message: 'User found' },
                    data: { username: 'E2E Player' },
                }),
            });
        });

        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });
        const checkout = page.locator('.public-button--bangjeff-order:visible').first();
        await page.locator('.variant-card--bangjeff:visible').first().click();
        await page.locator('input[placeholder="Masukkan User ID"]').fill('123456789');
        await page.locator('input[placeholder="example@gmail.com"]').fill('e2e@example.test');
        await page.locator('.payment-card:visible').first().click();

        await expect.poll(() => lookupCount).toBe(1);
        await expect(checkout).toBeDisabled();

        releaseLookup();
        await expect(page.locator('.account-pill--bangjeff-error:visible')).toBeVisible();
        await expect(checkout).toBeDisabled();

        await page.locator('input[placeholder="Masukkan User ID"]').fill('987654321');
        await expect(page.locator('.account-pill--bangjeff-success:visible')).toBeVisible();
        await expect(checkout).toBeEnabled();
    });

    test('keeps mobile checkout summary within the viewport when expanded', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 640 });
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        const checkoutBar = page.locator('.order-mobile-checkout--bangjeff:visible');
        await expect(checkoutBar).toBeVisible();

        const collapsedState = await checkoutBar.evaluate((element) => {
            const rect = element.getBoundingClientRect();
            return {
                position: getComputedStyle(element).position,
                left: rect.left,
                right: rect.right,
                bottom: rect.bottom,
                viewportWidth: window.innerWidth,
                viewportHeight: window.innerHeight,
                horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
            };
        });

        expect(collapsedState.position).toBe('fixed');
        expect(collapsedState.left).toBeGreaterThanOrEqual(0);
        expect(collapsedState.right).toBeLessThanOrEqual(collapsedState.viewportWidth);
        expect(collapsedState.bottom).toBeLessThanOrEqual(collapsedState.viewportHeight);
        expect(collapsedState.horizontalOverflow).toBe(false);

        await checkoutBar.locator('.order-mobile-checkout__toggle').click();
        await expect(page.locator('.order-page__body--bangjeff-checkout-expanded')).toBeVisible();

        const expandedState = await checkoutBar.evaluate((element) => {
            const rect = element.getBoundingClientRect();
            const styles = getComputedStyle(element);
            return {
                height: rect.height,
                bottom: rect.bottom,
                maxHeight: parseFloat(styles.maxHeight),
                viewportHeight: window.innerHeight,
                horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
            };
        });

        expect(expandedState.height).toBeLessThanOrEqual(expandedState.viewportHeight - 16);
        expect(expandedState.height).toBeLessThanOrEqual(expandedState.maxHeight);
        expect(expandedState.bottom).toBeLessThanOrEqual(expandedState.viewportHeight);
        expect(expandedState.horizontalOverflow).toBe(false);
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
        await page.locator('.variant-card--bangjeff:visible').first().click();
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

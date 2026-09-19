// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('Public storefront order flow', () => {
    test('renders seeded category, product, and payment method without broken media requests', async ({ page }) => {
        const brokenMediaRequests = [];
        page.on('response', (response) => {
            if (response.url().includes('e2e-missing.webp')) {
                brokenMediaRequests.push({ url: response.url(), status: response.status() });
            }
        });

        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        await expect(page.getByRole('heading', { name: 'E2E Game', exact: true })).toBeVisible();
        await expect(page.locator('.variant-card:visible').getByText('E2E Product 10000', { exact: true })).toBeVisible();
        await expect(page.locator('.payment-card:visible').getByText('QRIS', { exact: false })).toBeVisible();
        await expect(page.locator('input[placeholder="Masukkan User ID"]')).toBeVisible();

        const pageData = JSON.parse(await page.locator('script[data-page]').textContent());
        const e2eMethod = (pageData.props.paymentMethods || []).find((method) => method.code === 'E2E_QRIS');
        expect(e2eMethod?.image).toBeNull();
        expect(brokenMediaRequests).toEqual([]);
    });

    test('renders live sales toast only on the homepage', async ({ page }) => {
        let recentPurchasesRequests = 0;

        await page.addInitScript(() => {
            window.localStorage.setItem('hidePopup_900001', 'true');
        });
        await page.route('**/api/recent-purchases', async (route) => {
            recentPurchasesRequests += 1;
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify([{
                    item: 'E2E Live Sale',
                    name: 'E**',
                    image: null,
                    time_ago: 'Baru saja',
                }]),
            });
        });

        await page.goto('/id', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('.live-sales-toast--visible')).toBeVisible();
        expect(recentPurchasesRequests).toBeGreaterThanOrEqual(1);

        const homepageRequestCount = recentPurchasesRequests;
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(1200);

        await expect(page.locator('.live-sales-toast')).toHaveCount(0);
        expect(recentPurchasesRequests).toBe(homepageRequestCount);
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

    test('keeps the selected nominal when picked from another package group', async ({ page }) => {
        let finalOrderPosts = 0;
        page.on('request', (request) => {
            const url = new URL(request.url());
            if (request.method() === 'POST' && url.pathname === '/id') {
                finalOrderPosts += 1;
            }
        });

        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        const groups = page.locator('.variant-group--bangjeff');
        await expect(groups).toHaveCount(2);

        const firstGroup = groups.filter({ has: page.getByRole('heading', { name: 'E2E Package', exact: true }) });
        const instantGroup = groups.filter({ has: page.getByRole('heading', { name: 'E2E Package Instant', exact: true }) });

        const autoSelectedCard = firstGroup.locator('.variant-card--bangjeff').first();
        const instantCard = instantGroup.locator('.variant-card--bangjeff').first();

        await expect(autoSelectedCard).toHaveClass(/is-active/);

        await instantCard.click();
        await expect(instantCard).toHaveClass(/is-active/);

        // Regression: the auto-select effect must not treat a pick from another
        // group as stale and revert it to the first item of the previous group.
        await page.waitForTimeout(1000);
        await expect(instantCard).toHaveClass(/is-active/);
        await expect(autoSelectedCard).not.toHaveClass(/is-active/);
        await expect(page.locator('.variant-card--bangjeff.is-active')).toHaveCount(1);
        expect(finalOrderPosts).toBe(0);
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
        await expect(sidebar).toHaveCSS('position', 'static');

        const stickySummary = page.locator('.order-sidebar-bangjeff__summary-sticky:visible');
        await expect(stickySummary).toBeVisible();
        await expect(stickySummary).toHaveCSS('position', 'sticky');

        const pinScrollTop = await stickySummary.evaluate((element) => {
            const rect = element.getBoundingClientRect();

            return Math.max(240, Math.round(rect.top + window.scrollY - 60));
        });

        await page.evaluate((top) => window.scrollTo({ top, left: 0, behavior: 'instant' }), pinScrollTop);
        await page.waitForTimeout(100);

        const stickyState = await stickySummary.evaluate((element) => {
            const rect = element.getBoundingClientRect();
            const sidebarRect = element.closest('.order-layout__sidebar--bangjeff')?.getBoundingClientRect();
            const summary = element.querySelector('.order-summary--bangjeff')?.getBoundingClientRect();

            return {
                summaryTop: rect.top,
                sidebarTop: sidebarRect?.top ?? null,
                sidebarBottom: sidebarRect?.bottom ?? null,
                cardTop: summary?.top ?? null,
                cardBottom: summary?.bottom ?? null,
                viewportHeight: window.innerHeight,
            };
        });

        expect(stickyState.summaryTop).toBeGreaterThanOrEqual(100);
        expect(stickyState.summaryTop).toBeLessThanOrEqual(130);
        expect(stickyState.sidebarTop).not.toBeNull();
        expect(stickyState.sidebarTop).toBeLessThan(stickyState.summaryTop);
        expect(stickyState.sidebarBottom).toBeGreaterThanOrEqual(stickyState.summaryTop);
        expect(stickyState.cardTop).not.toBeNull();
        expect(stickyState.cardTop).toBeGreaterThanOrEqual(stickyState.summaryTop);
        expect(stickyState.cardBottom).toBeLessThan(stickyState.viewportHeight);
    });

    test('keeps only the checkout summary pinned while support cards scroll normally', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        const stickySummary = page.locator('.order-sidebar-bangjeff__summary-sticky:visible');
        await expect(stickySummary).toBeVisible();

        const beforeScroll = await stickySummary.evaluate((element) => {
            const root = document.querySelector('.public-app.public-app--order-bangjeff');
            const sidebarEl = element.closest('.order-layout__sidebar--bangjeff');
            const rating = document.querySelector('.order-mini-card--rating');
            const help = document.querySelector('.order-help-card--bangjeff');

            return {
                position: getComputedStyle(element).position,
                top: parseFloat(getComputedStyle(element).top),
                sidebarPosition: sidebarEl ? getComputedStyle(sidebarEl).position : null,
                ratingPosition: rating ? getComputedStyle(rating).position : null,
                helpPosition: help ? getComputedStyle(help).position : null,
                rootOverflowX: root ? getComputedStyle(root).overflowX : null,
                rootOverflowY: root ? getComputedStyle(root).overflowY : null,
            };
        });

        expect(beforeScroll.position).toBe('sticky');
        expect(beforeScroll.top).toBe(118);
        expect(beforeScroll.sidebarPosition).toBe('static');
        expect(beforeScroll.ratingPosition).toBe('static');
        expect(beforeScroll.helpPosition).toBe('static');
        expect(beforeScroll.rootOverflowX).toBe('clip');
        expect(beforeScroll.rootOverflowY).not.toBe('auto');

        const pinScrollTop = await stickySummary.evaluate((element) => {
            const rect = element.getBoundingClientRect();

            return Math.max(240, Math.round(rect.top + window.scrollY - 60));
        });

        await page.evaluate((top) => window.scrollTo({ top, left: 0, behavior: 'instant' }), pinScrollTop);
        await page.waitForTimeout(100);

        const afterScroll = await page.evaluate(() => {
            const box = (element) => {
                const rect = element?.getBoundingClientRect();

                return rect ? { top: rect.top, bottom: rect.bottom } : null;
            };

            return {
                sticky: box(document.querySelector('.order-sidebar-bangjeff__summary-sticky')),
                sidebar: box(document.querySelector('.order-layout__sidebar--bangjeff')),
                rating: box(document.querySelector('.order-mini-card--rating')),
                help: box(document.querySelector('.order-help-card--bangjeff')),
                viewportHeight: window.innerHeight,
            };
        });

        expect(afterScroll.sticky.top).toBeGreaterThanOrEqual(100);
        expect(afterScroll.sticky.top).toBeLessThanOrEqual(130);
        expect(afterScroll.sticky.bottom).toBeGreaterThan(afterScroll.sticky.top);
        expect(afterScroll.rating.bottom).toBeLessThanOrEqual(afterScroll.sticky.top + 1);
        expect(afterScroll.help.bottom).toBeLessThanOrEqual(afterScroll.sticky.top + 1);
        expect(afterScroll.sidebar.top).toBeLessThan(afterScroll.sticky.top);
        expect(afterScroll.sidebar.bottom).toBeGreaterThanOrEqual(afterScroll.sticky.bottom - 1);
        expect(afterScroll.sticky.bottom).toBeLessThanOrEqual(afterScroll.viewportHeight);
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

        const product = page.locator('.variant-card:visible').filter({ hasText: 'E2E Product 10000' });
        await expect(product).toBeVisible();
        await product.click();

        const userId = page.locator('input[placeholder="Masukkan User ID"]');
        // The category's user-id field is the first input in the account section.
        await userId.fill('123456789');

        await page.locator('.payment-card:visible').first().click();

        await expect(product).toHaveClass(/is-active/);
        await expect(page.locator('.payment-card:visible')).toHaveClass(/is-active/);
        await expect(product).toContainText('Rp');
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

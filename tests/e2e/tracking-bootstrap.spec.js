// @ts-check
const fs = require('node:fs');
const path = require('node:path');
const { test, expect } = require('@playwright/test');

const partialPath = path.resolve(__dirname, '../../resources/views/partials/tracking-bootstrap.blade.php');
const partial = fs.readFileSync(partialPath, 'utf8');
const helperScripts = [...partial.matchAll(/<script>\s*([\s\S]*?)<\/script>/g)]
    .map((match) => match[1])
    .filter((script) => script.includes('window.pushDataLayerEvent = function'));

if (helperScripts.length !== 1) {
    throw new Error(`Expected exactly one pushDataLayerEvent script in ${partialPath}, found ${helperScripts.length}.`);
}

function productionHelper(enabled = true) {
    const script = helperScripts[0];
    const rendered = script.replace('@json($trackingGtmEnabled)', enabled ? 'true' : 'false');

    if (rendered.includes('@json($trackingGtmEnabled)')) {
        throw new Error('Failed to render tracking-enabled expression in production helper.');
    }

    return rendered;
}

async function serveHelper(page, { enabled = true, initScript } = {}) {
    if (initScript) {
        await page.addInitScript(initScript);
    }

    await page.route('https://tracking.test/**', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'text/html',
            body: `<!doctype html><html><head><meta charset="utf-8"><script>${productionHelper(enabled)}</script></head><body></body></html>`,
        });
    });

    await page.goto('https://tracking.test/', { waitUntil: 'domcontentloaded' });
}

const ecommercePayload = {
    transaction_id: 'INV-P06-001',
    ecommerce: {
        currency: 'IDR',
        value: 50000,
        items: [{ item_id: '86', item_name: '86 Diamond', quantity: 1 }],
    },
};

test.describe('Tracking bootstrap helper', () => {
    test('pushes ecommerce reset before the first event and deduplicates in memory', async ({ page }) => {
        await serveHelper(page);

        const result = await page.evaluate(({ payload }) => {
            const first = window.pushDataLayerEvent('purchase', payload, { dedupeKey: 'purchase:INV-P06-001' });
            const second = window.pushDataLayerEvent('purchase', payload, { dedupeKey: 'purchase:INV-P06-001' });

            return {
                first,
                second,
                dataLayer: window.dataLayer,
                stored: sessionStorage.getItem('gtm:purchase:INV-P06-001'),
            };
        }, { payload: ecommercePayload });

        expect(result.first).toBe(true);
        expect(result.second).toBe(false);
        expect(result.dataLayer).toHaveLength(2);
        expect(result.dataLayer[0]).toEqual({ ecommerce: null });
        expect(result.dataLayer[1]).toMatchObject({ event: 'purchase', ...ecommercePayload });
        expect(result.stored).toBe('1');
    });

    test('uses sessionStorage after memory loss and after reload', async ({ page }) => {
        await serveHelper(page);

        await page.evaluate(({ payload }) => {
            window.pushDataLayerEvent('purchase', payload, { dedupeKey: 'purchase:INV-P06-001' });
            window.__trackedTransactions = {};
        }, { payload: ecommercePayload });

        const afterMemoryLoss = await page.evaluate(({ payload }) => ({
            accepted: window.pushDataLayerEvent('purchase', payload, { dedupeKey: 'purchase:INV-P06-001' }),
            layerLength: window.dataLayer.length,
            memoryRestored: window.__trackedTransactions['purchase:INV-P06-001'] === true,
        }), { payload: ecommercePayload });

        expect(afterMemoryLoss).toEqual({ accepted: false, layerLength: 2, memoryRestored: true });

        await page.reload({ waitUntil: 'domcontentloaded' });

        const afterReload = await page.evaluate(({ payload }) => ({
            accepted: window.pushDataLayerEvent('purchase', payload, { dedupeKey: 'purchase:INV-P06-001' }),
            layerLength: window.dataLayer.length,
        }), { payload: ecommercePayload });

        expect(afterReload).toEqual({ accepted: false, layerLength: 0 });
    });

    test('fails open when storage reads throw and still deduplicates in memory', async ({ page }) => {
        await serveHelper(page, {
            initScript: () => {
                const originalGetItem = Storage.prototype.getItem;
                Storage.prototype.getItem = function getItem(key) {
                    if (String(key).startsWith('gtm:')) {
                        throw new Error('storage read unavailable');
                    }

                    return originalGetItem.call(this, key);
                };
            },
        });

        const result = await page.evaluate(({ payload }) => ({
            first: window.pushDataLayerEvent('purchase', payload, { dedupeKey: 'purchase:INV-P06-001' }),
            second: window.pushDataLayerEvent('purchase', payload, { dedupeKey: 'purchase:INV-P06-001' }),
            layerLength: window.dataLayer.length,
        }), { payload: ecommercePayload });

        expect(result).toEqual({ first: true, second: false, layerLength: 2 });
    });

    test('degrades to memory dedupe when storage writes throw', async ({ page }) => {
        await serveHelper(page, {
            initScript: () => {
                const originalSetItem = Storage.prototype.setItem;
                Storage.prototype.setItem = function setItem(key, value) {
                    if (String(key).startsWith('gtm:')) {
                        throw new Error('storage write unavailable');
                    }

                    return originalSetItem.call(this, key, value);
                };
            },
        });

        const result = await page.evaluate(({ payload }) => ({
            first: window.pushDataLayerEvent('purchase', payload, { dedupeKey: 'purchase:INV-P06-001' }),
            second: window.pushDataLayerEvent('purchase', payload, { dedupeKey: 'purchase:INV-P06-001' }),
            layerLength: window.dataLayer.length,
        }), { payload: ecommercePayload });

        expect(result).toEqual({ first: true, second: false, layerLength: 2 });
    });

    test('guards invalid input and disabled tracking without mutating dataLayer', async ({ page }) => {
        await serveHelper(page, { enabled: false });

        const disabled = await page.evaluate(() => window.pushDataLayerEvent('page_view', { page: '/id' }));
        expect(disabled).toBe(false);
        expect(await page.evaluate(() => window.dataLayer.length)).toBe(0);

        await page.evaluate(() => { window.gtmTrackingEnabled = true; });
        const guards = await page.evaluate(() => ({
            emptyName: window.pushDataLayerEvent('', { page: '/id' }),
            missingPayload: window.pushDataLayerEvent('page_view', null),
        }));
        expect(guards).toEqual({ emptyName: false, missingPayload: false });
        expect(await page.evaluate(() => window.dataLayer.length)).toBe(0);

        const missingLayer = await page.evaluate(() => {
            window.dataLayer = null;
            return window.pushDataLayerEvent('page_view', { page: '/id' });
        });
        expect(missingLayer).toBe(false);
    });

    test('pushes non-ecommerce events without a reset object', async ({ page }) => {
        await serveHelper(page);

        const result = await page.evaluate(() => ({
            accepted: window.pushDataLayerEvent('page_view', { page: '/id' }),
            dataLayer: window.dataLayer,
        }));

        expect(result.accepted).toBe(true);
        expect(result.dataLayer).toEqual([{ event: 'page_view', page: '/id' }]);
    });
});

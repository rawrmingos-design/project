// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Regression guard for the mobile order page gutters.
 *
 * The order page is a CSS grid whose first track was implicit `auto`. The hero
 * meta chips are a `white-space: nowrap` flex row (~383px intrinsic width at
 * <=640px), so the track could never shrink below that and the whole shell
 * stayed 383px wide on 320-375px viewports: the 16px right gutter got pushed
 * off-screen and the section cards looked flush/cut against the right edge
 * while the left gutter was intact (user report: "kurang space kanan kiri").
 *
 * These assertions fail on the pre-fix stylesheet and pass afterwards.
 */

const MOBILE_WIDTHS = [320, 360, 390];

async function openOrderPage(page, width) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });
    await page.locator('#order-step-nominal').waitFor({ state: 'visible', timeout: 25000 });
}

test.describe('Storefront order mobile gutters', () => {
    for (const width of MOBILE_WIDTHS) {
        test(`order sections keep a symmetric gutter at ${width}px`, async ({ page }) => {
            await openOrderPage(page, width);

            const metrics = await page.evaluate(() => {
                const viewport = window.innerWidth;
                const panels = [...document.querySelectorAll('.order-panel--bangjeff')].filter(
                    (panel) => panel.getBoundingClientRect().width > 80,
                );
                const meta = [...document.querySelectorAll('.order-hero__meta--bangjeff')].find(
                    (el) => el.getBoundingClientRect().width > 40,
                );
                let metaInfo = null;
                if (meta) {
                    const metaRect = meta.getBoundingClientRect();
                    metaInfo = {
                        // The hero meta row may scroll horizontally (bangjeff) or wrap
                        // (istanatopup) — either is fine, but the row itself must fit
                        // inside the viewport instead of pushing the shell wider.
                        right: Math.round(metaRect.right),
                        scrollable: meta.scrollWidth > meta.clientWidth + 1,
                        chips: [...meta.querySelectorAll('span')].map((span) => ({
                            label: span.textContent.trim().slice(0, 24),
                            right: Math.round(span.getBoundingClientRect().right),
                        })),
                    };
                }
                return {
                    docOverflow: document.documentElement.scrollWidth - viewport,
                    panels: panels.map((panel) => {
                        const rect = panel.getBoundingClientRect();
                        return {
                            title: ((panel.querySelector('h2') || {}).textContent || '?').trim(),
                            gapLeft: Math.round(rect.left),
                            gapRight: Math.round(viewport - rect.right),
                        };
                    }),
                    meta: metaInfo,
                    viewport,
                };
            });

            // "Masukkan Data Akun", "Pilih Nominal", "Pilih Pembayaran", "Detail Kontak", "Kode Promo"
            expect(metrics.panels.length, 'order sections rendered').toBeGreaterThanOrEqual(4);
            expect(metrics.docOverflow, 'horizontal document overflow').toBeLessThanOrEqual(1);

            for (const panel of metrics.panels) {
                expect(panel.gapLeft, `left gutter on "${panel.title}"`).toBeGreaterThanOrEqual(8);
                expect(panel.gapRight, `right gutter on "${panel.title}"`).toBeGreaterThanOrEqual(8);
                expect(
                    Math.abs(panel.gapRight - panel.gapLeft),
                    `symmetric gutter on "${panel.title}" (L=${panel.gapLeft} R=${panel.gapRight})`,
                ).toBeLessThanOrEqual(1);
            }

            // hero meta row must fit inside the viewport (it used to push the shell wider)
            expect(metrics.meta, 'hero meta row rendered').not.toBeNull();
            expect(metrics.meta.right, 'hero meta row inside viewport').toBeLessThanOrEqual(metrics.viewport - 8);
            expect(metrics.meta.chips.length, 'hero meta chips rendered').toBeGreaterThanOrEqual(3);
            if (!metrics.meta.scrollable) {
                // When the row wraps instead of scrolling, every chip must be fully visible.
                for (const chip of metrics.meta.chips) {
                    expect(chip.right, `chip "${chip.label}" inside viewport`).toBeLessThanOrEqual(metrics.viewport - 8);
                }
            }
        });
    }
});

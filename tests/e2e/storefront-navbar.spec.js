// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Navbar modern (istanatopup + bangjeff): item dengan children ("Kalkulator")
 * harus membuka dropdown di viewport desktop, dan tetap tersembunyi di mobile
 * (children diakses lewat drawer).
 */
test.describe('Storefront navbar desktop dropdown', () => {
    test('desktop: parent item opens a dropdown with calculator children that navigate', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        const item = page.locator('.public-navbar__links--storefront .public-navbar__item--has-menu').first();
        await expect(item).toBeVisible();

        const trigger = item.locator('.public-navbar__link--has-menu');
        const submenu = item.locator('.public-navbar__submenu');

        await expect(trigger).toContainText('Kalkulator');
        await expect(submenu).toBeHidden();

        // Wrapper item harus setinggi link lain supaya baris navbar tidak bergeser.
        const siblingBox = await page.locator('.public-navbar__links--storefront .public-navbar__link').first().boundingBox();
        const itemBox = await item.boundingBox();
        expect(Math.round(itemBox.height)).toBe(Math.round(siblingBox.height));

        await trigger.hover();
        await expect(submenu).toBeVisible();
        await expect(submenu.locator('.public-navbar__submenu-link')).toHaveCount(3);
        await expect(submenu).toContainText('Win Rate');
        await expect(submenu).toContainText('Magic Wheel');
        await expect(submenu).toContainText('Zodiac');

        await submenu.locator('.public-navbar__submenu-link', { hasText: 'Magic Wheel' }).click();
        await page.waitForURL('**/id/calculator/magic-wheel');
    });

    test('mobile: the dropdown stays hidden and the drawer keeps the children reachable', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        await expect(page.locator('.public-navbar__submenu:visible')).toHaveCount(0);
        await expect(page.locator('.public-drawer__submenu-items')).toHaveCount(0);

        await page.getByRole('button', { name: 'Buka menu' }).click();
        const drawer = page.locator('.public-drawer.is-open');
        await expect(drawer).toBeVisible();

        await drawer.getByRole('button', { name: 'Kalkulator' }).click();
        const children = drawer.locator('.public-drawer__submenu-link');
        await expect(children).toHaveCount(3);

        await children.filter({ hasText: 'Zodiac' }).click();
        await page.waitForURL('**/id/calculator/zodiac');
    });
});

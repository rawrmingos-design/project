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

async function loginAsMember(page) {
    await page.goto('/id/sign-in', { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="username"]').fill('e2e-member');
    await page.locator('input[name="password"]').fill('e2e-password');
    // Legacy Blade login exposes #btnMasuk; the IstanaTopup React page uses
    // .public-auth-submit. Match either so themed runs work too.
    const loginButton = page.locator('#btnMasuk, .public-auth-submit').first();
    await expect(loginButton).toBeEnabled();
    await loginButton.click();
    await page.waitForURL(/\/id\/dashboard/, { timeout: 15_000 });
}

test.describe('Storefront navbar account menu', () => {
    test('desktop: the account area opens an account dropdown without navigating', async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 900 });
        await loginAsMember(page);
        await page.goto('/id/e2e-game', { waitUntil: 'domcontentloaded' });

        // `count()` tidak auto-wait: tunggu navbar ter-hydrate dulu, kalau tidak
        // deteksi varian membaca 0 dan test salah jalur (ambil markup bangjeff).
        await page
            .locator('.public-navbar__account-menu--compact, .public-navbar__account-trigger')
            .first()
            .waitFor({ state: 'visible', timeout: 15_000 });

        // Halaman order memakai wrapper `public-app--order-bangjeff` (bukan theme marker),
        // jadi deteksi varian navbar dari markup yang benar-benar dirender: pill ringkas
        // `.public-navbar__account-menu--compact` hanya ada di theme modern non-bangjeff.
        const isCompactVariant =
            (await page.locator('.public-navbar__account-menu--compact').count()) > 0;

        if (!isCompactVariant) {
            // Bangjeff: avatar trigger di top bar, dropdown dibuka via klik.
            const trigger = page.locator('.public-navbar__account-trigger').first();
            await expect(trigger).toBeVisible();
            await expect(page.locator('.public-navbar__account-dropdown')).toHaveCount(0);

            await trigger.click();
            const dropdown = page.locator('.public-navbar__account-dropdown').first();
            await expect(dropdown).toBeVisible();
            await expect(dropdown).toContainText('Keluar');
            expect(await dropdown.locator('.public-navbar__account-link').count()).toBeGreaterThanOrEqual(5);
            return;
        }

        const menu = page.locator('.public-navbar__account-menu--compact');
        await expect(menu).toBeVisible();

        const trigger = menu.locator('.public-navbar__compact-account');
        const dropdown = menu.locator('.public-navbar__account-dropdown');

        await expect(trigger).toContainText('e2e-member');
        await expect(dropdown).toBeHidden();

        // Hover membuka dropdown (bukan navigasi / refresh).
        await trigger.hover();
        await expect(dropdown).toBeVisible();
        await expect(dropdown).toContainText('Telah masuk sebagai');
        await expect(dropdown).toContainText('E2E Member');
        await expect(dropdown).toContainText('Keluar');
        expect(await dropdown.locator('.public-navbar__account-link').count()).toBeGreaterThanOrEqual(5);

        // Klik trigger hanya toggle; URL tidak berubah.
        await trigger.click();
        await page.waitForTimeout(600);
        expect(page.url()).toContain('/id/e2e-game');

        // Item di dalam dropdown tetap navigasi normal.
        await dropdown.locator('.public-navbar__account-link', { hasText: 'Dashboard' }).click();
        await page.waitForURL('**/id/dashboard');
    });
});

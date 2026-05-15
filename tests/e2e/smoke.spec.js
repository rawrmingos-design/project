const { test, expect } = require('@playwright/test');

test.describe('Public storefront smoke', () => {
    test('homepage renders with main navigation', async ({ page }) => {
        await page.goto('/id', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('header').first()).toBeVisible();
        await expect(page.locator('main').first()).toBeVisible();

        const visibleSearchInput = page.locator('input[placeholder*="Cari"]:visible');
        if (await visibleSearchInput.count()) {
            await expect(visibleSearchInput.first()).toBeVisible();
        } else {
            await expect(page.getByRole('link', { name: /topup|beranda/i }).first()).toBeVisible();
        }
    });

    test('sign in page renders login form', async ({ page }) => {
        await page.goto('/id/sign-in', { waitUntil: 'domcontentloaded' });

        const usernameField = page.locator('input[name="username"], input[autocomplete="username"]').first();
        await expect(usernameField).toBeVisible();

        const passwordField = page.locator('input[name="password"], input[type="password"]').first();
        await expect(passwordField).toBeVisible();
    });
});

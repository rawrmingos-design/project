const { test, expect } = require('@playwright/test');

const username = process.env.E2E_USERNAME || '';
const password = process.env.E2E_PASSWORD || '';

test.describe('Authenticated dashboard and affiliate flow', () => {
    test.describe.configure({ mode: 'serial' });
    test.setTimeout(90_000);

    test.beforeEach(async ({ page }) => {
        test.skip(!username || !password, 'Set E2E_USERNAME and E2E_PASSWORD first.');

        await page.goto('/id/sign-in', { waitUntil: 'domcontentloaded' });

        const usernameField = page.locator('input[name="username"], input[autocomplete="username"]').first();
        await usernameField.fill(username);
        await usernameField.dispatchEvent('input');

        const passwordField = page.locator('input[name="password"], input[type="password"]').first();
        await passwordField.fill(password);
        await passwordField.dispatchEvent('input');

        const loginButton = page.getByRole('button', { name: /masuk/i }).first();
        await expect(loginButton).toBeEnabled({ timeout: 10_000 });
        await loginButton.click();
    });

    test('dashboard page renders summary widgets', async ({ page }) => {
        await page.goto('/id/dashboard', { waitUntil: 'domcontentloaded' });
        await expect(page).toHaveURL(/\/id\/dashboard/);
        await expect(page.getByText(/ringkasan transaksi/i).first()).toBeVisible();
        await expect(page.getByText(/riwayat transaksi terbaru/i).first()).toBeVisible();
    });

    test('affiliate page renders tabs and state content', async ({ page }) => {
        await page.goto('/id/affiliate', { waitUntil: 'domcontentloaded' });
        await expect(page).toHaveURL(/\/id\/affiliate/);
        await expect(page.getByRole('link', { name: /riwayat/i }).first()).toBeVisible();
        await expect(page.getByText(/program afiliasi/i).first()).toBeVisible();
    });
});

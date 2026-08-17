// @ts-check
const { test, expect } = require('@playwright/test');

async function loginAsMember(page) {
    await page.goto('/id/sign-in', { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="username"]').fill('e2e-member');
    await page.locator('input[name="password"]').fill('e2e-password');
    const loginButton = page.locator('#btnMasuk');
    await expect(loginButton).toBeEnabled();
    await loginButton.click();
    await page.waitForURL(/\/id\/dashboard/, { timeout: 15_000 });
}

test.describe('Member deposit page', () => {
    test('shows minimum amount, payment method, and empty history state', async ({ page }) => {
        await loginAsMember(page);
        await page.goto('/id/deposit', { waitUntil: 'domcontentloaded' });

        await expect(page.getByRole('heading', { name: /top up saldo/i })).toBeVisible();
        await expect(page.locator('input[placeholder="Minimal Rp 10.000"]')).toBeVisible();
        await expect(page.locator('.public-deposit-method-card:visible')).toContainText('E2E QRIS');
        await expect(page.getByText(/belum ada transaksi deposit/i)).toBeVisible();
    });

    test('keeps submit disabled below minimum and enables it for a valid deposit', async ({ page }) => {
        await loginAsMember(page);
        await page.goto('/id/deposit', { waitUntil: 'domcontentloaded' });

        const amount = page.locator('input[placeholder="Minimal Rp 10.000"]');
        const phone = page.locator('input[placeholder="Contoh: 62812xxxx"]');
        const submit = page.locator('.public-deposit-summary__submit');

        await amount.fill('9999');
        await phone.fill('6281200000001');
        await expect(submit).toBeDisabled();

        await amount.fill('15000');
        await expect(submit).toBeEnabled();
        await expect(page.getByText(/Rp 15\.000/).first()).toBeVisible();
    });
});
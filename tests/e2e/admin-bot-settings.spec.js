// @ts-check
const { test, expect } = require('@playwright/test');

test.describe.configure({ mode: 'serial' });

test.skip(true, 'Admin Filament requires a configured FILAMENT_ADMIN_DOMAIN; public E2E harness intentionally tests member/public flows.');

async function loginAsAdmin(page) {
    await page.goto('/admin/login', { waitUntil: 'domcontentloaded' });
    await page.locator('input[type="email"], input[name="email"]').first().fill('e2e-admin@example.test');
    await page.locator('input[type="password"]').first().fill('e2e-password');
    await page.getByRole('button', { name: /sign in|masuk|login/i }).first().click();
    await page.waitForURL(/\/admin(\/dashboard)?/, { timeout: 15_000 });
}

test.describe('Admin bot order settings', () => {
    test('admin can open notifications settings and see both bot order toggles', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/admin/settings/notifications', { waitUntil: 'domcontentloaded' });

        await expect(page.getByText('Konfigurasi WhatsApp', { exact: true })).toBeVisible();
        await expect(page.getByText('Konfigurasi Telegram', { exact: true })).toBeVisible();
        await expect(page.getByText('Terima Order via WhatsApp', { exact: true })).toBeVisible();
        await expect(page.getByText('Terima Order via Telegram', { exact: true })).toBeVisible();
    });

    test('admin can persist bot order toggles', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/admin/settings/notifications', { waitUntil: 'domcontentloaded' });

        const waToggle = page.locator('input[type="checkbox"]').filter({ has: undefined }).nth(0);
        const tgToggle = page.locator('input[type="checkbox"]').filter({ has: undefined }).nth(1);
        await waToggle.check();
        await tgToggle.check();

        await page.locator('form[wire\\:submit="save"] button[type="submit"]').click();
        await expect(page.getByText('Pengaturan Tersimpan')).toBeVisible({ timeout: 10_000 });

        await page.reload({ waitUntil: 'domcontentloaded' });
        await expect(waToggle).toBeChecked();
        await expect(tgToggle).toBeChecked();
    });
});
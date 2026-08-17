// @ts-check
const { test, expect } = require('@playwright/test');

const E2E_MEMBER = {
    username: 'e2e-member',
    password: 'e2e-password',
};

/**
 * Login via the public sign-in page (Blade). Seeded by E2EBrowserSeeder.
 * Returns once the member dashboard is reachable.
 */
async function loginAsMember(page) {
    await page.goto('/id/sign-in', { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="username"]').fill(E2E_MEMBER.username);
    await page.locator('input[name="password"]').fill(E2E_MEMBER.password);
    await page.getByRole('button', { name: /masuk/i }).first().click();
    await page.waitForURL(/\/id\/dashboard/, { timeout: 15_000 });
    await expect(page).toHaveURL(/\/id\/dashboard/);
    await expect(page.locator('body')).not.toContainText('Username / password mismatch');
}

test.describe('Authenticated settings (member)', () => {
    test('member can reach the settings page', async ({ page }) => {
        await loginAsMember(page);
        await page.goto('/id/settings', { waitUntil: 'domcontentloaded' });
        await expect(page).toHaveURL(/\/id\/settings/);
        await expect(page.getByRole('heading', { name: 'Pengaturan', exact: true })).toBeVisible();
    });

    test('settings page shows WhatsApp + Telegram gateway cards', async ({ page }) => {
        await loginAsMember(page);
        await page.goto('/id/settings', { waitUntil: 'domcontentloaded' });
        await expect(page.getByRole('heading', { name: 'WhatsApp Gateway', exact: true })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Telegram Gateway', exact: true })).toBeVisible();
    });

    test('telegram gateway shows disabled create button when bot not configured', async ({ page }) => {
        await loginAsMember(page);
        await page.goto('/id/settings', { waitUntil: 'domcontentloaded' });

        const telegramSection = page.locator('section.public-settings-card').filter({ hasText: 'Telegram Gateway' });
        await expect(telegramSection).toBeVisible();

        // E2E env has no TELEGRAM_BOT_USERNAME -> botConfigured=false -> disabled.
        const createButton = telegramSection.getByRole('button', { name: /buat link telegram/i });
        await expect(createButton).toBeDisabled();
    });

    test('telegram status endpoint returns 200 for member and expects verified boolean', async ({ page }) => {
        await loginAsMember(page);
        const response = await page.request.get('/id/settings/telegram/status', {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        expect(response.ok()).toBe(true);
        const body = await response.json();
        expect(body.status).toBe('success');
        expect(typeof body.data.verified).toBe('boolean');
    });
});

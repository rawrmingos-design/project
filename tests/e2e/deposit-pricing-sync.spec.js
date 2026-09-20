// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Regression guard for the deposit price contract.
 *
 * Deposit must follow the SAME convention as the order checkout:
 *   - the customer pays exactly `nominal + admin fee`
 *   - the gateway is asked for a reduced amount so its own customer fee lands on that total
 *   - the gateway fee is absorbed by the store and is NOT shown as a second charge
 *
 * The old behaviour showed `nominal + admin fee` while Tripay charged more (50.450 shown,
 * 51.554 charged), and a follow-up build showed the absorbed gateway fee as a second row on
 * top of a "Biaya" row that already included it — double-counting on screen.
 */

async function loginAsMember(page) {
    await page.goto('/id/sign-in', { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="username"]').fill('e2e-member');
    await page.locator('input[name="password"]').fill('e2e-password');
    const loginButton = page.locator('#btnMasuk, .public-auth-submit').first();
    await expect(loginButton).toBeEnabled();
    await loginButton.click();
    await page.waitForURL(/\/id\/dashboard/, { timeout: 15_000 });
}

test.describe('Deposit price is what the customer pays', () => {
    test('the quote total equals nominal plus the admin fee', async ({ page }) => {
        await loginAsMember(page);

        const response = await page.request.post('/id/deposit/quote', {
            form: { jumlah: 50000, metode: 'E2E_QRIS' },
        });

        expect(response.ok()).toBeTruthy();

        const payload = await response.json();
        expect(payload.success).toBe(true);

        // Same rule as the order flow: displayed total == nominal + admin fee.
        expect(payload.data.total_amount).toBe(
            payload.data.net_amount + payload.data.admin_fee,
        );

        // Any gateway fee is absorbed, so it must never be charged on top of the total.
        expect(payload.data.gateway_amount + payload.data.gateway_fee).toBe(
            payload.data.total_amount,
        );
    });

    test('the summary shows one fee row and no separate gateway charge', async ({ page }) => {
        await loginAsMember(page);
        await page.goto('/id/deposit', { waitUntil: 'domcontentloaded' });

        const amount = page.locator('input[placeholder="Minimal Rp 10.000"]');
        const phone = page.locator('input[placeholder="Contoh: 62812xxxx"]');

        await amount.fill('50000');
        await phone.fill('6281200000001');

        // Let the debounced server quote land.
        await page.waitForTimeout(2000);

        const summary = await page.evaluate(() =>
            Array.from(document.querySelectorAll('.public-deposit-summary__row')).map(
                (row) => (row.textContent || '').replace(/\s+/g, ' ').trim(),
            ),
        );

        const joined = summary.join(' | ');
        expect(joined).toContain('Nominal');
        expect(joined).toContain('Biaya');
        expect(joined).toContain('Total Pembayaran');

        // The absorbed gateway fee must not be presented as its own charge: that row was the
        // double-count. The customer sees exactly one fee line.
        expect(joined).not.toContain('Biaya Payment Gateway');

        const feeRows = summary.filter((row) => row.startsWith('Biaya'));
        expect(feeRows).toHaveLength(1);
        // The label must not be the gateway variant — that row was the double-count.
        expect(feeRows[0]).not.toContain('Payment Gateway');

        const total = await page
            .locator('[data-role="deposit-total"], #summary_total')
            .first()
            .innerText();
        expect(total.replace(/\s/g, '')).toContain('Rp');
    });
});

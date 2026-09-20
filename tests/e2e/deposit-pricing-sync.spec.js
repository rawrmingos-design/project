// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Regression guard for the deposit price mismatch.
 *
 * The form used to show `nominal + admin fee` (Rp 50.450 for 50.000 on QRIS) while
 * the customer was actually charged Rp 51.554, because Tripay adds its own customer
 * fee (flat 750 + 0.70%) on top of the amount we ask it to collect. The deposit page
 * must therefore show the total that comes back from the server quote — the same
 * number the invoice and the gateway use.
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

/** Reads the total shown in the deposit summary. */
async function readSummaryTotal(page) {
    return page.locator('[data-role="deposit-total"], #summary_total').first().innerText();
}

test.describe('Deposit price is what the customer pays', () => {
    test('the quote endpoint returns a gateway-inclusive total', async ({ page }) => {
        await loginAsMember(page);

        const response = await page.request.post('/id/deposit/quote', {
            form: { jumlah: 50000, metode: 'E2E_QRIS' },
        });

        expect(response.ok()).toBeTruthy();

        const payload = await response.json();
        expect(payload.success).toBe(true);

        // Nominal + admin fee is what the gateway receives; the customer pays that
        // plus whatever customer fee the gateway adds.
        expect(payload.data.net_amount).toBe(50000);
        expect(payload.data.gateway_amount).toBe(
            payload.data.net_amount + payload.data.admin_fee,
        );
        expect(payload.data.total_amount).toBe(
            payload.data.gateway_amount + payload.data.gateway_fee,
        );
    });

    test('the summary total matches the quote, not nominal plus admin fee', async ({ page }) => {
        await loginAsMember(page);
        await page.goto('/id/deposit', { waitUntil: 'domcontentloaded' });

        const amount = page.locator('input[placeholder="Minimal Rp 10.000"]');
        const phone = page.locator('input[placeholder="Contoh: 62812xxxx"]');

        // E2E_QRIS is seeded with zero fees so the fixture is deterministic.
        await amount.fill('50000');
        await phone.fill('6281200000001');

        const response = await page.request.post('/id/deposit/quote', {
            form: { jumlah: 50000, metode: 'E2E_QRIS' },
        });
        expect(response.ok()).toBeTruthy();
        const quoted = await response.json();

        const expected = new Intl.NumberFormat('id-ID')
            .format(quoted.data.total_amount)
            .replace(/\./g, '.');

        // The displayed total must equal the server quote — this is the assertion that
        // fails when the browser re-derives the total from fee_percent/fix_fee alone.
        await expect
            .poll(async () => (await readSummaryTotal(page)).replace(/\s/g, ''), {
                timeout: 10_000,
            })
            .toContain(`Rp${expected}`);

        // And the invoice of the created deposit must agree with it.
        const submit = page.locator('.public-deposit-summary__submit');
        await expect(submit).toBeEnabled();
    });
});

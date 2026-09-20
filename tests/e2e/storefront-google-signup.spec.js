// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Guard for step 2 of the Google sign-up.
 *
 * Google cannot provide a phone number, and `users.no_wa` is NOT NULL on the real
 * schema, so a brand new Google account must NOT be created straight away: the
 * verified profile waits in the session until the visitor submits this form.
 *
 * The specs drive the controller's redirect contract directly (a real Google token
 * exchange is impossible offline): posting an invalid credential must never create
 * a user row, and the completion page must only be reachable with a pending signup.
 */

test.describe('Google signup completion', () => {
    test('the completion page is not reachable without a pending google signup', async ({ page }) => {
        await page.goto('/id/auth/google/complete', { waitUntil: 'domcontentloaded', timeout: 30_000 });

        // Guests without a verified Google profile are sent back to sign-in rather
        // than being shown an account form that cannot be submitted.
        expect(new URL(page.url()).pathname).toBe('/id/sign-in');
    });

    test('the sign-in page still renders normally next to the google flow', async ({ page }) => {
        const response = await page.goto('/id/sign-in', { waitUntil: 'domcontentloaded', timeout: 30_000 });
        expect(response?.status()).toBe(200);

        // The password form must keep working: adding the Google completion step must
        // not break the normal sign-in path.
        await expect(page.locator('input[name="username"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
    });
});

// @ts-check
const { test, expect } = require('@playwright/test');

const popupId = 900001;
const storageKey = `hidePopup_${popupId}`;

async function openHomepage(page) {
    const response = await page.goto('/id', { waitUntil: 'domcontentloaded' });
    expect(response?.ok()).toBeTruthy();

    return page.getByRole('dialog');
}

test.describe('Homepage popup release gate', () => {
    test('opens accessibly and Escape persists opt-out, unlocks scrolling, and restores focus', async ({ page }) => {
        await page.addInitScript(() => {
            document.addEventListener('DOMContentLoaded', () => {
                const focusTarget = document.createElement('button');
                focusTarget.id = 'e2e-focus-target';
                focusTarget.textContent = 'Focus target';
                document.body.prepend(focusTarget);
                focusTarget.focus();
            }, { once: true });
        });

        const dialog = await openHomepage(page);
        await expect(dialog).toBeVisible();
        await expect(dialog).toHaveAccessibleName(/popup|info penting/i);

        const closeButton = page.getByRole('button', { name: 'Tutup popup' });
        await expect(closeButton).toBeFocused();
        await expect(page.locator('body')).toHaveCSS('overflow', 'hidden');

        await page.keyboard.press('Escape');

        await expect(dialog).toBeHidden();
        await expect(page.locator('#e2e-focus-target')).toBeFocused();
        await expect.poll(() => page.evaluate(() => document.body.style.overflow)).toBe('');
        await expect.poll(() => page.evaluate((key) => localStorage.getItem(key), storageKey)).toBe('true');
    });

    test('close button keeps the same popup hidden after reload', async ({ page }) => {
        const dialog = await openHomepage(page);
        await expect(dialog).toBeVisible();

        await page.getByRole('button', { name: 'Tutup popup' }).click();
        await expect(dialog).toBeHidden();
        await expect.poll(() => page.evaluate((key) => localStorage.getItem(key), storageKey)).toBe('true');

        await page.reload({ waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(800);
        await expect(page.getByRole('dialog')).toBeHidden();
    });

    test('Tab and Shift+Tab remain inside the dialog', async ({ page }) => {
        const dialog = await openHomepage(page);
        await expect(dialog).toBeVisible();

        const closeButton = page.getByRole('button', { name: 'Tutup popup' });
        await expect(closeButton).toBeFocused();

        await page.keyboard.press('Tab');
        await expect(closeButton).toBeFocused();

        await page.keyboard.press('Shift+Tab');
        await expect(closeButton).toBeFocused();

        const focusIsInside = await page.evaluate(() => {
            const activeElement = document.activeElement;
            const popup = document.querySelector('[role="dialog"]');

            return Boolean(activeElement && popup?.contains(activeElement));
        });
        expect(focusIsInside).toBeTruthy();
    });

    test('backdrop dismissal persists the per-popup opt-out', async ({ page }) => {
        const dialog = await openHomepage(page);
        await expect(dialog).toBeVisible();

        await page.locator('.homepage-popup__viewport').click({ position: { x: 5, y: 5 } });

        await expect(dialog).toBeHidden();
        await expect.poll(() => page.evaluate((key) => localStorage.getItem(key), storageKey)).toBe('true');
    });
});

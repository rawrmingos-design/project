const { test, expect } = require('@playwright/test');

/**
 * Halaman order tema `default` (Blade) — inilah tema yang dipakai produksi,
 * dan jalur yang tidak pernah dirender oleh bundle React. Jadi regresi di sini
 * tidak akan tertangkap suite tema `istanatopup`.
 *
 * Yang dikunci: saat provider melaporkan akun di luar Indonesia, peringatan
 * muncul di bawah form User ID dan tombol "Pesan Sekarang" dimatikan; begitu
 * ID diganti jadi akun Indonesia, peringatan hilang dan tombol hidup lagi.
 */

const GAME_SLUG = 'e2e-game';

const ID_ACCOUNT = { code: 'ID', reported: 'Indonesia', provider: 'codashop' };
const MY_ACCOUNT = { code: 'MY', reported: 'Malaysia', provider: 'codashop' };

/** Balasan check-account dengan region tertentu (atau tanpa region sama sekali). */
function accountFound(accountRegion) {
    const data = { username: 'E2E Player' };
    if (accountRegion) {
        data.account_region = accountRegion;
    }
    return { status: { code: 200, message: 'User found' }, data };
}

/** Peringatan region: wadah terpisah di bawah input User ID. */
const warning = (page) => page.locator('#account-region-warning');
/** Tombol pesan yang benar-benar terlihat (desktop & mobile sama-sama ada). */
const orderButton = (page) => page.locator('#order-check:visible').first();

/**
 * newkbrorder.js mendengarkan `blur keyup` (debounce 800ms), sedangkan
 * `fill()` hanya mengubah nilai tanpa memicu event keyboard — jadi submit
 * manual event-nya supaya pengecekan akun benar-benar jalan.
 */
async function fillUserId(page, value) {
    const input = page.locator('#user_id');
    await input.fill(value);
    await input.dispatchEvent('keyup');
}

test.describe('Order layout default — peringatan region akun non-ID', () => {
    test('menampilkan peringatan di bawah form UID dan mematikan tombol pesan', async ({ page }) => {
        await page.route('**/ajax/check-account', async (route) => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(accountFound(MY_ACCOUNT)),
            });
        });

        await page.goto(`/id/${GAME_SLUG}`);

        await fillUserId(page, '999000111');

        const regionWarning = warning(page);
        await expect(regionWarning).toBeVisible();
        await expect(regionWarning).toContainText('Your account from region MY');
        await expect(regionWarning).toContainText('we cannot processed it. Only region ID allowed.');

        await expect(orderButton(page)).toBeDisabled();
    });

    test('akun region ID: tanpa peringatan dan tombol tetap aktif', async ({ page }) => {
        await page.route('**/ajax/check-account', async (route) => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(accountFound(ID_ACCOUNT)),
            });
        });

        await page.goto(`/id/${GAME_SLUG}`);

        await fillUserId(page, '123456789');
        await expect(page.locator('#nickname-display')).toContainText('E2E Player');

        await expect(warning(page)).toBeHidden();
        await expect(orderButton(page)).toBeEnabled();
    });

    test('mengganti ID non-ID dengan ID Indonesia memulihkan tombol', async ({ page }) => {
        let body = accountFound(MY_ACCOUNT);
        await page.route('**/ajax/check-account', async (route) => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(body),
            });
        });

        await page.goto(`/id/${GAME_SLUG}`);

        await fillUserId(page, '999000111');
        await expect(warning(page)).toBeVisible();
        await expect(orderButton(page)).toBeDisabled();

        body = accountFound(ID_ACCOUNT);
        await fillUserId(page, '123456789');
        await expect(page.locator('#nickname-display')).toContainText('Indonesia');

        await expect(warning(page)).toBeHidden();
        await expect(orderButton(page)).toBeEnabled();
    });

    test('provider tidak melaporkan region: order tidak pernah diblokir', async ({ page }) => {
        await page.route('**/ajax/check-account', async (route) => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(accountFound(null)),
            });
        });

        await page.goto(`/id/${GAME_SLUG}`);

        await fillUserId(page, '123456789');
        await expect(page.locator('#nickname-display')).toContainText('E2E Player');

        await expect(warning(page)).toBeHidden();
        await expect(orderButton(page)).toBeEnabled();
    });
});

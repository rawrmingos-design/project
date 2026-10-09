// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Tema legacy `default` (Blade) — tema yang dipakai produksi.
 *
 * Dua regresi yang dikunci di sini:
 *
 * 1. z-index hasil pencarian. Sebelumnya `z-30`, sedangkan header sticky memakai
 *    `z-index:50` (inline). Akibatnya baris menu navbar (Beranda/Cek Transaksi/
 *    Daftar Harga/Leaderboard/Kalkulator) menimpa hasil pencarian. Sekarang
 *    `z-[60]` supaya hasil selalu di atas navbar.
 *
 * 2. panel pencarian mobile transparan: `bg-opacity-80` membuat latar menembus
 *    konten di belakangnya. `bg-opacity-80` dihapus supaya `bg-murky-700` solid.
 *
 * Catatan: endpoint `POST /id/cari/index` di produksi diblokir Cloudflare
 * (410 Gone) di luar kode aplikasi, jadi di sini permintaannya di-mock — yang
 * diuji adalah geometri/nilai CSS, bukan isi hasil dari origin.
 */

const GAME_SLUG = 'e2e-game';
const SEARCH_URL = `/id/${GAME_SLUG}`;
/** Cocokkan endpoint pencarian dengan atau tanpa trailing slash. */
const SEARCH_ENDPOINT = /\/id\/cari\/index\/?$/;

/** Potongan hasil pencarian yang cukup untuk memicu kelas `show`. */
const SEARCH_RESULT_HTML = `
    <li class="py-1">
        <a href="/id/${GAME_SLUG}" class="flex items-center gap-3 px-3 py-2">
            <span class="text-sm text-white">E2E Game</span>
        </a>
    </li>
`;

/** Tutup prompt PWA/popup yang bisa menutupi navbar. */
async function dismissOverlays(page) {
    await page.evaluate(() => {
        document
            .querySelectorAll('.homepage-popup, [data-pwa-prompt], .pwa-connection-toast')
            .forEach((node) => node.remove());
        document.body.style.overflow = '';
        document.body.classList.remove('public-body-lock');
    });
}

/**
 * Titik-titik di area hasil pencarian yang DIKUASAI elemen lain (bukan bagian
 * dari hasil pencarian). Kosong = hasil pencarian menang di semua titik.
 *
 * Catatan: fungsi ini diserialisasi ke konteks browser oleh Playwright, jadi
 * selector HARUS dikirim sebagai argumen (closure tidak ikut terbawa).
 */
function overlapLosers(selector) {
    const results = document.querySelector(selector);
    if (!results) return ['hasil pencarian tidak ditemukan'];

    const box = results.getBoundingClientRect();
    const left = box.left + 6;
    const right = box.right - 6;
    const top = box.top + 6;
    const bottom = Math.min(box.bottom, window.innerHeight) - 6;
    if (right <= left || bottom <= top) return [];

    const losers = new Set();
    for (let x = left; x <= right; x += 20) {
        for (let y = top; y <= bottom; y += 5) {
            const stack = document.elementsFromPoint(x, y);
            if (!stack.some((el) => results.contains(el))) continue;
            const winner = stack[0];
            if (winner !== results && !results.contains(winner)) {
                losers.add((winner.className || winner.tagName).toString().trim().slice(0, 60));
            }
        }
    }
    return [...losers];
}

test.describe('Storefront search results stacking (tema legacy Blade)', () => {
    test.beforeEach(async ({ page }) => {
        await page.route(SEARCH_ENDPOINT, (route) =>
            route.fulfill({ status: 200, contentType: 'text/html', body: SEARCH_RESULT_HTML })
        );

        await page.setViewportSize({ width: 1280, height: 900 });
        await page.goto(SEARCH_URL, { waitUntil: 'domcontentloaded' });
        await dismissOverlays(page);
    });

    test('desktop: hasil pencarian menang atas baris menu navbar', async ({ page }) => {
        const input = page.locator('#searchProdsdekstop');
        await expect(input).toBeVisible();

        await input.fill('e2e');

        const results = page.locator('.resultsearchdekstop');
        await expect(results).toBeVisible();
        await expect(results).toHaveClass(/show/);
        await expect(results.locator('a')).toHaveCount(1);

        // Header memakai inline `z-index:50`; hasil harus di atasnya.
        const zIndex = await results.evaluate((n) => Number(window.getComputedStyle(n).zIndex));
        expect(zIndex).toBeGreaterThan(50);

        // Bukti geometri: tidak ada elemen lain yang menang di titik tumpang tindih.
        const losers = await page.evaluate(overlapLosers, '.resultsearchdekstop');
        expect(losers).toEqual([]);
    });

    test('desktop: hasil pencarian memakai latar solid (tidak tembus)', async ({ page }) => {
        await page.locator('#searchProdsdekstop').fill('e2e');
        await expect(page.locator('.resultsearchdekstop')).toBeVisible();

        const background = await page
            .locator('.resultsearchdekstop')
            .evaluate((n) => window.getComputedStyle(n).backgroundColor);

        // Opak: `rgb(...)`, bukan `rgba(..., 0.8)`.
        expect(background).toMatch(/^rgb\(/);
    });

    test('mobile: panel pencarian memakai latar solid (bukan bg-opacity-80)', async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(SEARCH_URL, { waitUntil: 'domcontentloaded' });
        await dismissOverlays(page);

        // Buka modal pencarian lewat tombol yang men-set `isSearchModalOpen`.
        const opened = await page.evaluate(() => {
            const trigger = [...document.querySelectorAll('button')].find(
                (b) => b.getAttribute('x-on:click') === 'isSearchModalOpen = true'
            );
            if (!trigger) return false;
            trigger.click();
            return true;
        });
        expect(opened).toBe(true);

        const panel = page.locator('div[id^="dialog-panel"]').first();
        await expect(panel).toBeVisible();

        // Markup: kelas opacity lama harus hilang, kelas latar tetap ada.
        const className = await panel.getAttribute('class');
        expect(className).toContain('bg-murky-700');
        expect(className).not.toContain('bg-opacity-80');

        // Nilai yang benar-benar dihitung harus opak.
        const background = await panel.evaluate((n) => window.getComputedStyle(n).backgroundColor);
        expect(background).toMatch(/^rgb\(/);
    });
});

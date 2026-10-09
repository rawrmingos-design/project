const { test, expect } = require('@playwright/test');

/**
 * Deskripsi SEO footer (rich text) harus tampil di SEMUA renderer/tema:
 * Blade legacy (`default`, kelas `.footer-seo-rich`) dan React
 * (`istanatopup` & `bangjeff`, kelas `.public-footer__seo`).
 *
 * Sebelumnya `Footer.jsx` merender section ini dengan gate
 * `{!isIstanaTopup ? ... : null}`, jadi tema `istanatopup` — tema yang dipakai
 * staging & produksi — kehilangan deskripsi footer sepenuhnya walau prop
 * `siteConfig.footerDescriptionHtml` sudah tersedia.
 *
 * Spec ini sengaja memakai hook `data-footer-seo*` (dipasang di kedua
 * renderer) supaya tidak bergantung pada nama kelas tema, dan mengukur browser
 * sungguhan: section ada dan terisi, kontrol buka/tutupnya benar-benar bekerja,
 * serta fade kolaps berakhir di warna surface tema — bukan warna hardcode milik
 * tema lain, yang tampak sebagai kotak gelap berbeda.
 */

const FOOTER_TEXT = 'Deterministic browser test storefront.';

const KELAS_SECTION = /public-footer__seo|footer-seo-rich/;

/**
 * Nonaktifkan popup beranda secara deterministik. Popup-nya modal dan
 * mencegat klik ke elemen footer, jadi kontrol buka/tutup tidak bisa diklik
 * selama popup terbuka. Pola ini sama dengan `storefront-order.spec.js`:
 * tandai opt-out lewat localStorage SEBELUM halaman dimuat.
 */
async function tanpaPopupBeranda(page) {
    await page.addInitScript(() => {
        window.localStorage.setItem('hidePopup_900001', 'true');
    });
}

/** Ambil pasangan rgba terakhir dari gradient (warna tujuan fade). */
function akhirGradient(backgroundImage) {
    const matches = [...String(backgroundImage).matchAll(/rgba?\(([^)]+)\)/g)];

    return matches.length ? `rgb(${matches[matches.length - 1][1].trim()})` : '';
}

/** Komponen warna dari stop pertama gradient. */
function stopPertama(backgroundImage) {
    const match = String(backgroundImage).match(/rgba?\(([^)]+)\)/);

    return match ? match[1].split(',').map((bagian) => bagian.trim()) : [];
}

test.describe('Deskripsi SEO footer', () => {
    test('section deskripsi tampil di beranda dan bisa dibuka/tutup', async ({ page }) => {
        await tanpaPopupBeranda(page);
        await page.goto('/id');

        const section = page.locator('[data-footer-seo]');
        await expect(section).toBeVisible();
        await expect(section).toHaveClass(KELAS_SECTION);

        const content = section.locator('[data-footer-seo-content]');
        await expect(content).toContainText(FOOTER_TEXT);

        const toggle = section.locator('[data-footer-seo-toggle]');
        const collapsible = await section.evaluate((el) => el.classList.contains('is-collapsible'));

        if (collapsible) {
            // Konten lebih tinggi dari ambang kolaps -> ada kontrol buka/tutup.
            await expect(toggle).toBeVisible();

            const tinggi = () => content.evaluate((el) => Math.round(el.getBoundingClientRect().height));
            const tertutup = await tinggi();
            expect(tertutup).toBeGreaterThan(0);

            await toggle.click();
            await page.waitForTimeout(450);

            expect(await tinggi()).toBeGreaterThan(tertutup);
            await expect(section).toHaveClass(/is-expanded/);
            await expect(toggle).toHaveAttribute('aria-expanded', 'true');
            await expect(toggle).toHaveText('Tutup');

            await toggle.click();
            await page.waitForTimeout(450);

            expect(await tinggi()).toBe(tertutup);
            await expect(toggle).toHaveAttribute('aria-expanded', 'false');
            await expect(toggle).toHaveText('Baca selengkapnya');
        } else {
            // Konten pendek: tidak ada tombol, dan tidak boleh terpotong.
            const terpotong = await content.evaluate(
                (el) => el.scrollHeight > el.getBoundingClientRect().height + 1
            );
            expect(terpotong).toBe(false);
        }
    });

    test('fade kolaps berakhir di warna surface tema, bukan warna tema lain', async ({ page }) => {
        await tanpaPopupBeranda(page);
        await page.goto('/id');

        const section = page.locator('[data-footer-seo]');
        const collapsible = await section.evaluate((el) => el.classList.contains('is-collapsible'));

        test.skip(!collapsible, 'Seeder tidak menghasilkan konten yang cukup untuk kolaps.');

        const info = await section.evaluate((el) => {
            const content = el.querySelector('[data-footer-seo-content]');
            const footer = el.closest('footer') || document.querySelector('footer');
            // Surface footer tidak selalu di <footer> itu sendiri: tema React
            // memakai `background: transparent` di sana dan warna aslinya ada di
            // pembungkus dalam. Cari ancestor pertama yang benar-benar berwarna.
            const surface = (() => {
                for (let node = content.parentElement; node; node = node.parentElement) {
                    const bg = getComputedStyle(node).backgroundColor;
                    if (bg && bg !== 'transparent' && !bg.startsWith('rgba(0, 0, 0, 0)')) {
                        return bg;
                    }
                }

                return footer ? getComputedStyle(footer).backgroundColor : '';
            })();

            return {
                gradient: getComputedStyle(content, '::after').backgroundImage,
                surface,
            };
        });

        // Invarian sebenarnya: fade mulai dari transparan dan berakhir tepat di
        // warna surface yang menaunginya. Warna hardcode #28282a (surface tema
        // lain) langsung ketahuan di sini.
        const [r, g, b, alpha] = stopPertama(info.gradient);
        expect(Number(alpha)).toBe(0);
        expect(akhirGradient(info.gradient)).toBe(info.surface);
        expect(info.gradient).not.toBe('none');
    });

    test('section deskripsi juga tampil di halaman non-beranda', async ({ page }) => {
        await page.goto('/id/artikel');

        const section = page.locator('[data-footer-seo]');
        await expect(section).toBeVisible();
        await expect(section.locator('[data-footer-seo-content]')).toContainText(FOOTER_TEXT);
    });
});

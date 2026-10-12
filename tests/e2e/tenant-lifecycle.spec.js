// @ts-check
/**
 * E2E siklus hidup tenant — STAGING ONLY.
 *
 * Kenapa tidak ikut suite lokal/CI biasa: suite E2E yang lain menjalankan server
 * php sendiri di atas sqlite yang di-seed ulang tiap kali, dan di sana tenancy
 * dimatikan (`tenancy.disabled`) serta tidak ada middleware tenant yang
 * terpasang. Siklus yang diuji di sini — subdomain tenant non-aktif disajikan
 * halaman status, tautan tagihannya hidup di host platform, lalu tenant pulih
 * setelah diaktifkan — baru ada di lingkungan ber-tenancy nyata.
 *
 * Dua mode:
 *   - **Siklus penuh** (default): `scripts/e2e/tenant-lifecycle-fixture.sh`
 *     membuat tenant sementara `e2e-lifecycle` di staging lewat shell, lalu
 *     mengaktifkan/menyuspend/menghapusnya. Tidak ada rute khusus test di dalam
 *     aplikasi — rute seperti itu akan ikut terpasang di produksi juga.
 *   - **Read-only** (`E2E_TENANT_DRY_RUN=1`): tidak membuat atau mengubah
 *     apa pun; memeriksa tenant yang sudah ada (`E2E_TENANT_SUBDOMAIN`, default
 *     `toko`). Dipakai untuk membuktikan spec-nya sendiri bekerja sebelum
 *     menjalankan siklus yang mengubah data.
 *
 * Keduanya butuh `E2E_STAGING_BASE_URL`; tanpa itu spec di-skip sehingga lokal
 * dan CI biasa tidak terpengaruh.
 *
 * Preferensi tim: laporan memakai ANGKA, bukan screenshot. Screenshot tetap
 * disimpan ke `test-results/tenant-lifecycle/` sebagai artefak, bukan untuk
 * dibaca di chat.
 */
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const { test, expect } = require('@playwright/test');

const BASE = (process.env.E2E_STAGING_BASE_URL || '').replace(/\/+$/, '');
const DRY_RUN = process.env.E2E_TENANT_DRY_RUN === '1';

const FIXTURE_SUBDOMAIN = process.env.E2E_TENANT_SUBDOMAIN || 'e2e-lifecycle';
const SUBDOMAIN = DRY_RUN ? (process.env.E2E_TENANT_EXISTING || 'toko') : FIXTURE_SUBDOMAIN;
const EXPECTED_STATUS = process.env.E2E_TENANT_EXPECTED_STATUS || 'suspended';

const TENANT_ORIGIN = BASE.replace('://', `://${SUBDOMAIN}.`);

const FIXTURE = path.resolve(__dirname, '..', '..', 'scripts', 'e2e', 'tenant-lifecycle-fixture.sh');
const ARTIFACT_DIR = path.resolve(__dirname, '..', '..', 'test-results', 'tenant-lifecycle');

/** Judul halaman status per status tenant — dipakai untuk memastikan salinan yang tampil benar. */
const JUDUL = {
    suspended: /ditangguhkan/i,
    pending_payment: /belum aktif/i,
};

test.describe('Siklus hidup tenant (staging)', () => {
    test.skip(BASE === '', 'E2E_STAGING_BASE_URL kosong — spec ini hanya jalan di staging.');

    let token = '';

    /**
     * Jalankan aksi fixture dan baca keluaran `KUNCI=nilai`.
     * @returns {Record<string, string>}
     */
    function fixture(action) {
        const stdout = execFileSync('bash', [FIXTURE, action], {
            encoding: 'utf8',
            env: process.env,
            timeout: 120_000,
        });

        /** @type {Record<string, string>} */
        const parsed = {};
        for (const line of stdout.split('\n')) {
            const match = /^([A-Z_]+)=(.*)$/.exec(line.trim());
            if (match) {
                parsed[match[1]] = match[2];
            }
        }

        return parsed;
    }

    /**
     * Props Inertia halaman saat ini — sumber kebenaran, bukan teks yang
     * kebetulan terlihat.
     */
    async function inertiaProps(page) {
        return JSON.parse(await page.locator('script[data-page]').textContent());
    }

    test.beforeAll(() => {
        fs.mkdirSync(ARTIFACT_DIR, { recursive: true });

        if (DRY_RUN) {
            // Mode read-only: token boleh datang dari env kalau kita mau
            // memeriksa halaman tagihan tertentu.
            token = process.env.E2E_TENANT_TOKEN || '';
            return;
        }

        const hasil = fixture('setup');
        expect(hasil.TOKEN, 'fixture harus mengembalikan token tagihan').toBeTruthy();
        expect(hasil.STATUS).toBe('suspended');
        token = hasil.TOKEN;
    });

    test.afterAll(() => {
        if (!DRY_RUN) {
            fixture('teardown');
        }
    });

    test('tenant non-aktif menampilkan halaman status, bukan 404', async ({ page }) => {
        const response = await page.goto(`${TENANT_ORIGIN}/`, { waitUntil: 'domcontentloaded' });

        expect(response?.status()).toBe(200);

        const props = await inertiaProps(page);
        expect(props.component).toBe('Public/TenantStatus');
        expect(props.props.status).toBe(EXPECTED_STATUS);

        await expect(page.getByRole('heading', { name: JUDUL[EXPECTED_STATUS] })).toBeVisible();

        // D9: alamat disensor, nama mentah tidak pernah tampil.
        expect(props.props.address).toContain('*');

        // Props tidak boleh memuat label subdomain mentah. Halaman itu sendiri
        // tetap memuat host di URL aset — itu setara address bar, bukan
        // kebocoran — jadi yang diperiksa props-nya.
        expect(JSON.stringify(props.props)).not.toContain(SUBDOMAIN);

        // Identitas pemilik tidak pernah muncul di mana pun.
        const isi = await page.content();
        expect(isi).not.toContain('E2E Lifecycle Owner');
        expect(isi).not.toContain('E2E Lifecycle Store');
        expect(isi).not.toContain('@example.test');

        // noindex harus benar-benar menjadi <meta>, bukan sekadar prop.
        await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', 'noindex, nofollow');

        await page.screenshot({ path: path.join(ARTIFACT_DIR, '01-status.png'), fullPage: true });
    });

    test('jalur platform /id juga dicegat, bukan homepage platform', async ({ page }) => {
        const response = await page.goto(`${TENANT_ORIGIN}/id`, { waitUntil: 'domcontentloaded' });

        expect(response?.status()).toBe(200);

        const props = await inertiaProps(page);
        expect(props.component).toBe('Public/TenantStatus');
    });

    test('tautan tagihan di halaman status hidup di host platform', async ({ page }) => {
        await page.goto(`${TENANT_ORIGIN}/`, { waitUntil: 'domcontentloaded' });

        const props = await inertiaProps(page);
        const billingUrl = props.props.billingUrl;

        expect(billingUrl, 'tenant non-aktif dengan tagihan harus menawarkan tautan tagihan').toBeTruthy();

        // Yang paling mudah salah: `route()` polos menempelkan host request
        // (subdomain tenant), padahal halaman tagihan hanya hidup di host
        // platform — tautannya jadi 404. Kunci hostnya di sini.
        expect(billingUrl.startsWith(`${BASE}/tagihan/`)).toBe(true);
        expect(billingUrl).not.toContain(`${SUBDOMAIN}.`);

        if (token !== '') {
            expect(billingUrl).toContain(token);
        }

        await expect(page.getByRole('link', { name: /lihat tagihan/i })).toBeVisible();

        const response = await page.goto(billingUrl, { waitUntil: 'domcontentloaded' });
        expect(response?.status()).toBe(200);

        const tagihan = await inertiaProps(page);
        expect(tagihan.component).toBe('Public/TenantBilling');
        expect(tagihan.props.invoice.number).toBeTruthy();
        expect(tagihan.props.address).toContain('*');

        // D13.4: tagihan hanya membawa data tagihan, tanpa identitas owner.
        const isi = JSON.stringify(tagihan.props);
        expect(isi).not.toContain('@example.test');
        expect(isi).not.toContain('E2E Lifecycle Owner');

        await page.screenshot({ path: path.join(ARTIFACT_DIR, '02-tagihan.png'), fullPage: true });
    });

    test('token yang tidak dikenal menghasilkan 404', async ({ page }) => {
        const response = await page.goto(`${BASE}/tagihan/${'a'.repeat(64)}`, {
            waitUntil: 'domcontentloaded',
        });

        expect(response?.status()).toBe(404);
    });

    test('halaman tagihan tidak disajikan dari subdomain tenant', async ({ page }) => {
        await page.goto(`${TENANT_ORIGIN}/`, { waitUntil: 'domcontentloaded' });

        const billingUrl = (await inertiaProps(page)).props.billingUrl;
        test.skip(!billingUrl, 'tenant ini tidak punya tagihan tertunggak.');

        const jalur = new URL(billingUrl).pathname;
        const response = await page.goto(`${TENANT_ORIGIN}${jalur}`, { waitUntil: 'domcontentloaded' });

        expect(response?.status()).toBe(404);
    });

    test('tenant diaktifkan kembali menyajikan toko, lalu suspend lagi memunculkan halaman status', async ({ page }) => {
        test.skip(DRY_RUN, 'Dry run tidak mengubah status tenant.');

        const aktif = fixture('activate');
        expect(aktif.STATUS).toBe('active');

        const toko = await page.goto(`${TENANT_ORIGIN}/`, { waitUntil: 'domcontentloaded' });
        expect(toko?.status()).toBe(200);

        const propsToko = await inertiaProps(page);
        expect(propsToko.component).not.toBe('Public/TenantStatus');

        await page.screenshot({ path: path.join(ARTIFACT_DIR, '03-pulih.png'), fullPage: true });

        const turun = fixture('suspend');
        expect(turun.STATUS).toBe('suspended');

        await page.goto(`${TENANT_ORIGIN}/`, { waitUntil: 'domcontentloaded' });

        const propsTurun = await inertiaProps(page);
        expect(propsTurun.component).toBe('Public/TenantStatus');

        await page.screenshot({ path: path.join(ARTIFACT_DIR, '04-suspend-lagi.png'), fullPage: true });
    });
});

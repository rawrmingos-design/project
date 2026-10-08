/**
 * Perilaku tema legacy (`public_theme=default`, dipakai produksi) untuk akun
 * non-ID: peringatan harus muncul di bawah form User ID dan tombol
 * "Pesan Sekarang" harus mati selama akun terdeteksi di luar Indonesia.
 *
 * Logikanya hidup di `public/assets/js/newkbrorder.js`. Test ini memuat skrip
 * ASLI (bukan salinan) ke jsdom yang disediakan jest supaya salinan tidak bisa
 * menyimpang dari sumber, lalu men-stub hanya bagian yang tidak relevan
 * (ajax harga, toast, price refresh).
 */
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(__dirname, '../..');
const legacyScript = fs.readFileSync(path.join(root, 'public/assets/js/newkbrorder.js'), 'utf8');
const jquerySource = fs.readFileSync(path.join(root, 'public/assets/js/jquery.min.js'), 'utf8');

const ID_ACCOUNT = { code: 'ID', reported: 'Indonesia', provider: 'codashop' };
const MY_ACCOUNT = { code: 'MY', reported: 'Malaysia', provider: 'codashop' };

const ORDER_PAGE_HTML = `
    <input id="ktg_tipe" value="game" />
    <input id="user_id" name="user_id" />
    <input id="zone" name="zone" />
    <div id="nickname-display"></div>
    <div id="account-region-warning" hidden></div>
    <button type="button" id="order-check">Pesan Sekarang!</button>
    <div class="method-list"></div>
    <div class="product-list"></div>
`;

async function bootOrderPage() {
    document.body.innerHTML = ORDER_PAGE_HTML;

    // jQuery vendored dimuat seperti di halaman asli (UMD -> jatuh ke global).
    window.eval(jquerySource);

    const $ = window.$;

    // Stub hal yang tidak relevan dengan region.
    window.showToast = () => {};
    window.scrollToElement = () => {};
    window.refreshOrderPrice = () => {};
    window.togglePaymentList = () => {};
    window.requireUserId = true;
    window.kategoriKode = 'e2e-game';
    window.csrfToken = 'e2e-token';
    window.routes = {
        checkAccount: '/ajax/check-account',
        confirmationUrl: '/id/konfirmasi-data',
        price: '/id/harga',
    };

    // `$.ajax` untuk check-account dikendalikan test; sisanya no-op.
    window.__accountResponse = null;
    window.__checkAccountCalls = 0;
    $.ajax = (options) => {
        const handle = {
            done() { return handle; },
            fail() { return handle; },
            always() { return handle; },
        };
        if (options && options.url === window.routes.checkAccount) {
            window.__checkAccountCalls += 1;
            const response = window.__accountResponse;
            Promise.resolve().then(() => {
                if (response instanceof Error) {
                    if (options.error) options.error({}, 'error', response);
                    return;
                }
                if (options.success) options.success(response);
            });
            return handle;
        }
        return handle;
    };

    // Skrip asli dijalankan di dalam window jsdom.
    window.eval(legacyScript);
    $(document).trigger('ready');
    // Beri kesempatan callback ready jQuery berjalan.
    await new Promise((resolve) => setTimeout(resolve, 50));

    return { $ };
}

async function checkAccount($, uid, response) {
    window.__accountResponse = response;
    $('#user_id').val(uid);
    $('#user_id').trigger('blur');
    // Skrip asli memakai debounce 800ms.
    await new Promise((resolve) => setTimeout(resolve, 1000));
}

function successResponse(extraData) {
    return {
        status: { code: 200, message: 'User found' },
        data: Object.assign({ username: 'E2E Player' }, extraData),
    };
}

describe('peringatan region akun non-ID (tema default, jalur produksi)', () => {
    jest.setTimeout(30000);

    test('akun non-ID menampilkan peringatan di bawah form UID dan mematikan tombol', async () => {
        const { $ } = await bootOrderPage();

        await checkAccount($, '999000111', successResponse({ account_region: MY_ACCOUNT }));

        const warning = $('#account-region-warning');
        expect(warning.attr('hidden')).toBeUndefined();
        expect(warning.text()).toBe('Your account from region MY we cannot processed it. Only region ID allowed.');
        expect($('#order-check').prop('disabled')).toBe(true);
        expect(window.__accountRegion).toMatchObject({ known: true, code: 'MY', blocked: true });
    });

    test('akun region ID tidak memunculkan peringatan dan tombol tetap aktif', async () => {
        const { $ } = await bootOrderPage();

        await checkAccount($, '123456789', successResponse({ account_region: ID_ACCOUNT }));

        const warning = $('#account-region-warning');
        expect(warning.attr('hidden')).toBeDefined();
        expect(warning.text()).toBe('');
        expect($('#order-check').prop('disabled')).toBe(false);
        expect(window.__accountRegion).toMatchObject({ known: true, code: 'ID', blocked: false });
    });

    test('provider tidak melaporkan region: tetap tidak diblokir', async () => {
        const { $ } = await bootOrderPage();

        await checkAccount($, '123456789', successResponse({}));

        expect($('#account-region-warning').attr('hidden')).toBeDefined();
        expect($('#order-check').prop('disabled')).toBe(false);
        expect(window.__accountRegion).toMatchObject({ known: false, blocked: false });
    });

    test('mengganti UID non-ID menjadi UID ID memulihkan tombol', async () => {
        const { $ } = await bootOrderPage();

        await checkAccount($, '999000111', successResponse({ account_region: MY_ACCOUNT }));
        expect($('#order-check').prop('disabled')).toBe(true);

        await checkAccount($, '123456789', successResponse({ account_region: ID_ACCOUNT }));
        expect($('#account-region-warning').attr('hidden')).toBeDefined();
        expect($('#order-check').prop('disabled')).toBe(false);
    });

    test('ID tidak ditemukan menghapus peringatan lama dan mengaktifkan tombol kembali', async () => {
        const { $ } = await bootOrderPage();

        await checkAccount($, '999000111', successResponse({ account_region: MY_ACCOUNT }));
        expect($('#order-check').prop('disabled')).toBe(true);

        await checkAccount($, '555', { status: { code: 404, message: 'ID tidak ditemukan' } });

        expect($('#account-region-warning').attr('hidden')).toBeDefined();
        expect($('#order-check').prop('disabled')).toBe(false);
        expect(window.__accountRegion).toBeNull();
    });
});

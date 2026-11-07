<?php

namespace Tests\Feature\Tenancy;

use App\Models\SettingWeb;
use App\Models\TierPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Landing page SaaS.
 *
 * Yang dijaga:
 *  - Halaman tampil dan memakai token desain asli (bukan halaman kosong).
 *  - Nama merek diambil dari `setting_webs.judul_web`, BUKAN ditulis keras.
 *    Ganti judul di admin → halaman ikut berubah tanpa deploy.
 *  - Harga paket diambil dari `tier_pricing`, lengkap dengan harga coret yang
 *    dihitung dari persentase diskon — bukan angka mati.
 *  - CTA menuju pendaftaran tenant yang sudah ada, bukan anchor mati.
 *  - Saklar SaaS mati → halaman 404, bukan setengah hidup.
 */
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config([
        'tenancy.disabled' => false,
        'tenancy.trial_enabled' => true,
        'tenancy.trial_days' => 3,
    ]);

    SettingWeb::query()->firstOrCreate(['id' => 1], [
        'judul_web' => 'ISTANATOPUP - TOP UP GAME MURAH & LEGAL 24 JAM',
        'deskripsi_web' => 'Deskripsi uji',
        'keywords' => 'topup',
        'url_wa' => 'https://wa.me/6285123031674',
        'url_ig' => 'https://instagram.com/uji',
        'url_tiktok' => 'https://tiktok.com/@uji',
        'url_youtube' => 'https://youtube.com/@uji',
        'url_fb' => 'https://facebook.com/uji',
        'topupindo_api' => 'x',
        'warna1' => '#111111',
        'warna2' => '#222222',
        'warna3' => '#333333',
        'warna4' => '#444444',
        'order_prefik' => 'INV',
        'paydisini_apikey' => 'paydisini-test',
        'tripay_api' => 'tripay-test',
        'tripay_merchant_code' => 'tripay-merchant-test',
        'tripay_private_key' => 'tripay-private-test',
        'duitku_merchant_code' => 'DTEST',
        'duitku_merchant_key' => 'duitku-test',
    ]);

    // Halaman ini Blade: harus diuji di tema yang memang merender Blade,
    // bukan bergantung pada nilai `public_theme` di database saat tes jalan.
    SettingWeb::query()->where('id', 1)->update([
        'public_theme' => \App\Support\PublicThemeRegistry::DEFAULT,
    ]);

    app(\App\Tenancy\TierPricingResolver::class)->flush();

    $this->seed(\Database\Seeders\TierPricingSeeder::class);
});

test('landing page tampil di host platform', function () {
    $response = $this->get('/sewa-web-topup');

    $response->assertOk();
    $response->assertSee('Sewa website top-up', false);

    // Struktur desain asli harus ikut, bukan halaman pengganti.
    // Catatan: `hero` tidak pernah berupa `id` — di desain asli penandanya
    // `data-od-id` (penanda alat desain) yang memang dibuang saat porting.
    $response->assertSee('class="hero"', false);
    foreach (['fitur', 'cara', 'harga', 'faq', 'masuk'] as $section) {
        $response->assertSee('id="' . $section . '"', false);
    }
});

test('setiap jangkar navigasi punya target nyata', function () {
    $response = $this->get('/sewa-web-topup');
    $html = $response->getContent();

    preg_match_all('/href="#([a-z-]+)"/', $html, $anchors);
    preg_match_all('/\sid="([a-z-]+)"/', $html, $ids);

    $targets = array_unique($ids[1]);
    $dangling = array_values(array_diff(array_unique($anchors[1]), $targets));

    // Tautan ke jangkar yang tidak ada = tombol mati. Desain asli memakai
    // `#atas` untuk logo, jadi bagian atas halaman harus punya id itu.
    expect($dangling)->toBe([], 'Jangkar tanpa target: ' . implode(', ', $dangling));
});

test('nama merek diambil dari setting_webs, bukan ditulis keras', function () {
    $response = $this->get('/sewa-web-topup');

    // judul_web = "ISTANATOPUP - TOP UP GAME MURAH & LEGAL 24 JAM"
    // Bagian sebelum pemisah dipakai sebagai nama merek.
    $response->assertViewHas('brand', 'ISTANATOPUP');

    // Nama merek desain asli ("Topupin") TIDAK boleh muncul.
    $response->assertDontSee('Topupin', false);
});

test('mengganti judul_web di admin mengganti merek di halaman', function () {
    SettingWeb::query()->where('id', 1)->update(['judul_web' => 'TOKO BARU — Top up murah']);

    $response = $this->get('/sewa-web-topup');

    $response->assertViewHas('brand', 'TOKO BARU');
    $response->assertSee('TOKO BARU', false);
});

test('harga paket diambil dari tier_pricing', function () {
    $response = $this->get('/sewa-web-topup');

    $response->assertViewHas('pricing', function (array $pricing) {
        return ($pricing['starter']['p3'] ?? null) === 330000
            && ($pricing['business']['p3'] ?? null) === 435000
            && ($pricing['enterprise']['p3'] ?? null) === 537000;
    });

    // Angka dari DB harus benar-benar tercetak di halaman.
    $response->assertSee('330.000', false);
    $response->assertSee('989.000', false);
});

test('harga coret dihitung dari diskon database, bukan angka mati', function () {
    TierPricing::query()->where('tier', 'starter')->update([
        'price_3_month' => 330000,
        'strike_discount_3_month' => 20.00,
    ]);

    app(\App\Tenancy\TierPricingResolver::class)->flush();

    $response = $this->get('/sewa-web-topup');

    // 330.000 / (1 - 20%) = 412.500. Model membulatkan lewat (int) round(),
    // jadi nilai yang benar 412.500 — bukan 413.000.
    $pricing = $response->viewData('pricing');

    expect($pricing['starter']['strike3'])->toBe(412500);

    // Harga coret TIDAK boleh lebih kecil dari harga bayar: itu bukan diskon,
    // itu angka yang menyesatkan pembeli.
    expect($pricing['starter']['strike3'])->toBeGreaterThan($pricing['starter']['p3']);
});

test('mengubah harga di database mengubah halaman tanpa deploy', function () {
    TierPricing::query()->where('tier', 'starter')->update(['price_3_month' => 275000]);
    app(\App\Tenancy\TierPricingResolver::class)->flush();

    $response = $this->get('/sewa-web-topup');

    $response->assertSee('275.000', false);
    $response->assertDontSee('Rp 330.000', false);
});

test('CTA menuju pendaftaran tenant, bukan anchor mati', function () {
    $response = $this->get('/sewa-web-topup');

    $signup = route('tenant.register.page');
    $response->assertViewHas('signupUrl', $signup);
    $response->assertSee($signup, false);
});

test('landing page menyebut masa uji coba sesuai config', function () {
    config(['tenancy.trial_days' => 3]);
    $response = $this->get('/sewa-web-topup');
    $response->assertViewHas('trialDays', 3);
    $response->assertViewHas('trialEnabled', true);
});

test('saklar SaaS mati membuat halaman 404', function () {
    config(['tenancy.disabled' => true]);

    $this->get('/sewa-web-topup')->assertNotFound();
});

test('aset landing tersedia dan tidak kosong', function () {
    foreach ([
        'assets/landing/landing.css',
        'assets/landing/landing-vendor.js',
        'assets/landing/landing-app.js',
    ] as $path) {
        $full = public_path($path);
        expect(is_file($full))->toBeTrue("Aset hilang: {$path}");
        expect(filesize($full))->toBeGreaterThan(1000, "Aset terlalu kecil: {$path}");
    }
});

test('halaman jualan TIDAK muncul di domain pelanggan', function () {
    // Route ini global. Tanpa penjagaan berbasis host, harga paket platform
    // bisa dibaca dari domain toko pelanggan — dan itu benar-benar terjadi
    // di staging sebelum penjagaannya diperbaiki.
    $tenant = \App\Models\Tenant::query()->create([
        'owner_user_id' => \App\Models\User::factory()->create(['role' => 'tenant_owner'])->id,
        'name' => 'Toko Pelanggan',
        'subdomain' => 'toko-pelanggan',
        'tier' => 'starter',
        'status' => \App\Models\Tenant::STATUS_ACTIVE,
        'margin_config' => ['markup_type' => 'percent', 'markup_value' => 10],
    ]);

    $base = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

    // Host pelanggan: subdomain toko.
    $this->get('https://toko-pelanggan.' . $base . '/sewa-web-topup')->assertNotFound();

    // Host asing sama sekali.
    $this->get('https://contoh-lain.test/sewa-web-topup')->assertNotFound();

    // Host platform tetap boleh.
    $this->get('https://' . $base . '/sewa-web-topup')->assertOk();
});

test('penjagaan host tidak bergantung pada ada tidaknya tenant', function () {
    // Tidak ada tenant sama sekali: halaman tetap hanya hidup di host platform.
    $base = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

    $this->get('https://bukan-platform.test/sewa-web-topup')->assertNotFound();
    $this->get('https://' . $base . '/sewa-web-topup')->assertOk();
});

test('halaman jualan tetap tampil walau tema toko memakai React', function () {
    // Halaman ini permukaan terpisah dengan CSS/JS sendiri. Kalau ia ikut
    // mati saat tema toko berganti ke React, halaman jualan platform hilang
    // tanpa alasan — dan itu benar-benar terjadi di staging (404).
    SettingWeb::query()->where('id', 1)->update([
        'public_theme' => \App\Support\PublicThemeRegistry::ISTANATOPUP,
    ]);

    $this->get('/sewa-web-topup')->assertOk();
});

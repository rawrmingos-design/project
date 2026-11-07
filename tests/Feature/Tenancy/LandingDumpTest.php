<?php

use App\Models\SettingWeb;
use App\Models\TierPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Membuang HTML hasil render ke disk supaya bisa dibuka di browser.
 *
 * Ini bukan pengganti verifikasi runtime: yang diuji di sini adalah view +
 * CSS + aset dengan data berbentuk produksi (dari seeder). Dipakai untuk
 * memeriksa tata letak di beberapa lebar layar.
 */
test('dump render landing page', function () {
    // Alat bantu lokal, bukan tes CI: menulis berkas ke public/.
    // Jalankan dengan LANDING_DUMP=1 supaya suite tidak mengotori repo.
    if (! env('LANDING_DUMP')) {
        expect(true)->toBeTrue();
        return;
    }

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

    app(\App\Tenancy\TierPricingResolver::class)->flush();

    $response = $this->get('/sewa-web-topup');
    expect($response->getStatusCode())->toBe(200);

    $html = $response->getContent();

    // Aset relatif -> absolut, supaya halaman bisa dibuka dari file://
    $html = str_replace('href="'.asset('assets/landing/'), 'href="assets/landing/', $html);
    $html = str_replace('src="'.asset('assets/landing/'), 'src="assets/landing/', $html);

    File::put(base_path('public/landing-preview.html'), $html);

    fwrite(STDERR, "\n  HTML  : " . strlen($html) . " bytes\n");
    fwrite(STDERR, "  harga : " . implode(', ', array_slice(array_unique(
        array_map(fn ($m) => $m[0], preg_match_all('/Rp [\d.]+/', $html, $mm) ? $mm : [[]])
    ), 0, 8)) . "\n");
});

<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TEKS HARGA DI TOKO TENANT HARUS MENJELASKAN MODEL YANG BENAR-BENAR BERLAKU.
 *
 * Kenapa ini bukan kosmetik
 * -------------------------
 * Halaman toko tenant dulu menulis "Harga tenant memakai modal Gold + markup
 * toko". Itu sisa model LAMA: di model sekarang modal tenant =
 * `layanans.harga` x (1 + markup_platform[tier]), dan `harga_gold` sudah bukan
 * acuan apa pun.
 *
 * Akibatnya bukan sekadar salah tulis. Tenant membaca kalimat itu, mengira
 * modalnya = `harga_gold`, lalu menghitung untungnya sendiri dengan basis yang
 * salah. Ketika angkanya tidak cocok dengan laporan, jawabannya ada di teks
 * yang kita tulis sendiri — dan itu tidak bisa dijawab dengan "sistemnya
 * benar".
 *
 * Jadi tesnya mengunci dua hal:
 *   1. tidak ada lagi penyebutan Gold/Platinum sebagai dasar modal
 *   2. yang tampil adalah markup yang BENAR-BENAR berlaku untuk tenant itu
 */
class TenantPricingCopyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tenancy.disabled' => false,
            'app.url' => 'https://topupengine.test',
        ]);

        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        app(\App\Tenancy\TierPricingResolver::class)->flush();

        SettingWeb::query()->create([
            'judul_web' => 'Platform Uji',
            'deskripsi_web' => 'Deskripsi',
            'keywords' => 'uji',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/uji',
            'url_tiktok' => 'https://tiktok.com/@uji',
            'url_youtube' => 'https://youtube.com/uji',
            'url_fb' => 'https://facebook.com/uji',
            'topupindo_api' => 'topupindo',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'order_prefik' => 'INV',
            'paydisini_apikey' => 'key',
        ]);

        $owner = User::factory()->create(['role' => 'tenant_owner', 'balance' => 0]);

        $this->tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Warung Uji',
            'subdomain' => 'warung-uji',
            'tier' => 'enterprise',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 5],
        ]);

        Kategori::factory()->create(['tipe' => 'game', 'server_id' => 1]);
    }

    private function tenantHome(): string
    {
        return "https://{$this->tenant->subdomain}.topupengine.test/";
    }

    public function test_homepage_no_longer_claims_cost_basis_is_gold_column(): void
    {
        $response = $this->get($this->tenantHome());

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringNotContainsString(
            'modal Gold',
            $html,
            'toko tenant tidak boleh lagi menyebut "modal Gold" sebagai dasar perhitungan'
        );

        // Pemeriksaan yang lebih luas: jangan sampai penyebutan kolom legacy
        // ini kembali lewat kalimat lain.
        $this->assertDoesNotMatchRegularExpression(
            '/modal\s+(gold|platinum)/i',
            $html,
            'tidak boleh ada penyebutan modal Gold/Platinum di UI tenant'
        );
    }

    public function test_homepage_shows_the_markup_that_actually_applies(): void
    {
        $response = $this->get($this->tenantHome());

        $response->assertOk();
        $html = $response->getContent();

        $platform = app(\App\Tenancy\TenantPricingService::class)
            ->platformMarkupPercent($this->tenant);
        $effective = app(\App\Tenancy\TenantPricingService::class)
            ->effectiveMarkupPercent($this->tenant);

        // Angka dibaca dari service, bukan di-hardcode di tes: kalau seeder
        // harga tier berubah, tes ini ikut berubah tanpa perlu diedit.
        $this->assertStringContainsString(
            'markup platform',
            $html,
            'penjelasan harga harus menyebut markup platform'
        );

        $this->assertMatchesRegularExpression(
            '/markup toko\s+[\d.,]+%/i',
            $html,
            'penjelasan harga harus menyebut markup toko yang berlaku'
        );

        $this->assertSame(
            5.0,
            round($effective, 2),
            'prasyarat: markup efektif tenant ini 5%'
        );
        $this->assertStringContainsString('5%', $html);
        $this->assertGreaterThan(0, $platform);
    }
}

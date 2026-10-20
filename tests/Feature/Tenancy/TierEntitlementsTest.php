<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\TierPricing;
use App\Tenancy\TierEntitlements;
use App\Tenancy\TierPricingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Gate fitur per tier.
 *
 * Yang dijaga di sini adalah arah kegagalannya. Fitur yang tidak dikenal harus
 * DITOLAK (fail-closed), karena kebalikannya berarti salah tulis satu kunci di
 * JSON panel langsung membuka fitur berbayar untuk semua tier.
 *
 * Sekaligus dijaga: fail-closed tidak boleh mengunci platform yang sedang
 * berjalan. Tier yang belum punya daftar fitur di DB memakai peta default.
 */
class TierEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(string $tier, ?string $subdomain = null): Tenant
    {
        $subdomain ??= 'toko-' . $tier . '-' . substr(md5(uniqid('', true)), 0, 6);

        return Tenant::query()->create([
            'name' => 'Toko ' . $tier,
            'subdomain' => $subdomain,
            'tier' => $tier,
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 8],
        ]);
    }

    private function seedWithFeatures(array $featuresByTier): void
    {
        $this->seed(\Database\Seeders\TierPricingSeeder::class);

        foreach ($featuresByTier as $tier => $features) {
            TierPricing::query()->where('tier', $tier)->update(['features' => $features]);
        }

        app(TierPricingResolver::class)->flush();
        app(TierEntitlements::class)->flush();
    }

    public function test_tier_belum_diatur_memakai_peta_default(): void
    {
        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        app(TierPricingResolver::class)->flush();
        app(TierEntitlements::class)->flush();

        $entitlements = app(TierEntitlements::class);

        // Seeder mengisi `features` dengan LABEL tampilan, bukan kunci teknis,
        // jadi gate jatuh ke peta default — bukan kosong.
        $this->assertTrue($entitlements->has($this->makeTenant('starter'), 'theme_color'));
        $this->assertFalse($entitlements->has($this->makeTenant('starter'), 'custom_domain'));
        $this->assertTrue($entitlements->has($this->makeTenant('business'), 'custom_domain'));
    }

    public function test_fitur_tidak_dikenal_ditolak_bukan_diizinkan(): void
    {
        $this->seedWithFeatures(['enterprise' => ['subdomain', 'custom_domain']]);

        $entitlements = app(TierEntitlements::class);
        $tenant = $this->makeTenant('enterprise');

        $this->assertFalse($entitlements->has($tenant, 'tidak_ada_fitur_ini'));
        $this->assertFalse($entitlements->has($tenant, ''));
        $this->assertFalse($entitlements->has($tenant, 'CUSTOM_DOMAIN'));
    }

    public function test_kunci_teknis_di_json_menimpa_peta_default(): void
    {
        // Admin mematikan custom_domain untuk enterprise lewat panel.
        $this->seedWithFeatures(['enterprise' => ['subdomain', 'theme_color']]);

        $entitlements = app(TierEntitlements::class);

        $this->assertTrue($entitlements->has($this->makeTenant('enterprise'), 'theme_color'));
        $this->assertFalse($entitlements->has($this->makeTenant('enterprise'), 'custom_domain'));
    }

    public function test_label_tampilan_di_json_bukan_kunci_gate(): void
    {
        // Persis isi seeder: label seperti "Semua fitur Business" tidak boleh
        // dianggap kunci fitur.
        $this->seedWithFeatures([
            'business' => ['Semua fitur Starter', 'Domain sendiri (custom domain)', 'subdomain'],
        ]);

        $entitlements = app(TierEntitlements::class);
        $tenant = $this->makeTenant('business');

        $this->assertTrue($entitlements->has($tenant, 'subdomain'));
        $this->assertFalse($entitlements->has($tenant, 'Semua fitur Starter'));
        $this->assertFalse($entitlements->has($tenant, 'custom_domain'), 'label tidak membuka gate');
    }

    public function test_authorize_menolak_dengan_403(): void
    {
        $this->seedWithFeatures(['starter' => ['subdomain']]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(TierEntitlements::class)->authorize($this->makeTenant('starter'), 'custom_domain');
    }

    public function test_ubah_fitur_di_panel_langsung_berlaku_tanpa_flush_manual(): void
    {
        $this->seedWithFeatures(['business' => ['subdomain']]);

        $entitlements = app(TierEntitlements::class);
        $tenant = $this->makeTenant('business');

        $this->assertFalse($entitlements->has($tenant, 'custom_domain'));

        // Jalur yang dipakai Filament: update lewat model, bukan DB::table.
        TierPricing::query()->where('tier', 'business')->first()
            ->update(['features' => ['subdomain', 'custom_domain']]);

        $this->assertTrue($entitlements->has($tenant, 'custom_domain'));
    }

    public function test_tier_tidak_dikenal_jatuh_ke_starter(): void
    {
        $this->seedWithFeatures([]);

        $entitlements = app(TierEntitlements::class);

        // `tenants.tier` NOT NULL, jadi yang diuji adalah nilai tak dikenal
        // (mis. sisa data lama), bukan null.
        $tenant = $this->makeTenant('platinum-legacy');

        // Starter tidak punya custom_domain; jangan pernah terbuka karena nama
        // tier tidak dikenal.
        $this->assertFalse($entitlements->has($tenant, 'custom_domain'));
        $this->assertTrue($entitlements->has($tenant, 'subdomain'));
    }
}

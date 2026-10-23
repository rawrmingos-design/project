<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P5 — markup tenant tidak punya peringatan di form settings.
 *
 * Dua masalah:
 *  1. markup <= markup platform membuat profit tenant NOL, dan service cuma
 *     menulis `Log::warning` lalu diam-diam menaikkan ke platformMarkup + 0.01.
 *     Tenant tidak pernah tahu angka yang dia simpan bukan angka yang berlaku.
 *  2. markup sangat besar (mis. 1.000.000%) diizinkan tanpa peringatan, padahal
 *     membuat toko tidak kompetitif.
 *
 * Yang diuji: form settings WAJIB memberi tahu tenant, dan markup mustahil
 * ditolak saat disimpan (bukan diterima lalu dikoreksi diam-diam).
 */
class TenantMarkupGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false]);
        config(['app.url' => 'https://topupengine.test']);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        app()->forgetInstance('tenant');
        parent::tearDown();
    }

    private function makeTenant(string $subdomain, array $margin = ['markup_type' => 'percent', 'markup_value' => 10]): Tenant
    {
        $owner = User::factory()->create(['role' => 'tenant_owner']);

        return Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko ' . $subdomain,
            'subdomain' => $subdomain,
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => $margin,
        ]);
    }

    /** URL settings milik toko tenant. */
    private function settingsUrl(string $subdomain): string
    {
        return "https://{$subdomain}.topupengine.test/settings";
    }

    // ---------------------------------------------------------------- form ---

    public function test_form_menampilkan_markup_platform_sebagai_batas_minimum(): void
    {
        $tenant = $this->makeTenant('markup-form');

        $response = $this->actingAs($tenant->owner)
            ->get($this->settingsUrl('markup-form'));

        $response->assertOk();
        // Batas minimum = markup platform tier business (3%). Tenant harus
        // melihat angka ini, bukan menebak.
        $response->assertSee('3', false);
        $response->assertSeeInOrder(['markup', 'platform'], false);
    }

    public function test_form_memperlihatkan_markup_efektif_berbeda_dari_yang_tersimpan(): void
    {
        // Tenant tersimpan dengan markup 1% — di bawah markup platform 3%.
        // `sellPrice()` diam-diam memakai 3.01%, jadi angka tersimpan BUKAN
        // angka yang berlaku. Form harus menunjukkan selisih itu, bukan
        // menampilkan "1%" seolah itulah yang dipakai.
        $tenant = $this->makeTenant('markup-rendah', ['markup_type' => 'percent', 'markup_value' => 1]);

        $response = $this->actingAs($tenant->owner)
            ->get($this->settingsUrl('markup-rendah'));

        $response->assertOk();
        // Markup efektif yang benar-benar dipakai muncul di halaman.
        $response->assertSee('3.01');
        // Dan tenant diberi tahu bahwa markup tersimpan tidak berlaku.
        $response->assertSee('markup efektif');
    }

    // -------------------------------------------------------------- simpan ---

    public function test_markup_di_bawah_markup_platform_ditolak_saat_disimpan(): void
    {
        // Kalau diterima, `sellPrice()` diam-diam menaikkannya jadi 3.01% dan
        // tenant mengira dia memakai 1%. Lebih baik tolak dengan pesan jelas.
        $tenant = $this->makeTenant('markup-tolak');

        $response = $this->actingAs($tenant->owner)
            ->post($this->settingsUrl('markup-tolak'), [
                'name' => $tenant->name,
                'primary_color' => '#A855F7',
                'accent_color' => '#06B6D4',
                'contact_whatsapp' => null,
                'markup_type' => 'percent',
                'markup_value' => 1,
            ]);

        $response->assertSessionHasErrors('markup_value');

        $tenant->refresh();
        $this->assertSame(10, (int) $tenant->margin_config['markup_value'], 'markup lama tidak boleh berubah');
    }

    public function test_markup_sama_dengan_markup_platform_ditolak(): void
    {
        $tenant = $this->makeTenant('markup-sama');

        $response = $this->actingAs($tenant->owner)
            ->post($this->settingsUrl('markup-sama'), [
                'name' => $tenant->name,
                'primary_color' => '#A855F7',
                'accent_color' => '#06B6D4',
                'contact_whatsapp' => null,
                'markup_type' => 'percent',
                'markup_value' => 3,
            ]);

        $response->assertSessionHasErrors('markup_value');
    }

    public function test_markup_ekstrem_ditolak_dengan_batas_atas_masuk_akal(): void
    {
        $tenant = $this->makeTenant('markup-ekstrem');

        $response = $this->actingAs($tenant->owner)
            ->post($this->settingsUrl('markup-ekstrem'), [
                'name' => $tenant->name,
                'primary_color' => '#A855F7',
                'accent_color' => '#06B6D4',
                'contact_whatsapp' => null,
                'markup_type' => 'percent',
                'markup_value' => 1000000,
            ]);

        $response->assertSessionHasErrors('markup_value');
    }

    public function test_markup_wajar_tetap_diterima(): void
    {
        $tenant = $this->makeTenant('markup-wajar');

        $response = $this->actingAs($tenant->owner)
            ->post($this->settingsUrl('markup-wajar'), [
                'name' => 'Toko Wajar Baru',
                'primary_color' => '#A855F7',
                'accent_color' => '#06B6D4',
                'contact_whatsapp' => null,
                'markup_type' => 'percent',
                'markup_value' => 12.5,
            ]);

        $response->assertSessionHasNoErrors();

        $tenant->refresh();
        $this->assertSame(12.5, (float) $tenant->margin_config['markup_value']);
    }

    // ------------------------------------------------------------- service ---

    public function test_service_melaporkan_batas_bawah_yang_sama_dengan_form(): void
    {
        // Sumber kebenaran ada di service, bukan di controller. Form harus
        // menampilkan angka yang benar-benar dipakai saat menghitung.
        $tenant = $this->makeTenant('markup-service');
        app(TenantContext::class)->set($tenant);

        $service = app(TenantPricingService::class);

        $this->assertSame(3.0, $service->platformMarkupPercent($tenant));
        $this->assertSame(3.0, $service->minimumTenantMarkupPercent($tenant));
    }
}

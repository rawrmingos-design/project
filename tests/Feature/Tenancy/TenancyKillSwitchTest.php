<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembelian;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PublicSiteConfigService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyKillSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        app()->forgetInstance('tenant');
        parent::tearDown();
    }

    public function test_saas_tenancy_is_disabled_by_default(): void
    {
        config(['app.url' => 'https://topupengine.test']);
        Kategori::factory()->create(['kode' => 'mobile-legends']);
        $this->makeTenant([
            'name' => 'Disabled Store',
            'subdomain' => 'disabled-store',
        ]);

        $this->assertTrue((bool) config('tenancy.disabled'));

        $this->get('https://disabled-store.topupengine.test/')
            ->assertNotFound();

        $this->assertFalse(app(TenantContext::class)->has());
        $this->assertFalse(app()->bound('tenant'));
    }

    public function test_tenant_storefront_works_when_kill_switch_is_disabled(): void
    {
        config([
            'app.url' => 'https://topupengine.test',
            'tenancy.disabled' => false,
        ]);
        Kategori::factory()->create(['kode' => 'mobile-legends']);
        $this->makeTenant([
            'name' => 'Enabled Store',
            'subdomain' => 'enabled-store',
        ]);

        $this->get('https://enabled-store.topupengine.test/')
            ->assertOk()
            ->assertSee('Enabled Store');
    }

    public function test_kill_switch_blocks_self_service_onboarding(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $this->get('https://topupengine.test/id/reseller-topup')
            ->assertNotFound();

        $this->getJson('/api/subdomain/check?name=fresh-store')
            ->assertNotFound();

        $this->postJson('/api/tenant/register', [
            'name' => 'Raka Reseller',
            'email' => 'raka@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'Raka Topup',
            'subdomain' => 'raka-topup',
            'tier' => 'starter',
            'terms_accepted' => true,
        ])->assertNotFound();

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseMissing('users', ['email' => 'raka@example.test']);
    }

    public function test_kill_switch_blocks_tenant_host_api_v2_but_keeps_public_host_api_v2_available(): void
    {
        config(['app.url' => 'https://topupengine.test']);
        $this->makeTenant([
            'name' => 'API Store',
            'subdomain' => 'api-store',
        ]);

        $category = Kategori::factory()->create([
            'tipe' => 'joki',
            'require_user_id' => false,
        ]);
        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Joki Rank Epic',
            'provider' => 'manual',
            'provider_id' => 'joki-rank-epic',
            'harga_member' => 100000,
            'harga_platinum' => 100000,
            'harga_gold' => 100000,
            'profit_member' => 10000,
            'profit_platinum' => 10000,
            'profit_gold' => 10000,
        ]);
        Method::query()->create([
            'name' => 'Manual Transfer',
            'code' => 'MANUAL',
            'payment' => 'manual',
            'tipe' => 'manual',
            'images' => 'manual.png',
            'keterangan' => 'Manual transfer',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);
        $payload = [
            'service' => $service->id,
            'payment_method' => 'MANUAL',
            'nomor' => '081234567890',
            'ktg_tipe' => 'joki',
            'qty' => 1,
            'email_joki' => 'player@example.test',
            'password_joki' => 'secret-pass',
            'loginvia_joki' => 'Moonton',
            'nickname_joki' => 'PlayerOne',
            'request_joki' => 'Push sampai Legend',
            'catatan_joki' => 'Main malam',
        ];

        $this->postJson('https://api-store.topupengine.test/api/v2/order/store', $payload)
            ->assertNotFound();
        $this->assertDatabaseCount('pembelians', 0);

        $this->postJson('https://topupengine.test/api/v2/order/store', $payload)
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseCount('pembelians', 1);
        $this->assertNull(Pembelian::query()->firstOrFail()->tenant_id);
    }

    public function test_public_site_config_hides_reseller_topup_link_when_disabled(): void
    {
        $shared = app(PublicSiteConfigService::class)->sharedProps();

        $this->assertFalse($shared['featureFlags']['saasTenancyEnabled']);

        $labels = collect($shared['siteConfig']['footerColumns'])
            ->flatMap(fn (array $column) => collect($column['items'])->pluck('label'))
            ->all();

        $this->assertNotContains('Reseller Topup', $labels);
    }

    private function makeTenant(array $attributes = []): Tenant
    {
        $owner = User::factory()->create(['role' => 'Gold']);

        return Tenant::query()->create(array_merge([
            'owner_user_id' => $owner->id,
            'name' => 'Tenant Store',
            'subdomain' => 'tenant-store',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ], $attributes));
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pembelians\Pages\ViewPembelian;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\ProviderPath;
use App\Models\User;
use App\Services\ResetDomainService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

class AdminResetDetailEditabilityTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_reset_detail_page_shows_provider_text_constrained_provider_choices_and_reset_only_edit_controls(): void
    {
        $admin = $this->createAdminUser();
        $digiflazz = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $digiflazzPath = $this->createProviderPath($digiflazz, 'digiflazz', 'SKU-WP');
        $vip = $this->createProviderPath($digiflazz, 'vip', 'VIP-WP');
        $invalidSku = $this->createProviderPath($digiflazz, 'bangjeff', 'BJ-WP', status: 'available');
        $pembelian = $this->createPembelian($digiflazz);

        $this->actingAs($admin);

        $pembelian = app(ResetDomainService::class)->executeReset($pembelian, $vip->id, $admin->id, 'Reset for manual detail edit');

        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->assertSee('Provider Aktif')
            ->assertSee('Vip')
            ->assertSee('Reset Context')
            ->assertSee('Requested')
            ->assertActionVisible('edit_reset_routing')
            ->assertActionHidden('reset_invoice')
            ->assertSet('providerSelectOptions', [
                $digiflazzPath->id => 'Digiflazz (SKU-WP)',
                $invalidSku->id => 'Bangjeff (BJ-WP)',
            ])
            ->callAction('edit_reset_routing', data: [
                'candidate_provider_id' => $digiflazzPath->id,
                'user_id' => '88990011',
                'zone' => '9999',
            ])
            ->assertHasNoActionErrors();

        $pembelian->refresh();

        $this->assertSame('88990011', $pembelian->user_id);
        $this->assertSame('9999', $pembelian->zone);
        $this->assertSame($digiflazz->id, $pembelian->active_layanan_id);
        $this->assertSame('digiflazz', $pembelian->active_provider_code);
        $this->assertSame('SKU-WP', $pembelian->active_provider_sku);
        $this->assertNotSame('BJ-WP', $pembelian->active_provider_sku);
    }

    public function test_non_reset_detail_keeps_edit_controls_hidden_and_renders_safe_provider_fallback(): void
    {
        $admin = $this->createAdminUser();
        $pembelian = $this->createPembelian(null, [
            'order_id' => 'INV-RESET-DETAIL-FALLBACK-001',
            'layanan' => 'Legacy Weekly Pass',
            'active_layanan_id' => null,
            'active_provider_code' => null,
            'active_provider_sku' => null,
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->assertSee('Provider Aktif')
            ->assertSee('Provider context unavailable')
            ->assertSee('Reset Context')
            ->assertSee('Not reset')
            ->assertActionHidden('edit_reset_routing');
    }

    public function test_reset_detail_rejects_candidate_that_becomes_unavailable_before_save_and_keeps_current_provider(): void
    {
        $admin = $this->createAdminUser();
        $digiflazz = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $digiflazzPath = $this->createProviderPath($digiflazz, 'digiflazz', 'SKU-WP');
        $vip = $this->createProviderPath($digiflazz, 'vip', 'VIP-WP');
        $pembelian = $this->createPembelian($digiflazz);

        $this->actingAs($admin);

        $pembelian = app(ResetDomainService::class)->executeReset($pembelian, $vip->id, $admin->id, 'Reset to test availability drift');

        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->assertActionVisible('edit_reset_routing')
            ->assertSet('providerSelectOptions', [
                $digiflazzPath->id => 'Digiflazz (SKU-WP)',
            ]);

        $digiflazzPath->update(['status' => 'inactive']);

        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->assertSet('providerSelectOptions', [])
            ->assertActionDisabled('edit_reset_routing');

        $pembelian->refresh();

        $this->assertSame('2001', $pembelian->zone);
        $this->assertSame($digiflazz->id, $pembelian->active_layanan_id);
        $this->assertSame('vip', $pembelian->active_provider_code);
        $this->assertSame('VIP-WP', $pembelian->active_provider_sku);
    }

    private function createAdminUser(): User
    {
        return User::create([
            'name' => 'Admin Reset',
            'username' => 'admin-reset-detail',
            'email' => 'admin-reset-detail@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'balance' => 0,
            'point_balance' => 0,
            'email_verified_at' => now(),
        ]);
    }

    private function createPembelian(?Layanan $activeLayanan = null, array $overrides = [], string $paymentStatus = 'Lunas'): Pembelian
    {
        $pembelian = Pembelian::create(array_merge([
            'order_id' => 'INV-RESET-DETAIL-001',
            'username' => 'reset-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Reset User',
            'layanan' => $activeLayanan?->layanan ?? 'Weekly Pass',
            'active_layanan_id' => $activeLayanan?->id,
            'active_provider_code' => $activeLayanan?->provider,
            'active_provider_sku' => $activeLayanan?->provider_id,
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Failed',
            'tipe_transaksi' => 'game',
        ], $overrides));

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => $paymentStatus,
            'metode' => 'QRIS',
            'reference' => 'REF-' . $pembelian->order_id,
        ]);

        return $pembelian->fresh(['activeLayanan', 'pembayaran']);
    }

    private function createLayanan(string $layanan, string $providerId, string $provider, string $status = 'active'): Layanan
    {
        return Layanan::create([
            'kategori_id' => '1',
            'layanan' => $layanan,
            'provider_id' => $providerId,
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => $status,
            'provider' => $provider,
            'catatan' => 'Test service',
            'is_flash_sale' => 0,
        ]);
    }

    private function createProviderPath(Layanan $layanan, string $providerCode, string $providerSku, string $status = 'available'): ProviderPath
    {
        return ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => $providerCode,
            'provider_sku' => $providerSku,
            'modal_price' => 10000,
            'priority' => 1,
            'status' => $status,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pembelians\Pages\ViewPembelian;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\ProviderPath;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

class FailedTransactionResetActionTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_eligible_failed_paid_transaction_shows_and_executes_reset_invoice_action(): void
    {
        $admin = $this->createAdminUser();
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $candidate = $this->createProviderPath($currentLayanan, 'vip', 'VIP-WP');
        $pembelian = $this->createPembelian($currentLayanan);

        $this->actingAs($admin);

        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->assertActionVisible('reset_invoice')
            ->assertActionHidden('edit_reset_routing')
            ->callAction('reset_invoice', data: [
                'candidate_provider_id' => $candidate->id,
                'reason' => 'Admin retry with validated provider',
            ])
            ->assertHasNoActionErrors();

        $pembelian->refresh();

        $this->assertSame(1, $pembelian->invoice_version);
        $this->assertSame('INV-RESET-ACTION-001_001', $pembelian->display_order_id);
        $this->assertSame($currentLayanan->id, $pembelian->active_layanan_id);
        $this->assertSame('vip', $pembelian->active_provider_code);
        $this->assertSame('VIP-WP', $pembelian->active_provider_sku);
        $this->assertSame('requested', $pembelian->reset_status);
        $this->assertSame($admin->id, $pembelian->reset_requested_by);
        $this->assertSame('Admin retry with validated provider', $pembelian->reset_reason);
    }

    public function test_reset_invoice_action_is_hidden_for_non_eligible_records(): void
    {
        $admin = $this->createAdminUser();
        $currentLayanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $candidate = $this->createProviderPath($currentLayanan, 'vip', 'VIP-WP');
        $successfulPembelian = $this->createPembelian($currentLayanan, [
            'order_id' => 'INV-RESET-ACTION-SUCCESS-001',
            'status' => 'Sukses',
        ], paymentStatus: 'Lunas');

        $this->actingAs($admin);

        Livewire::test(ViewPembelian::class, ['record' => $successfulPembelian->getRouteKey()])
            ->assertActionHidden('reset_invoice')
            ->assertActionHidden('edit_reset_routing');

        $successfulPembelian->refresh();

        $this->assertSame(0, $successfulPembelian->invoice_version);
        $this->assertSame($currentLayanan->id, $successfulPembelian->active_layanan_id);
        $this->assertSame('digiflazz', $successfulPembelian->active_provider_code);
        $this->assertSame('SKU-WP', $successfulPembelian->active_provider_sku);
        $this->assertNotSame('VIP-WP', $successfulPembelian->active_provider_sku);
    }

    private function createAdminUser(): User
    {
        return User::create([
            'name' => 'Admin Reset',
            'username' => 'admin-reset',
            'email' => 'admin-reset@example.com',
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
            'order_id' => 'INV-RESET-ACTION-001',
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

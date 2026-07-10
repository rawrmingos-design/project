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

class RepeatedResetVersioningTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_reset_action_advances_suffix_deterministically_across_repeated_resets(): void
    {
        $admin = $this->createAdminUser();
        $digiflazz = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $vip = $this->createProviderPath($digiflazz, 'vip', 'VIP-WP');
        $bangjeff = $this->createProviderPath($digiflazz, 'bangjeff', 'BJ-WP');
        $digiflazzPath = $this->createProviderPath($digiflazz, 'digiflazz', 'SKU-WP');
        $pembelian = $this->createPembelian($digiflazz);

        $this->actingAs($admin);

        $cycles = [
            [$vip->id, 1, 'INV-RESET-REPEATED-001_001'],
            [$bangjeff->id, 2, 'INV-RESET-REPEATED-001_002'],
            [$digiflazzPath->id, 3, 'INV-RESET-REPEATED-001_003'],
        ];

        foreach ($cycles as $index => [$candidateId, $expectedVersion, $expectedDisplayId]) {
            Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
                ->assertActionVisible('reset_invoice')
                ->callAction('reset_invoice', data: [
                    'candidate_provider_id' => $candidateId,
                    'reason' => 'Repeated reset cycle ' . ($index + 1),
                ])
                ->assertHasNoActionErrors();

            $pembelian->refresh();

            $this->assertSame('INV-RESET-REPEATED-001', $pembelian->order_id);
            $this->assertSame('INV-RESET-REPEATED-001', $pembelian->base_order_id);
            $this->assertSame($expectedVersion, $pembelian->invoice_version);
            $this->assertSame($expectedDisplayId, $pembelian->display_order_id);
            $this->assertSame($expectedDisplayId, $pembelian->active_attempt_reference);

            if ($expectedVersion < 3) {
                $pembelian->update([
                    'status' => 'Failed',
                    'reset_status' => 'failed',
                ]);
            }
        }
    }

    private function createAdminUser(): User
    {
        return User::create([
            'name' => 'Admin Reset',
            'username' => 'admin-reset-repeated',
            'email' => 'admin-reset-repeated@example.com',
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
            'order_id' => 'INV-RESET-REPEATED-001',
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

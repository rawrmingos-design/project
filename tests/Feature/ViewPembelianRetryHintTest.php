<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pembelians\Pages\ViewPembelian;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewPembelianRetryHintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_view_pembelian_shows_retry_hint_for_vip_without_provider_reference(): void
    {
        $admin = $this->createAdminUser();
        $pembelian = $this->createPembelian([
            'order_id' => 'INV-VIP-RETRY-HINT-001',
            'active_provider_code' => 'vip',
            'active_provider_sku' => 'VIP-WP',
            'provider_order_id' => null,
            'active_attempt_token' => null,
            'status' => 'Failed',
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->assertSee('Retry Status Check')
            ->assertSee('Retry status check untuk VIP butuh trxid/provider_order_id. Gunakan Reset Invoice setelah saldo/provider sudah siap.');
    }

    private function createAdminUser(): User
    {
        return User::create([
            'name' => 'Admin View Retry',
            'username' => 'admin-view-retry',
            'email' => 'admin-view-retry@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'balance' => 0,
            'point_balance' => 0,
            'email_verified_at' => now(),
        ]);
    }

    private function createPembelian(array $overrides = [], string $paymentStatus = 'Lunas'): Pembelian
    {
        $pembelian = Pembelian::create(array_merge([
            'order_id' => 'INV-VIEW-RETRY-HINT-001',
            'username' => 'retry-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Retry User',
            'layanan' => 'Weekly Pass',
            'active_layanan_id' => null,
            'active_provider_code' => 'manual',
            'active_provider_sku' => 'MANUAL-WP',
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

        return $pembelian->fresh('pembayaran');
    }
}

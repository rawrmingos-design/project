<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Pembelians\Pages\ViewPembelian;
use App\Jobs\SendPembelianToProviderJob;
use App\Models\Layanan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\ProviderPath;
use App\Models\User;
use App\Services\ResetDomainService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\AdminTestCase;

class SendCallbackQueueDispatchTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_send_callback_action_dispatches_background_job_for_reset_attempt(): void
    {
        Queue::fake();

        $admin = $this->createAdminUser();
        $layanan = $this->createLayanan('Weekly Pass', 'SKU-WP', 'digiflazz');
        $this->createProviderPath($layanan, 'digiflazz', 'SKU-WP');
        $vip = $this->createProviderPath($layanan, 'vip', 'VIP-WP');
        $pembelian = $this->createPembelian($layanan);

        $this->actingAs($admin);

        $pembelian = app(ResetDomainService::class)->executeReset($pembelian, $vip->id, $admin->id, 'Queue provider dispatch');

        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->assertActionVisible('send_callback')
            ->callAction('send_callback')
            ->assertHasNoActionErrors();

        Queue::assertPushed(SendPembelianToProviderJob::class, function (SendPembelianToProviderJob $job) use ($pembelian, $admin): bool {
            return $job->pembelianId === $pembelian->id
                && $job->requestedBy === $admin->id;
        });

        $pembelian->refresh();

        $this->assertSame('processing', $pembelian->reset_status);
        $this->assertStringContainsString('Provider dispatch queued', (string) $pembelian->log);
    }

    private function createAdminUser(): User
    {
        return User::create([
            'name' => 'Admin Queue',
            'username' => 'admin-queue',
            'email' => 'admin-queue@example.com',
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
            'order_id' => 'INV-SEND-CALLBACK-QUEUE-001',
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

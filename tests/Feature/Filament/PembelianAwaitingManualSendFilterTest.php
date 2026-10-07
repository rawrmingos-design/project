<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Pembelians\Pages\ListPembelians;
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

/**
 * Filter "Menunggu Kirim ke Provider" di daftar order.
 *
 * Setelah "Reset Invoice" hanya menyiapkan attempt (tidak lagi mengirim ke provider),
 * attempt yang belum dikirim bisa menggantung di Pending. Filter ini yang membuatnya
 * bisa dicari dari daftar, bukan cuma terlihat satu per satu di halaman detail.
 */
class PembelianAwaitingManualSendFilterTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_scope_only_returns_attempts_awaiting_manual_send(): void
    {
        $layanan = $this->createLayanan();
        $this->createProviderPath($layanan);

        $awaiting = $this->createPembelian($layanan, 'INV-AWAIT-001', 'Gagal');
        $alreadySent = $this->createPembelian($layanan, 'INV-AWAIT-002', 'Gagal');
        $neverReset = $this->createPembelian($layanan, 'INV-AWAIT-003', 'Gagal');

        $admin = $this->createAdminUser();

        // Reset: attempt dibuat, reset_status = 'requested', belum dikirim.
        app(ResetDomainService::class)->executeReset($awaiting, null, $admin->id, 'menunggu kirim');

        // Reset lalu dikirim: reset_status menjadi 'processing'.
        $reset = app(ResetDomainService::class)->executeReset($alreadySent, null, $admin->id, 'sudah dikirim');
        $reset->forceFill(['reset_status' => 'processing'])->saveQuietly();

        $ids = Pembelian::query()->awaitingManualSend()->pluck('order_id')->all();

        $this->assertContains('INV-AWAIT-001', $ids);
        $this->assertNotContains('INV-AWAIT-002', $ids, 'Attempt yang sudah dikirim tidak boleh ikut.');
        $this->assertNotContains('INV-AWAIT-003', $ids, 'Order tanpa reset tidak boleh ikut.');
    }

    public function test_filter_is_available_and_narrows_the_table(): void
    {
        Queue::fake();

        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $layanan = $this->createLayanan();
        $this->createProviderPath($layanan);

        $awaiting = $this->createPembelian($layanan, 'INV-FILTER-AWAIT', 'Gagal');
        $neverReset = $this->createPembelian($layanan, 'INV-FILTER-PLAIN', 'Gagal');

        app(ResetDomainService::class)->executeReset($awaiting, null, $admin->id, 'filter test');

        Livewire::test(ListPembelians::class)
            ->assertTableFilterExists('awaiting_manual_send')
            ->filterTable('awaiting_manual_send')
            ->assertCanSeeTableRecords([$awaiting->fresh()])
            ->assertCanNotSeeTableRecords([$neverReset->fresh()]);
    }

    /**
     * Order yang belum pernah direset tidak boleh menampilkan badge "MENUNGGU".
     */
    public function test_marker_column_reflects_state_without_filtering(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $layanan = $this->createLayanan();
        $this->createProviderPath($layanan);

        $awaiting = $this->createPembelian($layanan, 'INV-BADGE-AWAIT', 'Gagal');
        $neverReset = $this->createPembelian($layanan, 'INV-BADGE-PLAIN', 'Gagal');

        app(ResetDomainService::class)->executeReset($awaiting, null, $admin->id, 'badge test');

        $this->assertTrue($awaiting->fresh()->isAwaitingManualSend());
        $this->assertFalse($neverReset->fresh()->isAwaitingManualSend());

        Livewire::test(ListPembelians::class)
            ->assertCanSeeTableRecords([$awaiting->fresh(), $neverReset->fresh()]);
    }

    /**
     * Filter harus aman dipakai meski tidak ada satu pun order yang menunggu.
     */
    public function test_filter_returns_empty_when_nothing_awaits(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $layanan = $this->createLayanan();
        $this->createProviderPath($layanan);
        $this->createPembelian($layanan, 'INV-FILTER-EMPTY', 'Gagal');

        Livewire::test(ListPembelians::class)
            ->filterTable('awaiting_manual_send')
            ->assertCanNotSeeTableRecords(Pembelian::all());
    }

    private function createAdminUser(): User
    {
        return User::create([
            'name' => 'Admin Filter',
            'username' => 'admin-filter-await',
            'email' => 'admin-filter-await@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'balance' => 0,
            'point_balance' => 0,
            'email_verified_at' => now(),
        ]);
    }

    private function createPembelian(Layanan $layanan, string $orderId, string $status): Pembelian
    {
        $pembelian = Pembelian::create([
            'order_id' => $orderId,
            'username' => 'filter-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Filter User',
            'layanan' => $layanan->layanan,
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => $layanan->provider,
            'active_provider_sku' => $layanan->provider_id,
            'harga' => 15000,
            'profit' => 1000,
            'status' => $status,
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-' . $pembelian->order_id,
        ]);

        return $pembelian->fresh(['activeLayanan', 'pembayaran']);
    }

    private function createLayanan(): Layanan
    {
        return Layanan::create([
            'kategori_id' => '1',
            'layanan' => 'Weekly Pass',
            'provider_id' => 'SKU-WP',
            'harga' => 15000,
            'harga_member' => 14500,
            'harga_platinum' => 14000,
            'harga_gold' => 13500,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => 'active',
            'provider' => 'digiflazz',
            'catatan' => 'Test service',
            'is_flash_sale' => 0,
        ]);
    }

    private function createProviderPath(Layanan $layanan): ProviderPath
    {
        return ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'digiflazz',
            'provider_sku' => 'SKU-WP',
            'modal_price' => 10000,
            'priority' => 1,
            'status' => 'available',
        ]);
    }
}

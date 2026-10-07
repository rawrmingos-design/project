<?php

namespace Tests\Feature\Filament;

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

/**
 * Reset Invoice harus MENYIAPKAN attempt saja, bukan mengirimnya.
 *
 * Bug yang dijaga di sini (dilaporkan client): sebelum perbaikan, aksi "Reset Invoice"
 * langsung men-dispatch SendPembelianToProviderJob dengan mode 'auto'. Provider pun
 * menerima invoice baru bernomor `<order_id>_001` sebelum admin sempat memeriksa ID/zone.
 * Padahal copy aksi "Edit Reset Routing" sendiri mengatakan attempt baru boleh dikoreksi
 * "sebelum klik Send Callback" — jadi kode bertentangan dengan UI-nya, dan aksi
 * "Edit Reset Routing" tidak berguna karena jendelanya balapan dengan job.
 *
 * Kontrak baru:
 * - Reset  : invoice_version + 1, reset_status = 'requested', TIDAK ada job dikirim.
 * - Kirim  : hanya aksi "Send Callback".
 */
class ResetInvoiceRequiresManualSendTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /**
     * Inti perbaikan: reset tidak mengirim apa pun ke provider.
     */
    public function test_reset_invoice_does_not_dispatch_job_to_provider(): void
    {
        Queue::fake();

        $admin = $this->createAdminUser();
        $layanan = $this->createLayanan();
        $this->createProviderPath($layanan);
        $pembelian = $this->createResetEligiblePembelian($layanan);

        $this->actingAs($admin);

        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->assertActionVisible('reset_invoice')
            ->callAction('reset_invoice', ['reason' => 'Client minta retry'])
            ->assertHasNoActionErrors();

        // Tidak boleh ada order yang dikirim ke provider.
        Queue::assertNotPushed(SendPembelianToProviderJob::class);

        $pembelian->refresh();
        $this->assertSame(1, (int) $pembelian->invoice_version);
        $this->assertSame('requested', $pembelian->reset_status);
    }

    /**
     * Attempt yang belum dikirim harus punya penanda jelas di halaman order,
     * supaya admin tidak lupa mengklik "Send Callback".
     */
    public function test_reset_marks_order_as_awaiting_manual_send(): void
    {
        Queue::fake();

        $admin = $this->createAdminUser();
        $layanan = $this->createLayanan();
        $this->createProviderPath($layanan);
        $pembelian = $this->createResetEligiblePembelian($layanan);

        $this->actingAs($admin);

        $this->assertFalse($pembelian->isAwaitingManualSend(), 'Order belum direset, seharusnya belum menunggu.');

        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->callAction('reset_invoice', ['reason' => 'Cek penanda'])
            ->assertHasNoActionErrors();

        $pembelian->refresh();

        $this->assertTrue($pembelian->isAwaitingManualSend(), 'Attempt baru harus menunggu dikirim manual.');
        $this->assertSame('INV-RESET-MANUAL-001_001', $pembelian->display_order_id);
    }

    /**
     * Setelah admin mengklik "Send Callback", penanda harus hilang dan barulah
     * job dikirim ke provider — inilah satu-satunya jalur pengiriman.
     */
    public function test_send_callback_is_the_only_dispatch_path_and_clears_the_marker(): void
    {
        Queue::fake();

        $admin = $this->createAdminUser();
        $layanan = $this->createLayanan();
        $this->createProviderPath($layanan);
        $pembelian = $this->createResetEligiblePembelian($layanan);

        $this->actingAs($admin);

        // Reset dulu: attempt dibuat, belum dikirim.
        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->callAction('reset_invoice', ['reason' => 'Reset dulu'])
            ->assertHasNoActionErrors();

        Queue::assertNotPushed(SendPembelianToProviderJob::class);
        $this->assertTrue($pembelian->fresh()->isAwaitingManualSend());

        // Baru kemudian admin mengirim.
        Livewire::test(ViewPembelian::class, ['record' => $pembelian->getRouteKey()])
            ->assertActionVisible('send_callback')
            ->callAction('send_callback')
            ->assertHasNoActionErrors();

        Queue::assertPushed(SendPembelianToProviderJob::class, function (SendPembelianToProviderJob $job) use ($pembelian): bool {
            return $job->pembelianId === $pembelian->id;
        });

        $pembelian->refresh();

        $this->assertSame('processing', $pembelian->reset_status);
        $this->assertFalse($pembelian->isAwaitingManualSend(), 'Setelah dikirim, penanda harus hilang.');
    }

    /**
     * Order yang belum pernah direset tidak boleh menampilkan penanda apa pun.
     */
    public function test_order_without_reset_never_awaits_manual_send(): void
    {
        $layanan = $this->createLayanan();
        $this->createProviderPath($layanan);
        $pembelian = $this->createResetEligiblePembelian($layanan);

        $this->assertSame(0, (int) $pembelian->invoice_version);
        $this->assertFalse($pembelian->isAwaitingManualSend());
    }

    private function createAdminUser(): User
    {
        return User::create([
            'name' => 'Admin Reset',
            'username' => 'admin-reset-manual',
            'email' => 'admin-reset-manual@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'balance' => 0,
            'point_balance' => 0,
            'email_verified_at' => now(),
        ]);
    }

    private function createResetEligiblePembelian(Layanan $layanan): Pembelian
    {
        $pembelian = Pembelian::create([
            'order_id' => 'INV-RESET-MANUAL-001',
            'username' => 'reset-manual-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Reset Manual User',
            'layanan' => $layanan->layanan,
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => $layanan->provider,
            'active_provider_sku' => $layanan->provider_id,
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Gagal', // isResetEligible() butuh status Gagal/Batal
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

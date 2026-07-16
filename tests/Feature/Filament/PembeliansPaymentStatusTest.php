<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Pembelians\Pages\ListPembelians;
use App\Filament\Admin\Resources\Pembelians\Widgets\PaymentStatusStatsOverview;
use App\Filament\Admin\Resources\Pembelians\Widgets\PembelianStatusStatsOverview;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

class PembeliansPaymentStatusTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_see_expired_payment_badge_and_filter_by_payment_status(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $expiredPembelian = $this->createPembelian('INV-PAY-EXPIRED', 'Expired', 'Expired');
        $successPembelian = $this->createPembelian('INV-PAY-SUCCESS', 'Success', 'Lunas');
        $pendingPembelian = $this->createPembelian('INV-PAY-PENDING', 'Pending', 'Belum Lunas');

        Livewire::test(ListPembelians::class)
            ->assertCanSeeTableRecords([$expiredPembelian, $successPembelian, $pendingPembelian])
            ->assertTableColumnStateSet('pembayaran.status', 'Expired', $expiredPembelian)
            ->assertTableColumnStateSet('pembayaran.status', 'Success', $successPembelian)
            ->assertTableColumnStateSet('pembayaran.status', 'Pending', $pendingPembelian)
            ->filterTable('payment_status', 'expired')
            ->assertCanSeeTableRecords([$expiredPembelian])
            ->assertCanNotSeeTableRecords([$successPembelian, $pendingPembelian]);
    }

    public function test_provider_status_filter_remains_independent_from_payment_status(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $failedPembelian = $this->createPembelian('INV-PAY-FAILED', 'Failed', 'Expired');
        $pendingPembelian = $this->createPembelian('INV-PAY-PENDING', 'Pending', 'Belum Lunas');

        Livewire::test(ListPembelians::class)
            ->filterTable('status', 'failed')
            ->assertCanSeeTableRecords([$failedPembelian])
            ->assertCanNotSeeTableRecords([$pendingPembelian]);
    }

    public function test_pembelians_page_renders_profit_payment_and_order_widgets(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->createPembelian('INV-WIDGET-PENDING', 'Pending', 'Belum Lunas');

        Livewire::test(ListPembelians::class)
            ->assertSee('Widget Profit')
            ->assertSee('Status Pembayaran')
            ->assertSee('Status Pembelian');
    }

    public function test_payment_widget_can_switch_to_expired_status(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->createPembelian('INV-WIDGET-PAY-EXPIRED-1', 'Expired', 'Expired');
        $this->createPembelian('INV-WIDGET-PAY-EXPIRED-2', 'Expired', 'Expired');
        $this->createPembelian('INV-WIDGET-PAY-SUCCESS', 'Success', 'Lunas');

        Livewire::test(PaymentStatusStatsOverview::class)
            ->set('paymentPeriod', 'all_time')
            ->set('paymentStatus', 'expired')
            ->assertSee('Payment gateway: Expired')
            ->assertSee('2');
    }

    public function test_pembelian_widget_can_switch_to_expired_status(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $this->createPembelian('INV-WIDGET-ORDER-EXPIRED-1', 'Expired', 'Expired');
        $this->createPembelian('INV-WIDGET-ORDER-EXPIRED-2', 'Expired', 'Expired');
        $this->createPembelian('INV-WIDGET-ORDER-PENDING', 'Pending', 'Belum Lunas');

        Livewire::test(PembelianStatusStatsOverview::class)
            ->set('orderPeriod', 'all_time')
            ->set('orderStatus', 'expired')
            ->assertSee('Provider/supplier: Expired')
            ->assertSee('2');
    }

    public function test_expired_payment_sync_marks_pembelian_expired(): void
    {
        $pembelian = $this->createPembelian('INV-SYNC-EXPIRED', 'Pending', 'Belum Lunas');
        $payment = $pembelian->pembayaran;
        $payment->update(['expired_at' => now()->subMinute()]);

        $this->assertTrue($payment->refresh()->syncExpiredStatus());

        $payment->refresh();
        $pembelian->refresh();

        $this->assertSame('Expired', $payment->status);
        $this->assertSame('Expired', $pembelian->status);
        $this->assertStringContainsString('Payment expired at', (string) $pembelian->log);
    }

    public function test_expired_payment_sync_does_not_downgrade_successful_pembelian(): void
    {
        $pembelian = $this->createPembelian('INV-SYNC-SUCCESS', 'Sukses', 'Expired');
        $payment = $pembelian->pembayaran;

        $this->assertFalse($payment->syncExpiredPembelianStatus());

        $this->assertSame('Sukses', $pembelian->refresh()->status);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'Admin',
        ]);
    }

    private function createPembelian(string $orderId, string $status, string $paymentStatus): Pembelian
    {
        $pembelian = Pembelian::create([
            'order_id' => $orderId,
            'username' => 'admin-resource-user',
            'user_id' => '123456',
            'zone' => '1234',
            'nickname' => 'Resource User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 500,
            'status' => $status,
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => 15000,
            'no_pembayaran' => 'PAY-' . $orderId,
            'no_pembeli' => '08123456789',
            'status' => $paymentStatus,
            'metode' => 'QRIS',
            'reference' => 'REF-' . $orderId,
            'expired_at' => $paymentStatus === 'Expired' ? now()->subHour() : now()->addHour(),
        ]);

        return $pembelian->fresh(['pembayaran']);
    }
}

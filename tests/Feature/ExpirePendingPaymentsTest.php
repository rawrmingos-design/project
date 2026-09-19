<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Voucher;
use App\Services\Payments\ExpirePendingPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpirePendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_expires_unpaid_payment_and_related_order(): void
    {
        $pembelian = $this->createPembelian('INV-EXPIRE-001', 'Pending');
        $payment = $this->createPayment($pembelian->order_id, 'Belum Lunas', now()->subMinute());

        $stats = app(ExpirePendingPayments::class)->expire();

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(1, $stats['expired_payments']);
        $this->assertSame(1, $stats['expired_pembelians']);
        $this->assertSame('Expired', $payment->fresh()->status);
        $this->assertSame('Expired', $pembelian->fresh()->status);
        $this->assertStringContainsString('Payment expired at', (string) $pembelian->fresh()->log);
    }

    public function test_it_skips_future_expiry_and_missing_expiry(): void
    {
        $futureOrder = $this->createPembelian('INV-EXPIRE-FUTURE', 'Pending');
        $missingOrder = $this->createPembelian('INV-EXPIRE-MISSING', 'Pending');
        $futurePayment = $this->createPayment($futureOrder->order_id, 'Belum Lunas', now()->addMinute());
        $missingPayment = $this->createPayment($missingOrder->order_id, 'Belum Lunas', null);

        $stats = app(ExpirePendingPayments::class)->expire();

        $this->assertSame(0, $stats['scanned']);
        $this->assertSame('Belum Lunas', $futurePayment->fresh()->status);
        $this->assertSame('Belum Lunas', $missingPayment->fresh()->status);
        $this->assertSame('Pending', $futureOrder->fresh()->status);
        $this->assertSame('Pending', $missingOrder->fresh()->status);
    }

    public function test_it_does_not_expire_paid_payment_or_successful_order(): void
    {
        $paidOrder = $this->createPembelian('INV-EXPIRE-PAID', 'Pending');
        $successOrder = $this->createPembelian('INV-EXPIRE-SUCCESS', 'Sukses');
        $paidPayment = $this->createPayment($paidOrder->order_id, 'Lunas', now()->subMinute());
        $successPayment = $this->createPayment($successOrder->order_id, 'Belum Lunas', now()->subMinute());

        $stats = app(ExpirePendingPayments::class)->expire();

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(1, $stats['expired_payments']);
        $this->assertSame(0, $stats['expired_pembelians']);
        $this->assertSame('Lunas', $paidPayment->fresh()->status);
        $this->assertSame('Pending', $paidOrder->fresh()->status);
        $this->assertSame('Expired', $successPayment->fresh()->status);
        $this->assertSame('Sukses', $successOrder->fresh()->status);
    }

    public function test_dry_run_reports_candidates_without_updating(): void
    {
        $pembelian = $this->createPembelian('INV-EXPIRE-DRY-RUN', 'Pending');
        $payment = $this->createPayment($pembelian->order_id, 'Belum Lunas', now()->subMinute());

        $this->artisan('payments:expire-pending', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame('Belum Lunas', $payment->fresh()->status);
        $this->assertSame('Pending', $pembelian->fresh()->status);
    }

    public function test_command_respects_limit(): void
    {
        $firstOrder = $this->createPembelian('INV-EXPIRE-LIMIT-1', 'Pending');
        $secondOrder = $this->createPembelian('INV-EXPIRE-LIMIT-2', 'Pending');
        $firstPayment = $this->createPayment($firstOrder->order_id, 'Belum Lunas', now()->subMinute());
        $secondPayment = $this->createPayment($secondOrder->order_id, 'Belum Lunas', now()->subMinute());

        $this->artisan('payments:expire-pending', ['--limit' => 1])
            ->assertExitCode(0);

        $this->assertSame('Expired', $firstPayment->fresh()->status);
        $this->assertSame('Belum Lunas', $secondPayment->fresh()->status);
        $this->assertSame('Expired', $firstOrder->fresh()->status);
        $this->assertSame('Pending', $secondOrder->fresh()->status);
    }

    public function test_it_restores_voucher_stock_when_pending_order_expires(): void
    {
        $voucher = Voucher::query()->create([
            'kode' => 'EXPIRE10',
            'promo' => 10,
            'stock' => 7,
            'mintrx' => 0,
            'max_potongan' => 1000,
        ]);

        $pembelian = $this->createPembelian('INV-EXPIRE-VOUCHER', 'Pending', [
            'voucher' => 'EXPIRE10',
            'tipe_transaksi' => 'game',
        ]);
        $this->createPayment($pembelian->order_id, 'Belum Lunas', now()->subMinute());

        $stats = app(ExpirePendingPayments::class)->expire();

        $this->assertSame(1, $stats['expired_pembelians']);
        $this->assertSame('Expired', $pembelian->fresh()->status);
        $this->assertSame(8, (int) $voucher->fresh()->stock);
    }

    public function test_it_restores_voucher_stock_only_once_and_never_for_voucher_type_orders(): void
    {
        $voucher = Voucher::query()->create([
            'kode' => 'EXPIRE20',
            'promo' => 20,
            'stock' => 5,
            'mintrx' => 0,
            'max_potongan' => 1000,
        ]);

        $promoOrder = $this->createPembelian('INV-EXPIRE-ONCE', 'Pending', [
            'voucher' => 'EXPIRE20',
            'tipe_transaksi' => 'game',
        ]);
        $this->createPayment($promoOrder->order_id, 'Belum Lunas', now()->subMinute());

        // Kolom voucher pada order bertipe 'voucher' berisi SN, bukan kode promo.
        $snOrder = $this->createPembelian('INV-EXPIRE-SN', 'Pending', [
            'voucher' => 'EXPIRE20',
            'tipe_transaksi' => 'voucher',
        ]);
        $this->createPayment($snOrder->order_id, 'Belum Lunas', now()->subMinute());

        app(ExpirePendingPayments::class)->expire();
        app(ExpirePendingPayments::class)->expire(); // Run kedua tidak menambah stok lagi.

        $this->assertSame(6, (int) $voucher->fresh()->stock); // 5 + 1, sekali saja.
        $this->assertSame('Expired', $promoOrder->fresh()->status);
        $this->assertSame('Expired', $snOrder->fresh()->status);
    }

    private function createPembelian(string $orderId, string $status, array $overrides = []): Pembelian
    {
        return Pembelian::query()->create(array_merge([
            'order_id' => $orderId,
            'username' => 'member-test',
            'layanan' => 'Test Service',
            'harga' => 12000,
            'profit' => 1000,
            'user_id' => '12345',
            'zone' => '2001',
            'status' => $status,
            'tipe_transaksi' => 'game',
        ], $overrides));
    }

    private function createPayment(string $orderId, string $status, $expiredAt): Pembayaran
    {
        return Pembayaran::query()->create([
            'order_id' => $orderId,
            'harga' => '12000',
            'no_pembayaran' => 'PAY-' . $orderId,
            'no_pembeli' => '081234567890',
            'status' => $status,
            'metode' => 'QRIS',
            'expired_at' => $expiredAt,
        ]);
    }
}

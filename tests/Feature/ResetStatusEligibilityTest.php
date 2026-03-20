<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetStatusEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_failed_and_cancelled_transactions_are_reset_eligible(): void
    {
        foreach (['Failed', 'Gagal', 'Batal'] as $status) {
            $pembelian = $this->createPembelian($status);

            $this->assertTrue($pembelian->isResetEligible(), "Expected [{$status}] to be reset eligible.");
        }
    }

    public function test_success_pending_processing_and_refunded_transactions_are_not_reset_eligible(): void
    {
        foreach (['Success', 'Sukses', 'Pending', 'Processing', 'Proses', 'Refunded'] as $status) {
            $pembelian = $this->createPembelian($status);

            $this->assertFalse($pembelian->isResetEligible(), "Expected [{$status}] to be reset ineligible.");
        }
    }

    public function test_unpaid_finalized_payment_and_in_flight_attempts_are_not_reset_eligible(): void
    {
        $unpaid = $this->createPembelian('Failed', paymentStatus: 'Belum Lunas');
        $refunded = $this->createPembelian('Failed', paymentStatus: 'Refunded');
        $inFlightByResetStatus = $this->createPembelian('Failed', overrides: ['reset_status' => 'processing']);
        $inFlightByAttemptReference = $this->createPembelian('Failed', overrides: ['active_attempt_reference' => 'INV-FAILED-999_002']);

        $this->assertFalse($unpaid->isResetEligible());
        $this->assertFalse($refunded->isResetEligible());
        $this->assertFalse($inFlightByResetStatus->isResetEligible());
        $this->assertFalse($inFlightByAttemptReference->isResetEligible());
    }

    public function test_refunded_or_cancelled_admin_finalization_updates_payment_status_and_blocks_reset(): void
    {
        $manuallyCancelled = $this->createPembelian('Success');
        $manuallyCancelled->update(['status' => 'Batal']);
        $manuallyCancelled->syncPaymentStatusForResetEligibility();

        $refunded = $this->createPembelian('Success', overrides: ['order_id' => 'INV-REFUND-ELIGIBLE-001']);
        $refunded->update(['status' => 'Refunded']);
        $refunded->syncPaymentStatusForResetEligibility();

        $this->assertSame('Refunded', $manuallyCancelled->fresh('pembayaran')->pembayaran->status);
        $this->assertFalse($manuallyCancelled->fresh('pembayaran')->isResetEligible());

        $this->assertSame('Refunded', $refunded->fresh('pembayaran')->pembayaran->status);
        $this->assertFalse($refunded->fresh('pembayaran')->isResetEligible());
    }

    public function test_failed_reset_attempt_becomes_reset_eligible_again_after_status_falls_back_to_failed(): void
    {
        $pembelian = $this->createPembelian('Processing', overrides: [
            'order_id' => 'INV-RESET-RETRY-001',
            'invoice_version' => 1,
            'display_order_id' => 'INV-RESET-RETRY-001_001',
            'active_attempt_reference' => 'INV-RESET-RETRY-001_001',
            'reset_status' => 'processing',
        ]);

        $this->assertFalse($pembelian->isResetEligible());

        $pembelian->update(['status' => 'Gagal']);
        $pembelian->refresh();

        $this->assertSame('failed', $pembelian->normalizedResetStatus());
        $this->assertTrue($pembelian->isResetEligible());
    }

    private function createPembelian(string $status, string $paymentStatus = 'Lunas', array $overrides = []): Pembelian
    {
        $orderId = $overrides['order_id'] ?? 'INV-' . strtoupper(str_replace(' ', '-', $status)) . '-' . substr((string) microtime(true), -6);

        $pembelian = Pembelian::create(array_merge([
            'order_id' => $orderId,
            'username' => 'reset-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Reset User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => $status,
            'tipe_transaksi' => 'game',
        ], $overrides));

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => $paymentStatus,
            'metode' => 'QRIS',
            'reference' => 'REF-' . $orderId,
        ]);

        return $pembelian->fresh(['pembayaran']);
    }
}

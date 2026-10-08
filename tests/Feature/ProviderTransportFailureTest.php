<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\ProviderStatusUpdateService;
use App\Support\ProviderTransportError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kegagalan TRANSPORT (timeout/koneksi) bukan vonis provider atas order.
 *
 * Bug nyata yang dijaga di sini: `cURL error 28` dari Digiflazz diperlakukan sebagai
 * status `Gagal` sehingga order yang sudah dibayar bisa di-Gagal-kan (lalu di-refund)
 * hanya karena provider sedang tidak bisa dihubungi. Selain itu pesan error-nya masih
 * menempel di `keterangan_sn` saat status naik kembali ke `Processing`.
 *
 * Aturan yang diuji: 1..(N-1) kegagalan beruntun TIDAK mengubah status; kegagalan
 * ke-N memutus Gagal; jawaban definitif dari provider mereset hitungan.
 */
class ProviderTransportFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('providers.digiflazz.max_consecutive_transport_failures', 3);
    }

    public function test_helper_classifies_transport_messages_only(): void
    {
        $this->assertTrue(ProviderTransportError::isTransportFailure(
            'Connection Error: cURL error 28: Connection timed out after 10001 milliseconds',
        ));
        $this->assertTrue(ProviderTransportError::isTransportFailure('Connection Error: cURL error 7: Failed to connect'));
        $this->assertTrue(ProviderTransportError::isTransportFailure('cURL error 28: Operation timed out'));

        // Pesan bisnis biasa BUKAN kegagalan transport.
        $this->assertFalse(ProviderTransportError::isTransportFailure('Saldo Anda tidak cukup'));
        $this->assertFalse(ProviderTransportError::isTransportFailure('Tujuan salah / tidak ditemukan'));
        $this->assertFalse(ProviderTransportError::isTransportFailure(''));
        $this->assertFalse(ProviderTransportError::isTransportFailure(null));
    }

    public function test_transport_failures_below_threshold_leave_status_untouched(): void
    {
        $service = app(ProviderStatusUpdateService::class);
        $pembelian = $this->createPaidPendingOrder('INV-TRANSPORT-UNDER-1', 'Processing');

        // Percobaan 1 dan 2: status TIDAK boleh berubah.
        $this->assertFalse($service->recordTransportFailure($pembelian, 'Connection Error: cURL error 28', 'provider_status_polling'));
        $this->assertSame('Processing', $pembelian->fresh()->status);
        $this->assertSame(1, (int) $pembelian->fresh()->transport_failure_count);

        $this->assertFalse($service->recordTransportFailure($pembelian->fresh(), 'Connection Error: cURL error 28', 'provider_status_polling'));
        $this->assertSame('Processing', $pembelian->fresh()->status);
        $this->assertSame(2, (int) $pembelian->fresh()->transport_failure_count);

        // Tidak boleh ada refund selama belum diputus Gagal.
        $this->assertNull($pembelian->fresh()->refunded_at);
    }

    public function test_third_consecutive_transport_failure_marks_order_failed(): void
    {
        $service = app(ProviderStatusUpdateService::class);
        $pembelian = $this->createPaidPendingOrder('INV-TRANSPORT-THREE', 'Processing');

        $service->recordTransportFailure($pembelian, 'Connection Error: cURL error 28', 'provider_status_polling');
        $service->recordTransportFailure($pembelian->fresh(), 'Connection Error: cURL error 28', 'provider_status_polling');

        $failedNow = $service->recordTransportFailure($pembelian->fresh(), 'Connection Error: cURL error 28', 'provider_status_polling');

        $this->assertTrue($failedNow);
        $this->assertSame('Gagal', $pembelian->fresh()->status);
        $this->assertStringContainsString('setelah 3 percobaan beruntun', (string) $pembelian->fresh()->keterangan_sn);
    }

    public function test_definitive_provider_answer_resets_consecutive_counter(): void
    {
        $service = app(ProviderStatusUpdateService::class);
        $pembelian = $this->createPaidPendingOrder('INV-TRANSPORT-RESET', 'Processing');

        // Dua kegagalan transport beruntun.
        $service->recordTransportFailure($pembelian, 'Connection Error: cURL error 28', 'provider_status_polling');
        $service->recordTransportFailure($pembelian->fresh(), 'Connection Error: cURL error 28', 'provider_status_polling');
        $this->assertSame(2, (int) $pembelian->fresh()->transport_failure_count);

        // Provider akhirnya menjawab: masih Processing. Hitungan harus direset ke 0
        // sehingga order mendapat jatah 3 percobaan penuh lagi.
        $service->apply($pembelian->fresh(), [
            'success' => true,
            'order_status' => 'Processing',
            'transaction_id' => 'INV-TRANSPORT-RESET',
            'message' => 'Order accepted by Digiflazz status: Processing',
            'sn' => '',
            'transport_failure_reset' => true,
        ], 'provider_status_polling');

        $this->assertSame(0, (int) $pembelian->fresh()->transport_failure_count);
        $this->assertSame('Processing', $pembelian->fresh()->status);
    }

    public function test_stale_transport_error_note_is_cleared_when_status_becomes_non_final(): void
    {
        $service = app(ProviderStatusUpdateService::class);

        // Kondisi bug: order Processing tapi keterangan_sn masih pesan timeout.
        $pembelian = $this->createPaidPendingOrder('INV-TRANSPORT-STALE', 'Processing');
        $pembelian->forceFill([
            'keterangan_sn' => 'Connection Error: cURL error 28: Connection timed out after 10001 milliseconds',
        ])->saveQuietly();

        $service->apply($pembelian->fresh(), [
            'success' => true,
            'order_status' => 'Processing',
            'transaction_id' => 'INV-TRANSPORT-STALE',
            'message' => 'Order accepted by Digiflazz status: Processing',
            'sn' => '',
        ], 'provider_status_polling');

        $note = (string) $pembelian->fresh()->keterangan_sn;

        $this->assertSame('Sedang Diproses', $note);
        $this->assertStringNotContainsString('Connection Error', $note);
        $this->assertSame('Processing', $pembelian->fresh()->status);
    }

    public function test_real_provider_sn_is_not_overwritten_by_cleanup(): void
    {
        $service = app(ProviderStatusUpdateService::class);
        $pembelian = $this->createPaidPendingOrder('INV-TRANSPORT-SN', 'Processing');

        $service->apply($pembelian->fresh(), [
            'success' => true,
            'order_status' => 'Sukses',
            'transaction_id' => 'INV-TRANSPORT-SN',
            'message' => 'Sukses',
            'sn' => 'SN-REAL-12345',
        ], 'provider_status_polling');

        $this->assertSame('SN-REAL-12345', $pembelian->fresh()->keterangan_sn);
    }

    public function test_final_order_is_never_touched_by_transport_failures(): void
    {
        $service = app(ProviderStatusUpdateService::class);
        $pembelian = $this->createPaidPendingOrder('INV-TRANSPORT-FINAL', 'Sukses');

        $failedNow = $service->recordTransportFailure($pembelian, 'Connection Error: cURL error 28', 'status_check');

        $this->assertFalse($failedNow);
        $this->assertSame('Sukses', $pembelian->fresh()->status);
        $this->assertSame(0, (int) $pembelian->fresh()->transport_failure_count);
    }

    public function test_threshold_is_configurable(): void
    {
        config()->set('providers.digiflazz.max_consecutive_transport_failures', 2);

        $service = app(ProviderStatusUpdateService::class);
        $pembelian = $this->createPaidPendingOrder('INV-TRANSPORT-CONFIG', 'Processing');

        $this->assertFalse($service->recordTransportFailure($pembelian, 'cURL error 28', 'provider_status_polling'));
        $this->assertTrue($service->recordTransportFailure($pembelian->fresh(), 'cURL error 28', 'provider_status_polling'));
        $this->assertSame('Gagal', $pembelian->fresh()->status);
    }

    private function createPaidPendingOrder(string $orderId, string $status): Pembelian
    {
        $pembelian = Pembelian::create([
            'order_id' => $orderId,
            'username' => 'transport-failure-user',
            'user_id' => '900001',
            'zone' => '9001',
            'nickname' => 'Transport Failure User',
            'layanan' => 'Weekly Pass',
            'provider_order_id' => $orderId,
            'active_provider_code' => 'digiflazz',
            'active_provider_sku' => 'FF12',
            'active_attempt_token' => $orderId,
            'active_attempt_reference' => $orderId,
            'status' => $status,
            'keterangan_sn' => 'Sedang Diproses',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => 15000,
            'no_pembayaran' => 'PAY-' . $orderId,
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-' . $orderId,
            'expired_at' => now()->addHour(),
        ]);

        return $pembelian->fresh(['pembayaran']);
    }
}

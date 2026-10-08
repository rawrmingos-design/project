<?php

namespace Tests\Feature;

use App\Jobs\SendPembelianToProviderJob;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\OrderProcessingService;
use App\Services\ProviderStatusUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Jalur tombol "Cek Status" di admin (dispatch mode `retry_status`).
 *
 * Job ini memakai jalur yang SAMA dengan polling otomatis, jadi kegagalan transport
 * harus diperlakukan sama: diklasifikasi dari PESANNYA, bukan dari field `status`
 * (yang bernilai `Gagal` bahkan untuk timeout), dihitung beruntun, dan baru diputus
 * Gagal pada percobaan ke-N.
 *
 * Kenapa test ini ada terpisah: ProviderTransportFailureTest menguji service-nya,
 * tetapi BUG ASLINYA terjadi lewat jalur ini -- admin menekan "Cek Status", order
 * masuk antrean, provider timeout, dan order yang sudah dibayar berakhir "Gagal".
 * Menguji service saja tidak membuktikan caller-nya memanggilnya.
 */
class AdminStatusCheckTransportFailureTest extends TestCase
{
    use RefreshDatabase;

    /** Pesan yang benar-benar dikembalikan DigiFlazz saat saldo kosong / timeout. */
    private const TRANSPORT_MESSAGE = 'Connection Error: cURL error 28: Connection timed out after 10001 milliseconds';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('providers.digiflazz.max_consecutive_transport_failures', 3);
    }

    /**
     * Inti bug: provider membalas `status = Gagal` untuk sebuah timeout, tetapi
     * timeout TIDAK membawa informasi apa pun tentang order. Dua percobaan pertama
     * tidak boleh mengubah status.
     */
    public function test_admin_status_check_ignores_gagal_status_field_for_transport_errors(): void
    {
        $pembelian = $this->createProcessingOrder('INV-ADMIN-CHECK-STRIKE-12');

        $service = $this->mockOrderProcessingServiceReturningTransportFailure();

        // Percobaan 1
        $this->runStatusCheck($pembelian, $service);
        $pembelian->refresh();
        $this->assertSame('Processing', $pembelian->status, 'Percobaan 1 tidak boleh memvonis order gagal.');
        $this->assertSame(1, (int) $pembelian->transport_failure_count);
        $this->assertStringContainsString('transport failure 1/3', (string) $pembelian->log);

        // Percobaan 2
        $this->runStatusCheck($pembelian->fresh(), $service);
        $pembelian->refresh();
        $this->assertSame('Processing', $pembelian->status, 'Percobaan 2 tidak boleh memvonis order gagal.');
        $this->assertSame(2, (int) $pembelian->transport_failure_count);
        $this->assertStringContainsString('transport failure 2/3', (string) $pembelian->log);

        // Belum boleh ada refund selama belum diputus Gagal.
        $this->assertNull($pembelian->fresh()->refunded_at);
    }

    public function test_admin_status_check_fails_order_only_on_third_strike(): void
    {
        $pembelian = $this->createProcessingOrder('INV-ADMIN-CHECK-STRIKE-3');
        $service = $this->mockOrderProcessingServiceReturningTransportFailure();

        $this->runStatusCheck($pembelian, $service);
        $this->runStatusCheck($pembelian->fresh(), $service);
        $this->assertSame('Processing', $pembelian->fresh()->status);

        // Percobaan ke-3 baru memutus Gagal.
        $this->runStatusCheck($pembelian->fresh(), $service);

        $pembelian->refresh();
        $this->assertSame('Gagal', $pembelian->status);
        $this->assertStringContainsString('setelah 3 percobaan beruntun', (string) $pembelian->keterangan_sn);
        $this->assertSame(0, (int) $pembelian->transport_failure_count, 'Hitungan harus direset setelah status final.');
    }

    /**
     * Kalau provider akhirnya menjawab (walau baru bilang "masih Processing"),
     * hitungan kegagalan beruntun harus kembali 0 supaya order mendapat jatah
     * 3 percobaan penuh lagi -- bukan mengakumulasi kegagalan seumur hidup order.
     */
    public function test_definitive_answer_after_strikes_resets_counter_and_clears_stale_note(): void
    {
        $pembelian = $this->createProcessingOrder('INV-ADMIN-CHECK-RECOVER');
        $service = $this->mockOrderProcessingServiceReturningTransportFailure();

        $this->runStatusCheck($pembelian, $service);
        $this->runStatusCheck($pembelian->fresh(), $service);
        $this->assertSame(2, (int) $pembelian->fresh()->transport_failure_count);

        // Provider bisa dihubungi lagi dan menjawab: masih diproses.
        app(ProviderStatusUpdateService::class)->apply($pembelian->fresh(), [
            'success' => true,
            'order_status' => 'Processing',
            'transaction_id' => $pembelian->provider_order_id,
            'message' => 'Order accepted by Digiflazz status: Processing',
            'sn' => '',
            'transport_failure_reset' => true,
        ], 'provider_status_polling');

        $pembelian->refresh();
        $this->assertSame(0, (int) $pembelian->transport_failure_count);
        $this->assertSame('Processing', $pembelian->status);

        // Keterangan tidak boleh lagi menyimpan pesan timeout yang sudah basi,
        // dan tidak boleh mengandung kata "Connection Error" pada status non-final.
        $this->assertStringNotContainsString('Connection Error', (string) $pembelian->keterangan_sn);
    }

    public function test_admin_status_check_success_path_is_unaffected(): void
    {
        $pembelian = $this->createProcessingOrder('INV-ADMIN-CHECK-SUCCESS');

        $service = Mockery::mock(OrderProcessingService::class);
        $service->shouldReceive('process')
            ->once()
            ->andReturn([
                'success' => true,
                'order_status' => 'Sukses',
                'transaction_id' => $pembelian->provider_order_id,
                'sn' => 'SN-ADMIN-OK-001',
                'message' => 'Sukses',
            ]);

        $this->runStatusCheck($pembelian, $service);

        $pembelian->refresh();
        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('SN-ADMIN-OK-001', $pembelian->keterangan_sn);
    }

    private function runStatusCheck(Pembelian $pembelian, OrderProcessingService $service): void
    {
        (new SendPembelianToProviderJob($pembelian->id, 1, 'retry_status'))->handle(
            $service,
            app(ProviderStatusUpdateService::class),
        );
    }

    /**
     * Meniru balasan DigiFlazz saat timeout: `success = false` + `status = Gagal`
     * (persis yang membuat bug ini muncul di produksi).
     */
    private function mockOrderProcessingServiceReturningTransportFailure(): OrderProcessingService
    {
        $service = Mockery::mock(OrderProcessingService::class);
        $service->shouldReceive('process')
            ->andReturn([
                'success' => false,
                'order_status' => 'Gagal',
                'transaction_id' => null,
                'sn' => null,
                'message' => self::TRANSPORT_MESSAGE,
                'transport_error' => true,
            ]);

        return $service;
    }

    private function createProcessingOrder(string $orderId): Pembelian
    {
        $pembelian = Pembelian::create([
            'order_id' => $orderId,
            'username' => 'admin-check-user',
            'user_id' => '777001',
            'zone' => '7001',
            'nickname' => 'Admin Check User',
            'layanan' => 'Weekly Pass',
            'provider_order_id' => $orderId . '_001',
            'display_order_id' => $orderId . '_001',
            'active_attempt_reference' => $orderId . '_001',
            'active_provider_code' => 'digiflazz',
            'active_provider_sku' => 'FF12',
            'status' => 'Processing',
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

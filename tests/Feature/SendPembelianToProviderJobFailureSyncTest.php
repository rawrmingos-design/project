<?php

namespace Tests\Feature;

use App\Jobs\SendPembelianToProviderJob;
use App\Models\Pembelian;
use App\Services\OrderProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendPembelianToProviderJobFailureSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_order_failed_when_provider_returns_definitive_failed_status(): void
    {
        $pembelian = Pembelian::create([
            'order_id' => 'INV-DIGI-FAILED-SYNC-001',
            'username' => 'member-test',
            'layanan' => 'Test Service',
            'harga' => 12000,
            'profit' => 1000,
            'user_id' => '12345',
            'zone' => '2001',
            'status' => 'Processing',
            'provider_order_id' => 'INV-DIGI-FAILED-SYNC-001_001',
            'invoice_version' => 1,
            'display_order_id' => 'INV-DIGI-FAILED-SYNC-001_001',
            'active_attempt_reference' => 'INV-DIGI-FAILED-SYNC-001_001',
            'reset_status' => 'processing',
            'tipe_transaksi' => 'game',
        ]);

        $service = Mockery::mock(OrderProcessingService::class);
        $service->shouldReceive('process')
            ->once()
            ->withArgs(function (Pembelian $record) use ($pembelian): bool {
                return $record->id === $pembelian->id;
            })
            ->andReturn([
                'success' => false,
                'order_status' => 'Gagal',
                'transaction_id' => 'INV-DIGI-FAILED-SYNC-001_001',
                'sn' => null,
                'message' => 'Provider rejected request',
            ]);

        $job = new SendPembelianToProviderJob($pembelian->id, 1);
        $job->handle($service);

        $pembelian->refresh();

        $this->assertSame('Gagal', $pembelian->status);
        $this->assertSame('failed', $pembelian->reset_status);
        $this->assertStringContainsString('final failure', (string) $pembelian->log);
    }
}


<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FailedTransactionResetVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_invoice_suffixes_follow_reset_versions_without_mutating_canonical_order_id(): void
    {
        $pembelian = Pembelian::create([
            'order_id' => 'INV-RESET-001',
            'username' => 'reset-user',
            'user_id' => '20002',
            'zone' => '3002',
            'nickname' => 'Reset User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Failed',
            'tipe_transaksi' => 'game',
        ]);

        $this->assertSame('INV-RESET-001', $pembelian->order_id);
        $this->assertSame('INV-RESET-001', $pembelian->base_order_id);
        $this->assertSame('INV-RESET-001', $pembelian->display_invoice_id);
        $this->assertSame('INV-RESET-001_001', $pembelian->nextDisplayInvoiceId());

        $expectedDisplays = [
            1 => 'INV-RESET-001_001',
            2 => 'INV-RESET-001_002',
            3 => 'INV-RESET-001_003',
        ];

        foreach ($expectedDisplays as $version => $displayInvoiceId) {
            $pembelian->forceFill([
                'invoice_version' => $version,
                'reset_count' => $version,
                'display_order_id' => null,
                'active_attempt_reference' => null,
            ])->save();

            $pembelian->refresh();

            $this->assertSame('INV-RESET-001', $pembelian->order_id);
            $this->assertSame('INV-RESET-001', $pembelian->base_order_id);
            $this->assertSame($version, $pembelian->invoice_version);
            $this->assertSame($displayInvoiceId, $pembelian->display_invoice_id);
            $this->assertSame($displayInvoiceId, $pembelian->display_order_id);
            $this->assertSame($displayInvoiceId, $pembelian->active_attempt_reference);
            $this->assertSame($version > 0, $pembelian->is_reset_attempt);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembelianRetryEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_vip_retry_status_check_requires_provider_reference(): void
    {
        $pembelian = $this->createPembelian([
            'order_id' => 'INV-VIP-RETRY-ELIGIBLE-001',
            'active_provider_code' => 'vip',
            'active_provider_sku' => 'VIP-WP',
            'provider_order_id' => null,
            'active_attempt_token' => null,
            'status' => 'Failed',
        ]);

        $this->assertTrue($pembelian->canBeRetried());
        $this->assertFalse($pembelian->hasRetryStatusReference());
        $this->assertFalse($pembelian->canRunRetryStatusCheck());
        $this->assertSame(
            'Retry status check untuk VIP butuh trxid/provider_order_id. Gunakan Reset Invoice setelah saldo/provider sudah siap.',
            $pembelian->retryUnavailableReason(),
        );
    }

    public function test_non_vip_retry_status_check_remains_available_without_provider_reference(): void
    {
        $pembelian = $this->createPembelian([
            'order_id' => 'INV-DIGI-RETRY-ELIGIBLE-001',
            'active_provider_code' => 'digiflazz',
            'active_provider_sku' => 'DGI-WP',
            'provider_order_id' => null,
            'active_attempt_token' => null,
            'status' => 'Pending',
        ]);

        $this->assertTrue($pembelian->canBeRetried());
        $this->assertTrue($pembelian->canRunRetryStatusCheck());
        $this->assertNull($pembelian->retryUnavailableReason());
    }

    private function createPembelian(array $overrides = [], string $paymentStatus = 'Lunas'): Pembelian
    {
        $pembelian = Pembelian::create(array_merge([
            'order_id' => 'INV-RETRY-ELIGIBILITY-001',
            'username' => 'retry-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Retry User',
            'layanan' => 'Weekly Pass',
            'active_layanan_id' => null,
            'active_provider_code' => 'manual',
            'active_provider_sku' => 'MANUAL-WP',
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

        return $pembelian->fresh('pembayaran');
    }
}

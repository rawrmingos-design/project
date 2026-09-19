<?php

namespace Tests\Feature;

use App\Models\Pembelian;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherStockRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_restores_stock_exactly_once_per_order(): void
    {
        $voucher = $this->createVoucher('RESTORE10', 5);
        $order = $this->createOrder(['voucher' => 'RESTORE10']);

        $service = app(VoucherService::class);

        $this->assertTrue($service->restoreStockForOrder($order));
        $this->assertSame(6, (int) $voucher->fresh()->stock);
        $this->assertNotNull($order->fresh()->voucher_stock_restored_at);

        // Callback terkirim ulang / command expiry jalan lagi: tidak menambah stok.
        $this->assertFalse($service->restoreStockForOrder($order->fresh()));
        $this->assertSame(6, (int) $voucher->fresh()->stock);
    }

    public function test_it_skips_orders_without_a_promo_code(): void
    {
        $order = $this->createOrder([]);

        $this->assertFalse(app(VoucherService::class)->restoreStockForOrder($order));
        $this->assertNull($order->fresh()->voucher_stock_restored_at);
    }

    public function test_it_never_treats_voucher_type_sn_rows_as_promo_codes(): void
    {
        $voucher = $this->createVoucher('SNCODE10', 3);
        $order = $this->createOrder([
            'voucher' => 'SNCODE10',
            'tipe_transaksi' => 'voucher',
        ]);

        $this->assertFalse(app(VoucherService::class)->restoreStockForOrder($order));
        $this->assertSame(3, (int) $voucher->fresh()->stock);
        $this->assertNull($order->fresh()->voucher_stock_restored_at);
    }

    public function test_it_finalizes_the_order_even_when_the_voucher_row_is_gone(): void
    {
        $order = $this->createOrder(['voucher' => 'SUDAH-DIHAPUS']);

        $this->assertFalse(app(VoucherService::class)->restoreStockForOrder($order));

        // Order tetap ditandai selesai supaya tidak diproses berulang-ulang.
        $this->assertNotNull($order->fresh()->voucher_stock_restored_at);
    }

    private function createVoucher(string $kode, int $stock): Voucher
    {
        return Voucher::query()->create([
            'kode' => $kode,
            'promo' => 10,
            'stock' => $stock,
            'mintrx' => 0,
            'max_potongan' => 1000,
        ]);
    }

    private function createOrder(array $overrides): Pembelian
    {
        return Pembelian::query()->create(array_merge([
            'order_id' => 'INV-VSR-' . uniqid(),
            'username' => 'member-test',
            'layanan' => 'Test Service',
            'harga' => 12000,
            'profit' => 1000,
            'user_id' => '12345',
            'zone' => '2001',
            'status' => 'Gagal',
            'tipe_transaksi' => 'game',
        ], $overrides));
    }
}

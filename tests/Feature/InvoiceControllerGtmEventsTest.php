<?php

namespace Tests\Feature;

use App\Http\Controllers\InvoiceController;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceControllerGtmEventsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function paid_payment_pushes_payment_success_without_purchase_when_order_is_not_success_yet(): void
    {
        $orderId = 'INV-GTM-PAID-PROCESSING';
        $this->createInvoiceData($orderId, paymentStatus: 'Lunas', orderStatus: 'Proses');

        $events = $this->invoiceEventsFor($orderId);

        $this->assertContains('invoice_viewed', $events->pluck('name')->all());
        $this->assertContains('payment_success', $events->pluck('name')->all());
        $this->assertContains('order_processing', $events->pluck('name')->all());
        $this->assertNotContains('purchase', $events->pluck('name')->all());

        $paymentSuccess = $events->firstWhere('name', 'payment_success');
        $this->assertSame('Lunas', $paymentSuccess['payload']['payment_status']);
        $this->assertSame('Proses', $paymentSuccess['payload']['order_status']);
        $this->assertSame("payment_success:{$orderId}", $paymentSuccess['dedupe_key']);
    }

    #[Test]
    public function paid_payment_pushes_purchase_only_when_order_is_success(): void
    {
        $orderId = 'INV-GTM-PAID-SUCCESS';
        $this->createInvoiceData($orderId, paymentStatus: 'Lunas', orderStatus: 'Sukses');

        $events = $this->invoiceEventsFor($orderId);

        $this->assertContains('invoice_viewed', $events->pluck('name')->all());
        $this->assertContains('payment_success', $events->pluck('name')->all());
        $this->assertContains('purchase', $events->pluck('name')->all());
        $this->assertNotContains('order_processing', $events->pluck('name')->all());

        $purchase = $events->firstWhere('name', 'purchase');
        $this->assertSame('Lunas', $purchase['payload']['payment_status']);
        $this->assertSame('Sukses', $purchase['payload']['order_status']);
        $this->assertSame('success', $purchase['payload']['transaction_status']);
        $this->assertSame("purchase:{$orderId}", $purchase['dedupe_key']);
        $this->assertSame('success', $purchase['payload']['ecommerce']['transaction_status']);
    }

    #[Test]
    public function unpaid_payment_keeps_add_payment_info_and_does_not_push_payment_success_or_purchase(): void
    {
        $orderId = 'INV-GTM-UNPAID';
        $this->createInvoiceData($orderId, paymentStatus: 'Belum Lunas', orderStatus: 'Pending');

        $events = $this->invoiceEventsFor($orderId);
        $eventNames = $events->pluck('name')->all();

        $this->assertContains('invoice_viewed', $eventNames);
        $this->assertContains('add_payment_info', $eventNames);
        $this->assertContains('payment_pending', $eventNames);
        $this->assertNotContains('payment_success', $eventNames);
        $this->assertNotContains('purchase', $eventNames);
    }

    private function invoiceEventsFor(string $orderId)
    {
        /** @var View $view */
        $view = app(InvoiceController::class)->create($orderId);

        $this->assertInstanceOf(View::class, $view);

        return collect($view->getData()['gtmInvoiceEvents'] ?? []);
    }

    private function createInvoiceData(string $orderId, string $paymentStatus, string $orderStatus): void
    {
        $category = Kategori::factory()->create([
            'nama' => 'Mobile Legends',
            'tipe' => 'game',
        ]);

        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Mobile Legends 86 Diamond',
            'harga' => 50000,
        ]);

        Method::create([
            'code' => 'QRIS',
            'name' => 'QRIS Test',
            'payment' => 'tripay',
            'keterangan' => 'QRIS test method',
            'tipe' => 'qris',
            'images' => 'qris.png',
            'statuspayment' => 1,
        ]);

        Pembelian::factory()->create([
            'order_id' => $orderId,
            'layanan' => 'Mobile Legends 86 Diamond',
            'harga' => 50000,
            'status' => $orderStatus,
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::create([
            'order_id' => $orderId,
            'harga' => '50000',
            'no_pembayaran' => 'QRIS-' . $orderId,
            'no_pembeli' => '08123456789',
            'status' => $paymentStatus,
            'metode' => 'QRIS',
            'reference' => 'REF-' . $orderId,
        ]);
    }
}

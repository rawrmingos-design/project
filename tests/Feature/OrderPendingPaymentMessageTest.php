<?php

namespace Tests\Feature;

use App\Http\Controllers\OrderController;
use Tests\TestCase;

class OrderPendingPaymentMessageTest extends TestCase
{
    public function test_pending_payment_message_displays_va_details(): void
    {
        config([
            'app.name' => 'Z-Vault Store',
            'app.url' => 'https://z-vault.test',
        ]);

        $message = app(OrderController::class)->buildPendingPaymentMessage(
            'INV-123',
            '1 Diamonds (Test Manual)',
            12500,
            'BCA Virtual Account',
            '1234567890',
            '30/07/2026 13:00',
        );

        $this->assertSame("⏳ *MENUNGGU PEMBAYARAN*\n\nTerima kasih telah berbelanja di Z-Vault Store.\n\n🧾 *RINCIAN TRANSAKSI*\n├ Nomor Invoice: *INV-123*\n├ Produk: *1 Diamonds (Test Manual)*\n├ Total Tagihan: *Rp 12.500*\n└ Metode: *BCA Virtual Account*\n\n💳 Kode Bayar / VA: *1234567890*\n⏰ Bayar sebelum: *30/07/2026 13:00*\n\n⚠️ Selesaikan pembayaran agar pesanan diproses otomatis.\n🔗 Invoice: https://z-vault.test/id/invoices/INV-123", $message);
    }

    public function test_pending_payment_message_hides_qris_payload_and_payment_link(): void
    {
        config(['app.url' => 'https://z-vault.test']);
        $qrisPayload = '00020101021226610014COM.GO-JEK.WWW01189360091430274901050210G1234567890303UMI51440014ID.CO.QRIS.WWW0215ID10200423000030303UMI5204541153033605802ID5910Test Store6007Jakarta6105123456304ABCD';
        $paymentLink = 'https://gateway.example/checkout/INV-123';

        foreach ([$qrisPayload, $paymentLink] as $paymentCode) {
            $message = app(OrderController::class)->buildPendingPaymentMessage(
                'INV-123',
                'Mobile Legends',
                10000,
                'QRIS',
                $paymentCode,
                '30/07/2026 13:00',
            );

            $this->assertStringContainsString('Buka invoice di bawah untuk scan QRIS', $message);
            $this->assertStringNotContainsString($paymentCode, $message);
            $this->assertStringContainsString('https://z-vault.test/id/invoices/INV-123', $message);
        }
    }
}

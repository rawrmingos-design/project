<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DuitkuRetryOrderCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use the seeder to populate setting_webs with all required fields
        $this->seed(\Database\Seeders\SettingWebsSeeder::class);

        // Override with test-specific Duitku config
        DB::table('setting_webs')->where('id', 1)->update([
            'duitku_merchant_key' => 'test-merchant-key',
            'duitku_merchant_code' => 'TEST123',
            'duitku_mode' => 'sandbox',
        ]);
    }

    public function test_callback_updates_correct_payment_when_order_has_multiple_retries(): void
    {
        // Setup: Create order with original payment (expired)
        $order = Pembelian::factory()->create([
            'order_id' => 'TEST-ORDER-001',
            'layanan' => 'Mobile Legends 100 Diamond',
            'harga' => 50000,
            'status' => 'Pending',
        ]);

        // First payment attempt (expired)
        $oldPayment = Pembayaran::create([
            'order_id' => 'TEST-ORDER-001',
            'harga' => 50000,
            'metode' => 'QRIS',
            'status' => 'Belum Lunas',
            'no_pembayaran' => 'QRIS-001',
            'no_pembeli' => '081234567890',
            'reference' => 'OLD-REF-001',
            'duitku_reference' => 'OLD-REF-001',
            'duitku_merchant_order_id' => 'DUITKU-TEST-ORDER-001',
            'expired_at' => now()->subHours(1),
        ]);

        // Second payment attempt (retry, still active)
        $newPayment = Pembayaran::create([
            'order_id' => 'TEST-ORDER-001',
            'harga' => 50000,
            'metode' => 'QRIS',
            'status' => 'Belum Lunas',
            'no_pembayaran' => 'QRIS-002',
            'no_pembeli' => '081234567890',
            'reference' => 'NEW-REF-002',
            'duitku_reference' => 'NEW-REF-002',
            'duitku_merchant_order_id' => 'DUITKU-TEST-ORDER-001', // SAME merchantOrderId!
            'expired_at' => now()->addHours(1),
        ]);

        // Simulate Duitku callback for the NEW payment (retry)
        $merchantCode = 'TEST123';
        $amount = '50000';
        $merchantOrderId = 'DUITKU-TEST-ORDER-001';
        $signature = md5($merchantCode . $amount . $merchantOrderId . 'test-merchant-key');

        $response = $this->post(route('duitku.callback'), [
            'merchantCode' => $merchantCode,
            'amount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            'reference' => 'NEW-REF-002', // Reference for the NEW payment
            'resultCode' => '00', // Success
            'signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertSee('SUCCESS');

        // Assert: NEW payment (retry) should be marked as paid
        $newPayment->refresh();
        $this->assertSame('Lunas', $newPayment->status);
        $this->assertNotNull($newPayment->paid_at);

        // Assert: OLD payment should remain unpaid
        $oldPayment->refresh();
        $this->assertSame('Belum Lunas', $oldPayment->status);
        $this->assertNull($oldPayment->paid_at);
    }

    public function test_callback_falls_back_to_merchant_order_id_when_reference_not_found(): void
    {
        // Setup: Create order with payment that only has merchantOrderId (no reference set)
        $order = Pembelian::factory()->create([
            'order_id' => 'TEST-ORDER-002',
            'layanan' => 'Mobile Legends 100 Diamond',
            'harga' => 50000,
            'status' => 'Pending',
        ]);

        $payment = Pembayaran::create([
            'order_id' => 'TEST-ORDER-002',
            'harga' => 50000,
            'metode' => 'QRIS',
            'status' => 'Belum Lunas',
            'no_pembayaran' => 'QRIS-003',
            'no_pembeli' => '081234567890',
            'duitku_merchant_order_id' => 'DUITKU-TEST-ORDER-002',
            'expired_at' => now()->addHours(1),
        ]);

        // Simulate Duitku callback with reference that doesn't match any payment record
        $merchantCode = 'TEST123';
        $amount = '50000';
        $merchantOrderId = 'DUITKU-TEST-ORDER-002';
        $signature = md5($merchantCode . $amount . $merchantOrderId . 'test-merchant-key');

        $response = $this->post(route('duitku.callback'), [
            'merchantCode' => $merchantCode,
            'amount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            'reference' => 'UNKNOWN-REF-999', // Reference not in database
            'resultCode' => '00',
            'signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertSee('SUCCESS');

        // Assert: Payment should still be marked as paid via merchantOrderId fallback
        $payment->refresh();
        $this->assertSame('Lunas', $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_callback_picks_latest_payment_when_multiple_retries_match_by_merchant_order_id(): void
    {
        // Setup: Create order with 3 payment attempts (all using same merchantOrderId)
        $order = Pembelian::factory()->create([
            'order_id' => 'TEST-ORDER-003',
            'layanan' => 'Mobile Legends 100 Diamond',
            'harga' => 50000,
            'status' => 'Pending',
        ]);

        // First payment (oldest)
        $payment1 = Pembayaran::create([
            'order_id' => 'TEST-ORDER-003',
            'harga' => 50000,
            'metode' => 'QRIS',
            'status' => 'Belum Lunas',
            'no_pembayaran' => 'QRIS-004',
            'no_pembeli' => '081234567890',
            'duitku_merchant_order_id' => 'DUITKU-TEST-ORDER-003',
            'expired_at' => now()->addHours(1),
            'created_at' => now()->subHours(3),
        ]);

        // Second payment
        $payment2 = Pembayaran::create([
            'order_id' => 'TEST-ORDER-003',
            'harga' => 50000,
            'metode' => 'QRIS',
            'status' => 'Belum Lunas',
            'no_pembayaran' => 'QRIS-005',
            'no_pembeli' => '081234567890',
            'duitku_merchant_order_id' => 'DUITKU-TEST-ORDER-003',
            'expired_at' => now()->addHours(1),
            'created_at' => now()->subHours(2),
        ]);

        // Third payment (latest)
        $payment3 = Pembayaran::create([
            'order_id' => 'TEST-ORDER-003',
            'harga' => 50000,
            'metode' => 'QRIS',
            'status' => 'Belum Lunas',
            'no_pembayaran' => 'QRIS-006',
            'no_pembeli' => '081234567890',
            'duitku_merchant_order_id' => 'DUITKU-TEST-ORDER-003',
            'expired_at' => now()->addHours(1),
            'created_at' => now()->subHours(1),
        ]);

        // Simulate callback with no reference (only merchantOrderId)
        $merchantCode = 'TEST123';
        $amount = '50000';
        $merchantOrderId = 'DUITKU-TEST-ORDER-003';
        $signature = md5($merchantCode . $amount . $merchantOrderId . 'test-merchant-key');

        $response = $this->post(route('duitku.callback'), [
            'merchantCode' => $merchantCode,
            'amount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            // No reference provided
            'resultCode' => '00',
            'signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertSee('SUCCESS');

        // Assert: Latest payment (payment3) should be marked as paid
        $payment3->refresh();
        $this->assertSame('Lunas', $payment3->status);
        $this->assertNotNull($payment3->paid_at);

        // Assert: Older payments should remain unpaid
        $payment1->refresh();
        $payment2->refresh();
        $this->assertSame('Belum Lunas', $payment1->status);
        $this->assertSame('Belum Lunas', $payment2->status);
        $this->assertNull($payment1->paid_at);
        $this->assertNull($payment2->paid_at);
    }

    public function test_callback_ignores_payment_that_is_already_paid(): void
    {
        // Setup: Create payment that is already paid
        $order = Pembelian::factory()->create([
            'order_id' => 'TEST-ORDER-004',
            'layanan' => 'Mobile Legends 100 Diamond',
            'harga' => 50000,
            'status' => 'Success',
        ]);

        $payment = Pembayaran::create([
            'order_id' => 'TEST-ORDER-004',
            'harga' => 50000,
            'metode' => 'QRIS',
            'status' => 'Lunas', // Already paid
            'no_pembayaran' => 'QRIS-007',
            'no_pembeli' => '081234567890',
            'reference' => 'PAID-REF-001',
            'duitku_reference' => 'PAID-REF-001',
            'duitku_merchant_order_id' => 'DUITKU-TEST-ORDER-004',
            'paid_at' => now()->subHours(1),
        ]);

        $merchantCode = 'TEST123';
        $amount = '50000';
        $merchantOrderId = 'DUITKU-TEST-ORDER-004';
        $signature = md5($merchantCode . $amount . $merchantOrderId . 'test-merchant-key');

        // Try to process duplicate callback
        $response = $this->post(route('duitku.callback'), [
            'merchantCode' => $merchantCode,
            'amount' => $amount,
            'merchantOrderId' => $merchantOrderId,
            'reference' => 'PAID-REF-001',
            'resultCode' => '00',
            'signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertSee('SUCCESS');

        // Payment status should remain unchanged
        $payment->refresh();
        $this->assertSame('Lunas', $payment->status);
    }
}

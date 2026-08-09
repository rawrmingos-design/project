<?php

namespace Tests\Feature;

use App\Jobs\SendPembelianToProviderJob;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RazerCallbackHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.razerpay.secret_key' => 'razer-test-secret']);
        Bus::fake();
    }

    public function test_invalid_signature_is_rejected_before_database_mutation(): void
    {
        [$payment] = $this->createPurchaseInvoice();
        $payload = $this->payload($payment->order_id, (int) $payment->harga);
        $payload['skey'] = str_repeat('0', 32);

        $this->postJson('/callback/razerpay', $payload)
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid signature');

        $this->assertSame('Belum Lunas', $payment->fresh()->status);
        Bus::assertNothingDispatched();
    }

    public function test_valid_callback_marks_invoice_paid_and_dispatches_one_fulfillment_job(): void
    {
        [$payment, $purchase] = $this->createPurchaseInvoice();

        $this->postJson('/callback/razerpay', $this->payload($payment->order_id, (int) $payment->harga))
            ->assertOk()
            ->assertJsonPath('message', 'Callback processed');

        $this->assertSame('Lunas', $payment->fresh()->status);
        Bus::assertDispatched(SendPembelianToProviderJob::class, fn ($job): bool => $job->pembelianId === $purchase->id);
    }

    public function test_callback_replay_is_idempotent(): void
    {
        [$payment] = $this->createPurchaseInvoice();
        $payload = $this->payload($payment->order_id, (int) $payment->harga);

        $this->postJson('/callback/razerpay', $payload)->assertOk();
        $this->postJson('/callback/razerpay', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Callback already processed');

        Bus::assertDispatchedTimes(SendPembelianToProviderJob::class, 1);
    }

    private function createPurchaseInvoice(): array
    {
        $purchase = Pembelian::factory()->create([
            'order_id' => 'RAZER-HARDENING-001',
            'status' => 'Pending',
        ]);
        $payment = Pembayaran::query()->create([
            'order_id' => $purchase->order_id,
            'harga' => 15000,
            'no_pembayaran' => 'RAZER-PAYMENT-001',
            'no_pembeli' => '081234567890',
            'status' => 'Belum Lunas',
            'metode' => 'RAZERPAY',
        ]);

        return [$payment, $purchase];
    }

    private function payload(string $orderId, int $amount): array
    {
        $payload = [
            'nbcb' => 1,
            'tranID' => 'RAZER-TRANSACTION-001',
            'orderid' => $orderId,
            'status' => '00',
            'domain' => 'example.test',
            'amount' => (string) $amount,
            'currency' => 'IDR',
            'appcode' => 'APP-001',
            'paydate' => '2026-08-08 12:00:00',
        ];
        $key0 = md5(
            $payload['tranID']
            . $payload['orderid']
            . $payload['status']
            . $payload['domain']
            . $payload['amount']
            . $payload['currency'],
        );
        $payload['skey'] = md5(
            $payload['paydate']
            . $payload['domain']
            . $key0
            . $payload['appcode']
            . 'razer-test-secret',
        );

        return $payload;
    }
}

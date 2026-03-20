<?php

namespace Tests\Feature;

use App\Http\Controllers\DigiflazzCallbackController;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResetCallbackStaleAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_aware_callbacks_ignore_base_and_stale_attempt_references_but_accept_the_active_attempt(): void
    {
        config(['providers.digiflazz.webhook_secret' => 'test-secret']);

        $user = User::factory()->create([
            'username' => 'reset-callback-user',
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-RESET-CALLBACK-001',
            'username' => $user->username,
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Reset Callback User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'provider_order_id' => 'INV-RESET-CALLBACK-001_001',
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
            'invoice_version' => 2,
            'display_order_id' => 'INV-RESET-CALLBACK-001_002',
            'active_attempt_reference' => 'INV-RESET-CALLBACK-001_002',
            'active_provider_code' => 'digiflazz',
            'active_provider_sku' => 'ML-WP',
            'reset_status' => 'processing',
            'reset_count' => 2,
        ]);

        Pembayaran::create([
            'order_id' => $pembelian->order_id,
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-' . $pembelian->order_id,
        ]);

        $this->dispatchCallback('INV-RESET-CALLBACK-001', 'Sukses', sn: 'BASE-SN');
        $this->dispatchCallback('INV-RESET-CALLBACK-001_001', 'Sukses', sn: 'STALE-SN');

        $pembelian->refresh();

        $this->assertSame('Pending', $pembelian->status);
        $this->assertNull($pembelian->keterangan_sn);
        $this->assertSame('INV-RESET-CALLBACK-001_001', $pembelian->provider_order_id);

        $response = $this->dispatchCallback('INV-RESET-CALLBACK-001_002', 'Sukses', sn: 'ACTIVE-SN');

        $this->assertSame(['success' => true], $response->getData(true));

        $pembelian->refresh();

        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('ACTIVE-SN', $pembelian->keterangan_sn);
        $this->assertSame('INV-RESET-CALLBACK-001_002', $pembelian->provider_order_id);
    }

    public function test_legacy_non_reset_rows_still_accept_base_order_id_callbacks(): void
    {
        config(['providers.digiflazz.webhook_secret' => 'test-secret']);

        $user = User::factory()->create([
            'username' => 'legacy-callback-user',
        ]);

        $pembelian = Pembelian::create([
            'order_id' => 'INV-LEGACY-CALLBACK-001',
            'username' => $user->username,
            'user_id' => '20002',
            'zone' => '2001',
            'nickname' => 'Legacy Callback User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
        ]);

        $response = $this->dispatchCallback('INV-LEGACY-CALLBACK-001', 'Sukses', sn: 'LEGACY-SN');

        $this->assertSame(['success' => true], $response->getData(true));

        $pembelian->refresh();

        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('LEGACY-SN', $pembelian->keterangan_sn);
        $this->assertSame('INV-LEGACY-CALLBACK-001', $pembelian->provider_order_id);
    }

    private function dispatchCallback(string $refId, string $status, string $sn = '', string $message = '')
    {
        $payload = [
            'data' => [
                'ref_id' => $refId,
                'status' => $status,
                'sn' => $sn,
                'message' => $message,
            ],
        ];

        $content = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha1=' . hash_hmac('sha1', $content, (string) config('providers.digiflazz.webhook_secret'));

        $request = Request::create(
            '/wejizy/digi/payload',
            'POST',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE' => $signature,
                'HTTP_X_DIGIFLAZZ_EVENT' => 'testing',
            ],
            content: $content,
        );

        return app(DigiflazzCallbackController::class)->handle($request);
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\OrderApiController;
use App\Models\Pembelian;
use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResetStatusCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('legacyStatusProvider')]
    public function test_legacy_status_labels_stay_compatible_for_badges_and_api_output(
        string $rawStatus,
        string $expectedNormalizedStatus,
        string $expectedLabel,
        string $expectedBadgeColor,
        string $expectedApiStatusCode
    ): void {
        $token = 'token-' . md5($rawStatus);
        
        $integration = ResellerIntegration::factory()->create([
            'api_key_hash' => hash('sha256', $token),
            'mode' => 'live',
            'is_active' => true,
        ]);
        $user = $integration->user;

        $pembelian = Pembelian::create([
            'order_id' => 'INV-' . strtoupper(md5($rawStatus)),
            'username' => $user->username,
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Compatibility User',
            'layanan' => 'Weekly Pass',
            'harga' => 15000,
            'profit' => 1000,
            'status' => $rawStatus,
            'tipe_transaksi' => 'game',
        ]);

        $this->assertSame($expectedNormalizedStatus, $pembelian->normalized_status);
        $this->assertSame($expectedLabel, $pembelian->status_display_label);
        $this->assertSame($expectedBadgeColor, $pembelian->status_badge_color);
        $this->assertNotEmpty($pembelian->status_icon);

        $request = Request::create('/api/status-order/' . $pembelian->order_id, 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        // Phase 3: resolveApiUser() reads from middleware-set request attribute.
        // Tests that call the controller directly must inject api_user themselves,
        // since middleware is not invoked in this code path.
        $request->attributes->set('api_user', $user);

        $response = app(OrderApiController::class)->statusOrder($request, $pembelian->order_id);
        $payload = $response->getData(true);

        $this->assertSame($expectedApiStatusCode, $payload['data']['statusCode']);
        $this->assertSame($pembelian->order_id, $payload['data']['invoiceNumber']);

    }

    public static function legacyStatusProvider(): array
    {
        return [
            'success english' => ['Success', 'success', 'Success', 'success', 'Success'],
            'success indonesian' => ['Sukses', 'success', 'Success', 'success', 'Success'],
            'pending' => ['Pending', 'pending', 'Pending', 'warning', 'Pending'],
            'processing english' => ['Processing', 'processing', 'Processing', 'info', 'Processing'],
            'processing indonesian' => ['Proses', 'processing', 'Processing', 'info', 'Processing'],
            'failed english' => ['Failed', 'failed', 'Failed', 'danger', 'Failed'],
            'failed indonesian' => ['Gagal', 'failed', 'Failed', 'danger', 'Failed'],
            'cancelled indonesian' => ['Batal', 'cancelled', 'Cancelled', 'danger', 'Canceled'],
        ];
    }
}

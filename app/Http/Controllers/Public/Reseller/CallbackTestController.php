<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerCallbackProfile;
use App\Services\ResellerCallbackDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Phase 5 — Task 5.3
 * Sandbox Webhook Tester: sends a synthetic test payload to the reseller's
 * sandbox callback profile without requiring a real order.
 *
 * Route: POST /id/reseller/callbacks/test
 * Throttle: 5 requests/minute per user (reseller-callback-test)
 */
class CallbackTestController extends Controller
{
    public function __construct(
        private readonly ResellerCallbackDeliveryService $deliveryService,
    ) {}

    public function fire(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Load sandbox integration with its callback profile
        $user->load('resellerIntegrations.callbackProfile');

        $sandboxIntegration = $user->resellerIntegrations
            ->where('mode', 'sandbox')
            ->first();

        if (! $sandboxIntegration) {
            return redirect()->back()->with('flash_error',
                'Sandbox integration tidak ditemukan. Hubungi admin untuk mengaktifkan integration Anda.'
            );
        }

        /** @var ResellerCallbackProfile|null $profile */
        $profile = $sandboxIntegration->callbackProfile;

        if (! $profile || ! $profile->is_enabled) {
            return redirect()->back()->with('flash_error',
                'Webhook profile sandbox belum diaktifkan. Setup URL dan secret di halaman Credentials terlebih dahulu.'
            );
        }

        if (blank($profile->callback_url ?? null)) {
            return redirect()->back()->with('flash_error',
                'Callback URL belum diisi di webhook profile Anda.'
            );
        }

        // Build synthetic test payload — clearly marked as a test
        $timestamp = now()->toIso8601String();
        $testInvoice = 'TEST-' . now()->format('YmdHis');

        $payload = [
            'event'           => 'h2h.webhook.test',
            'environment'     => 'sandbox',
            'sandbox'         => true,
            'test'            => true,
            'timestamp'       => $timestamp,
            'invoiceNumber'   => $testInvoice,
            'referenceNumber' => 'TEST-REF-' . now()->format('YmdHis'),
            'code'            => 'TEST-SKU-001',
            'productName'     => 'Webhook Test Event',
            'user_id'         => 'test_user_id',
            'zone_id'         => '',
            'statusCode'      => 'SUCCESS',
            'statusLabel'     => 'Sukses',
            'sn'              => '',
            'keteranganSn'    => '',
        ];

        // Use the existing delivery service to send the webhook
        $result = $this->deliveryService->sendTestWebhook($profile, $payload);

        if ($result['success']) {
            return redirect()->back()->with('flash_success',
                'Test webhook berhasil dikirim. Lihat log di bawah untuk status response.'
            );
        }

        return redirect()->back()->with('flash_error',
            'Test webhook gagal dikirim. Pastikan URL callback aktif dan bisa menerima POST request.'
        );
    }
}

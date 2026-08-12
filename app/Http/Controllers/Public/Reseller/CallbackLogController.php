<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerCallbackDelivery;
use App\Services\ResellerCallbackDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CallbackLogController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $deliveries = ResellerCallbackDelivery::query()
            ->whereHas('integration', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['integration:id,mode,integration_code'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(static fn (ResellerCallbackDelivery $delivery): array => [
                'id' => $delivery->getKey(),
                'created_at' => $delivery->created_at?->toIso8601String(),
                'status' => $delivery->status,
                'attempt_count' => (int) $delivery->attempt_count,
                'last_response_status' => $delivery->last_response_status,
                'order_id' => $delivery->order_id,
                'integration' => $delivery->integration ? [
                    'mode' => $delivery->integration->mode,
                    'integration_code' => $delivery->integration->integration_code,
                ] : null,
            ])
            ->withQueryString();

        // Phase 5 — Task 5.3: Tell frontend whether reseller has an active sandbox profile
        // so the "Test Webhook" button can be shown/disabled accordingly.
        $hasActiveSandboxProfile = \App\Models\ResellerCallbackProfile::query()
            ->whereHas('integration', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('mode', 'sandbox')
            )
            ->where('is_enabled', true)
            ->whereNotNull('callback_url')
            ->exists();

        return Inertia::render('Reseller/CallbackLogs', [
            'deliveries'              => $deliveries,
            'hasActiveSandboxProfile' => $hasActiveSandboxProfile,
        ]);
    }

    /**
     * Manually resend a failed callback delivery.
     *
     * Guards (service layer handles concurrency):
     *   - Delivery must belong to the authenticated user (via integration)
     *   - Status must be 'failed' (not 'delivered')
     *   - attempt_count must be < 4 (max 3 manual resend attempts)
     *   - Rate limited: 10/minute per user (reseller-callback-resend throttle)
     */
    public function resend(Request $request, int $deliveryId): RedirectResponse
    {
        $user = $request->user();

        // Auth isolation: only allow resend if delivery belongs to this user
        /** @var ResellerCallbackDelivery|null $delivery */
        $delivery = ResellerCallbackDelivery::query()
            ->whereHas('integration', fn ($q) => $q->where('user_id', $user->id))
            ->find($deliveryId);

        if (! $delivery) {
            return redirect()->back()->with('error', 'Callback log tidak ditemukan.');
        }

        if ($delivery->status === 'delivered') {
            return redirect()->back()->with('info', 'Callback ini sudah berhasil terkirim sebelumnya.');
        }

        if ($delivery->attempt_count >= 4) {
            return redirect()->back()->with('error', 'Batas maksimal pengiriman ulang telah tercapai (3x). Hubungi support jika masih diperlukan.');
        }

        $result = app(ResellerCallbackDeliveryService::class)->resend($delivery);

        return match ($result['status']) {
            'delivered' => redirect()->back()->with('success', 'Callback berhasil dikirim ulang ke endpoint Anda.'),
            'skipped'   => redirect()->back()->with('info', 'Callback sudah terkirim, tidak perlu dikirim ulang.'),
            'rejected'  => redirect()->back()->with('error', 'Batas maksimal pengiriman ulang telah tercapai.'),
            default     => redirect()->back()->with('error',
                'Gagal mengirim ulang callback. ' . ($result['reason'] ?? 'Coba lagi beberapa saat.')),
        };
    }
}

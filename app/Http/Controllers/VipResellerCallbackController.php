<?php

namespace App\Http\Controllers;

use App\Http\Controllers\provider\VipResellerController;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Services\InvoiceNotificationDispatcher;
use App\Services\PointService;
use App\Support\PembelianStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VipResellerCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $signature = (string) $request->header('X-Client-Signature', '');
        $payload = $request->json()->all();

        Log::debug('VIP Reseller callback received', [
            'signature_present' => $signature !== '',
            'payload_keys' => is_array($payload) ? array_keys($payload) : [],
            'data_keys' => isset($payload['data']) && is_array($payload['data']) ? array_keys($payload['data']) : [],
        ]);

        if (! $this->hasValidSignature($signature)) {
            Log::warning('VIP Reseller callback signature verification failed.');

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        if (! is_array($payload) || ! isset($payload['data']) || ! is_array($payload['data'])) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $data = $payload['data'];
        $trxId = trim((string) ($data['trxid'] ?? ''));

        if ($trxId === '') {
            return response()->json(['success' => false, 'message' => 'Missing trxid'], 400);
        }

        DB::transaction(function () use ($trxId, $data, $payload): void {
            $invoice = Pembelian::query()
                ->where('provider_order_id', $trxId)
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                Log::warning("VIP Reseller callback: invoice not found for trxid {$trxId}", [
                    'payload' => $payload,
                ]);

                return;
            }

            $statusMeta = VipResellerController::normalizeStatusMeta($data['status'] ?? null);
            $incomingStatus = PembelianStatus::normalize($statusMeta['internal_status']);
            $currentStatus = PembelianStatus::normalize($invoice->status);

            if (PembelianStatus::shouldIgnoreTransition($currentStatus, $incomingStatus)) {
                Log::debug("VIP Reseller callback ignored for {$trxId}", [
                    'current_status' => $invoice->status,
                    'incoming_status' => $data['status'] ?? null,
                ]);

                return;
            }

            $note = trim((string) ($data['note'] ?? ''));

            if (($statusMeta['is_partial'] ?? false) === true) {
                $note = trim(($note !== '' ? $note . ' | ' : '') . 'VIP partial: cek refund/penyelesaian manual di provider.');
            }

            $updateData = [
                'status' => PembelianStatus::preferredDatabaseLabel($statusMeta['internal_status']),
                'provider_order_id' => $trxId,
                'log' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ];

            if ($note !== '') {
                $updateData['keterangan_sn'] = $note;
            }

            $invoice->update($updateData);

            $payment = Pembayaran::where('order_id', $invoice->order_id)
                ->orderByDesc('id')
                ->first();

            $this->handleNotificationsAndRefunds(
                $invoice->fresh(['user']),
                $payment,
                $incomingStatus,
                $note,
                (bool) ($statusMeta['should_refund'] ?? false),
                (bool) ($statusMeta['is_partial'] ?? false),
            );
        });

        return response()->json(['success' => true]);
    }

    private function dispatchInvoiceNotificationSafely(Pembelian $invoice, string $transition): void
    {
        try {
            app(InvoiceNotificationDispatcher::class)->dispatchForTransition($invoice->fresh(), $transition);
        } catch (\Throwable $exception) {
            Log::error('VIP Reseller invoice notification dispatch failed', [
                'order_id' => $invoice->order_id,
                'transition' => $transition,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function hasValidSignature(string $signature): bool
    {
        $settings = SettingWeb::query()->where('id', 1)->first();
        $apiId = (string) ($settings->vip_apiid ?? '');
        $apiKey = (string) ($settings->vip_apikey ?? '');
        $apiSign = (string) ($settings->vip_sign ?? '');

        if ($apiId === '' || $apiKey === '' || $signature === '') {
            return false;
        }

        $expected = VipResellerController::resolveSignature($apiSign, $apiId, $apiKey);

        return hash_equals($expected, $signature);
    }

    private function handleNotificationsAndRefunds(
        Pembelian $invoice,
        ?Pembayaran $payment,
        string $incomingStatus,
        string $note,
        bool $shouldRefund,
        bool $isPartial
    ): void {
        try {
            if ($shouldRefund) {
                app(PointService::class)->refundRedeemedPoints($invoice);

                if (($payment?->metode ?? null) === 'SALDO' && $invoice->user) {
                    $invoice->user->increment('balance', $invoice->harga);
                }

                $this->dispatchInvoiceNotificationSafely(
                    $invoice,
                    InvoiceNotificationDispatcher::TRANSITION_PROVIDER_FAILED,
                );

                return;
            }

            if ($isPartial) {
                Log::warning('VIP Reseller partial status detected', [
                    'order_id' => $invoice->order_id,
                    'provider_order_id' => $invoice->provider_order_id,
                    'note' => $note,
                ]);

                return;
            }

            if ($incomingStatus === PembelianStatus::SUCCESS) {
                $this->dispatchInvoiceNotificationSafely(
                    $invoice,
                    InvoiceNotificationDispatcher::TRANSITION_PROVIDER_SUCCESS,
                );
            }
        } catch (\Throwable $e) {
            Log::error('VIP Reseller callback notification/refund error', [
                'order_id' => $invoice->order_id,
                'status' => $incomingStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

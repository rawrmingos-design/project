<?php

namespace App\Http\Controllers;

use App\Http\Controllers\provider\ApiGamesController;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\InvoiceNotificationDispatcher;
use App\Services\PointService;
use App\Support\PembelianStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiGamesCallbackController extends Controller
{
    public function handle(Request $request, ApiGamesController $apiGames)
    {
        $payload = $request->all();
        $refId = trim((string) ($payload['ref_id'] ?? ''));
        $signature = (string) $request->header('X-Apigames-Authorization', '');

        Log::debug('ApiGames callback received', [
            'signature_present' => $signature !== '',
            'payload_keys' => array_keys($payload),
        ]);

        if ($refId === '') {
            return response()->json(['success' => false, 'message' => 'Missing ref_id'], 400);
        }

        if (! $apiGames->verifyWebhookSignature($refId, $signature)) {
            Log::warning('ApiGames callback signature verification failed', [
                'ref_id' => $refId,
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        DB::transaction(function () use ($payload, $refId): void {
            $invoice = $this->resolveInvoiceForCallback($refId);

            if (! $invoice) {
                Log::warning('ApiGames callback invoice not found', [
                    'ref_id' => $refId,
                ]);

                return;
            }

            $statusMeta = ApiGamesController::normalizeStatusMeta($payload['status'] ?? null);
            $incomingStatus = PembelianStatus::normalize($statusMeta['internal_status']);
            $currentStatus = PembelianStatus::normalize($invoice->status);

            if (PembelianStatus::shouldIgnoreTransition($currentStatus, $incomingStatus)) {
                Log::debug('ApiGames callback ignored', [
                    'ref_id' => $refId,
                    'current_status' => $invoice->status,
                    'incoming_status' => $payload['status'] ?? null,
                ]);

                return;
            }

            $snValue = trim((string) ($payload['sn'] ?? ''));
            $messageValue = trim((string) ($payload['message'] ?? ''));
            $noteValue = $snValue !== '' ? $snValue : $messageValue;

            if (($statusMeta['is_partial'] ?? false) === true) {
                $noteValue = trim(($noteValue !== '' ? $noteValue . ' | ' : '') . 'API Games sukses sebagian: perlu cek penyelesaian/refund manual.');
            }

            if (($statusMeta['is_provider_validation'] ?? false) === true) {
                $noteValue = trim(($noteValue !== '' ? $noteValue . ' | ' : '') . 'API Games validasi provider: tunggu status final dari webhook/status check.');
            }

            $updateData = [
                'status' => PembelianStatus::preferredDatabaseLabel($incomingStatus),
                'provider_order_id' => trim((string) ($payload['trx_id'] ?? $invoice->provider_order_id ?: $refId)),
                'log' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ];

            if ($noteValue !== '') {
                $updateData['keterangan_sn'] = $noteValue;
            }

            $invoice->update($updateData);

            $payment = Pembayaran::query()
                ->where('order_id', $invoice->order_id)
                ->orderByDesc('id')
                ->first();

            $this->handleNotificationsAndRefunds(
                $invoice->fresh(['user']),
                $payment,
                $incomingStatus,
                $noteValue,
                (bool) ($statusMeta['should_refund'] ?? false),
                (bool) ($statusMeta['is_partial'] ?? false),
                (bool) ($statusMeta['is_provider_validation'] ?? false),
            );
        });

        return response()->json(['success' => true]);
    }

    private function dispatchInvoiceNotificationSafely(Pembelian $invoice, string $transition): void
    {
        try {
            app(InvoiceNotificationDispatcher::class)->dispatchForTransition($invoice->fresh(), $transition);
        } catch (\Throwable $exception) {
            Log::error('ApiGames invoice notification dispatch failed', [
                'order_id' => $invoice->order_id,
                'transition' => $transition,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveInvoiceForCallback(string $refId): ?Pembelian
    {
        $activeAttemptInvoice = Pembelian::query()
            ->where(function ($query) use ($refId) {
                $query->where('active_attempt_reference', $refId)
                    ->orWhere('display_order_id', $refId);
            })
            ->lockForUpdate()
            ->first();

        if ($activeAttemptInvoice) {
            return $activeAttemptInvoice;
        }

        return Pembelian::query()
            ->where('order_id', $refId)
            ->where(function ($query) {
                $query->whereNull('invoice_version')
                    ->orWhere('invoice_version', 0);
            })
            ->lockForUpdate()
            ->first();
    }

    private function handleNotificationsAndRefunds(
        Pembelian $invoice,
        ?Pembayaran $payment,
        string $incomingStatus,
        string $note,
        bool $shouldRefund,
        bool $isPartial,
        bool $isProviderValidation
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

            if ($isPartial || $isProviderValidation) {
                Log::warning('ApiGames non-final provider status detected', [
                    'order_id' => $invoice->order_id,
                    'provider_order_id' => $invoice->provider_order_id,
                    'status' => $incomingStatus,
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
        } catch (\Throwable $exception) {
            Log::error('ApiGames callback notification/refund error', [
                'order_id' => $invoice->order_id,
                'status' => $incomingStatus,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

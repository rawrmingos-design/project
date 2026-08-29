<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Services\InvoiceNotificationDispatcher;
use App\Services\PointService;
use App\Support\PembelianStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DigiflazzCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $event = strtolower((string) $request->header('X-Digiflazz-Event', ''));
        $signature = (string) $request->header('X-Hub-Signature', '');

        Log::debug('Digiflazz callback received', [
            'event' => $event,
            'user_agent' => $request->header('User-Agent'),
            'signature_present' => $signature !== '',
            'payload_keys' => is_array($payload) ? array_keys($payload) : [],
            'data_keys' => isset($payload['data']) && is_array($payload['data']) ? array_keys($payload['data']) : [],
        ]);

        if (!$this->hasValidSignature($request, $signature)) {
            Log::warning('Digiflazz callback signature verification failed', [
                'event' => $event,
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
            Log::warning('Digiflazz callback payload invalid', [
                'payload_type' => gettype($payload),
                'payload_keys' => is_array($payload) ? array_keys($payload) : [],
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $data = $payload['data'];
        $refId = (string) ($data['ref_id'] ?? '');

        if ($refId === '') {
            Log::warning('Digiflazz callback missing ref_id', [
                'event' => $event,
                'data_keys' => array_keys($data),
            ]);

            return response()->json(['success' => false, 'message' => 'Missing ref_id'], 400);
        }

        DB::transaction(function () use ($refId, $data, $payload, $event) {
            $invoice = $this->resolveInvoiceForCallback($refId);

            if (!$invoice) {
                $staleInvoice = $this->findPotentialStaleInvoice($refId);

                if ($staleInvoice) {
                    Log::debug("Digiflazz callback ignored for stale attempt reference {$refId}", [
                        'event' => $event,
                        'order_id' => $staleInvoice->order_id,
                        'active_attempt_reference' => $staleInvoice->active_attempt_reference,
                        'provider_order_id' => $staleInvoice->provider_order_id,
                    ]);
                } else {
                    Log::error("Digiflazz callback: invoice not found for ref_id {$refId}", [
                        'event' => $event,
                        'payload' => $payload,
                    ]);
                }

                return;
            }

            $incomingStatus = PembelianStatus::normalize($data['status'] ?? null);
            $currentStatus = PembelianStatus::normalize($invoice->status);
            $statusTransitioned = $currentStatus !== $incomingStatus;
            $snValue = trim((string) ($data['sn'] ?? ''));
            $messageValue = trim((string) ($data['message'] ?? ''));
            $snOrMessage = $snValue !== '' ? $snValue : $messageValue;

            if (PembelianStatus::shouldIgnoreTransition($currentStatus, $incomingStatus)) {
                Log::debug("Digiflazz callback ignored for {$refId}", [
                    'current_status' => $invoice->status,
                    'incoming_status' => $data['status'] ?? null,
                    'event' => $event,
                ]);

                return;
            }

            $updateData = [
                'status' => $statusTransitioned
                    ? PembelianStatus::preferredDatabaseLabel($incomingStatus)
                    : $invoice->status,
                'provider_order_id' => $this->resolveProviderOrderId($invoice, $refId),
                'log' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ];

            if ($snOrMessage !== '') {
                $updateData['keterangan_sn'] = $snOrMessage;
            }

            if ($invoice->tipe_transaksi === 'voucher' && $snValue !== '') {
                $updateData['voucher'] = $snValue;
            }

            $invoice->update($updateData);

            $payment = Pembayaran::where('order_id', $invoice->order_id)
                ->orderByDesc('id')
                ->first();

            $this->handleNotificationsAndRefunds(
                $invoice,
                $payment,
                $incomingStatus,
                $statusTransitioned,
            );

            Log::debug("Digiflazz callback processed for {$refId}", [
                'event' => $event,
                'from_status' => $invoice->getOriginal('status'),
                'to_status' => $incomingStatus,
            ]);
        });

        return response()->json(['success' => true]);
    }

    private function hasValidSignature(Request $request, string $signature): bool
    {
        $secret = (string) config('providers.digiflazz.webhook_secret');

        if ($secret === '') {
            Log::error('Digiflazz webhook secret is not configured.');

            return false;
        }

        $expectedSignature = 'sha1=' . hash_hmac('sha1', $request->getContent(), $secret);

        return hash_equals($expectedSignature, $signature);
    }

    private function handleNotificationsAndRefunds(
        Pembelian $invoice,
        ?Pembayaran $payment,
        string $incomingStatus,
        bool $statusTransitioned
    ): void {
        try {
            if (!$statusTransitioned) {
                Log::debug('Digiflazz callback duplicate terminal side effects skipped', [
                    'order_id' => $invoice->order_id,
                    'status' => $incomingStatus,
                ]);

                return;
            }

            if ($incomingStatus === PembelianStatus::FAILED || $incomingStatus === PembelianStatus::CANCELLED) {
                app(PointService::class)->refundRedeemedPoints($invoice);

                if (($payment?->metode ?? null) === 'SALDO' && $invoice->user) {
                    $invoice->user->increment('balance', $invoice->harga);
                    Log::info("Refunded SALDO for Digiflazz order {$invoice->order_id}", [
                        'username' => $invoice->user->username,
                    ]);
                }

                $this->dispatchInvoiceNotificationSafely(
                    $invoice,
                    InvoiceNotificationDispatcher::TRANSITION_PROVIDER_FAILED,
                );

                return;
            }

            if ($incomingStatus === PembelianStatus::SUCCESS) {
                $this->dispatchInvoiceNotificationSafely(
                    $invoice,
                    InvoiceNotificationDispatcher::TRANSITION_PROVIDER_SUCCESS,
                );
            }
        } catch (\Throwable $e) {
            Log::error('Digiflazz callback notification/refund error', [
                'order_id' => $invoice->order_id,
                'status' => $incomingStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function dispatchInvoiceNotificationSafely(Pembelian $invoice, string $transition): void
    {
        try {
            app(InvoiceNotificationDispatcher::class)->dispatchForTransition(
                $invoice->fresh(),
                $transition,
            );
        } catch (\Throwable $exception) {
            Log::error('Digiflazz invoice notification dispatch failed', [
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

        $providerOrderInvoice = Pembelian::query()
            ->where('provider_order_id', $refId)
            ->where(function ($query) {
                $query->whereNull('active_attempt_reference')
                    ->orWhere('active_attempt_reference', '')
                    ->orWhereColumn('active_attempt_reference', 'provider_order_id');
            })
            ->lockForUpdate()
            ->first();

        if ($providerOrderInvoice) {
            return $providerOrderInvoice;
        }

        return Pembelian::query()
            ->where('order_id', $refId)
            ->where(function ($query) {
                $query->whereNull('invoice_version')
                    ->orWhere('invoice_version', 0);
            })
            ->where(function ($query) {
                $query->whereNull('active_attempt_reference')
                    ->orWhere('active_attempt_reference', '')
                    ->orWhereColumn('active_attempt_reference', 'order_id');
            })
            ->lockForUpdate()
            ->first();
    }

    private function findPotentialStaleInvoice(string $refId): ?Pembelian
    {
        return Pembelian::query()
            ->where(function ($query) use ($refId) {
                $query->where('provider_order_id', $refId)
                    ->orWhere('order_id', $refId)
                    ->orWhere('display_order_id', $refId)
                    ->orWhere('active_attempt_reference', $refId);
            })
            ->first();
    }

    private function resolveProviderOrderId(Pembelian $invoice, string $refId): string
    {
        $activeReference = trim((string) ($invoice->active_attempt_reference ?: $invoice->display_order_id ?: $invoice->order_id));

        if ($activeReference !== '' && $refId === $activeReference) {
            return $refId;
        }

        return (string) ($invoice->provider_order_id ?: $refId);
    }
}

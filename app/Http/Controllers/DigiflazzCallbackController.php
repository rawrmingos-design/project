<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pembelian;
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

        Log::info('Digiflazz callback received', [
            'event' => $event,
            'user_agent' => $request->header('User-Agent'),
            'signature_present' => $signature !== '',
            'payload' => $payload,
        ]);

        if (!$this->hasValidSignature($request, $signature)) {
            Log::warning('Digiflazz callback signature verification failed', [
                'event' => $event,
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
            Log::warning('Digiflazz callback payload invalid', ['payload' => $payload]);

            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $data = $payload['data'];
        $refId = (string) ($data['ref_id'] ?? '');

        if ($refId === '') {
            Log::warning('Digiflazz callback missing ref_id', ['payload' => $payload]);

            return response()->json(['success' => false, 'message' => 'Missing ref_id'], 400);
        }

        DB::transaction(function () use ($refId, $data, $payload, $event) {
            $invoice = Pembelian::query()
                ->where(function ($query) use ($refId) {
                    $query->where('provider_order_id', $refId)
                        ->orWhere('order_id', $refId);
                })
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                Log::error("Digiflazz callback: invoice not found for ref_id {$refId}", [
                    'event' => $event,
                    'payload' => $payload,
                ]);

                return;
            }

            $incomingStatus = $this->normalizeStatus($data['status'] ?? null);
            $currentStatus = $this->normalizeStatus($invoice->status);
            $snValue = trim((string) ($data['sn'] ?? ''));
            $messageValue = trim((string) ($data['message'] ?? ''));
            $snOrMessage = $snValue !== '' ? $snValue : $messageValue;

            if ($this->shouldIgnoreStatusTransition($currentStatus, $incomingStatus)) {
                Log::info("Digiflazz callback ignored for {$refId}", [
                    'current_status' => $invoice->status,
                    'incoming_status' => $data['status'] ?? null,
                    'event' => $event,
                ]);

                return;
            }

            $updateData = [
                'status' => $incomingStatus,
                'provider_order_id' => $invoice->provider_order_id ?: $refId,
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

            $this->handleNotificationsAndRefunds($invoice, $payment, $incomingStatus, $messageValue, $snOrMessage);

            Log::info("Digiflazz callback processed for {$refId}", [
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

    private function normalizeStatus(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'sukses', 'success', 'Sukses' => 'Sukses',
            'pending', 'process', 'Pending', 'processing', 'proses' => 'Pending',
            'gagal', 'failed', 'batal', 'Batal', 'cancelled', 'canceled' => 'Gagal',
            default => trim((string) $status) !== '' ? trim((string) $status) : 'Pending',
        };
    }

    private function shouldIgnoreStatusTransition(string $currentStatus, string $incomingStatus): bool
    {
        $isCurrentFinal = in_array($currentStatus, ['Sukses', 'Gagal'], true);
        $isIncomingFinal = in_array($incomingStatus, ['Sukses', 'Gagal'], true);

        if ($isCurrentFinal && !$isIncomingFinal) {
            return true;
        }

        if ($currentStatus === 'Sukses' && $incomingStatus === 'Gagal') {
            return true;
        }

        return false;
    }

    private function handleNotificationsAndRefunds(
        Pembelian $invoice,
        ?Pembayaran $payment,
        string $incomingStatus,
        string $messageValue,
        string $snOrMessage
    ): void {
        try {
            $waService = new \App\Services\WhatsappNotificationService();
            $emailService = new \App\Services\EmailNotificationService();
            $targetWa = $payment?->no_pembeli ?: ($invoice->user->no_wa ?? null);
            $targetEmail = $invoice->email_pembeli ?? ($invoice->user->email ?? null);

            if ($incomingStatus === 'Gagal') {
                app(\App\Services\PointService::class)->refundRedeemedPoints($invoice);

                if (($payment?->metode ?? null) === 'SALDO' && $invoice->user) {
                    $invoice->user->increment('balance', $invoice->harga);
                    Log::info("Refunded SALDO for Digiflazz order {$invoice->order_id}", [
                        'username' => $invoice->user->username,
                    ]);
                }

                if ($targetWa) {
                    $waService->sendNotification($targetWa, 'transaction_failed', [
                        'nickname' => $invoice->nickname,
                        'order_id' => $invoice->order_id,
                        'product' => $invoice->layanan,
                        'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                        'reason' => $messageValue !== '' ? $messageValue : 'Transaksi dibatalkan oleh provider.',
                    ]);
                }

                if ($targetEmail) {
                    $emailService->sendTransactionEmail($targetEmail, [
                        'order_id' => $invoice->order_id,
                        'product' => $invoice->layanan,
                        'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                        'status' => 'Failed',
                        'nickname' => $invoice->nickname,
                        'note' => $messageValue !== '' ? $messageValue : 'Transaksi dibatalkan oleh provider.',
                    ]);
                }

                return;
            }

            if ($incomingStatus === 'Sukses') {
                if ($targetWa) {
                    $waService->sendNotification($targetWa, 'transaction_success', [
                        'nickname' => $invoice->nickname,
                        'order_id' => $invoice->order_id,
                        'product' => $invoice->layanan,
                        'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                        'sn' => $snOrMessage,
                    ]);
                }

                if ($targetEmail) {
                    $emailService->sendTransactionEmail($targetEmail, [
                        'order_id' => $invoice->order_id,
                        'product' => $invoice->layanan,
                        'amount' => 'Rp ' . number_format($invoice->harga, 0, ',', '.'),
                        'status' => 'Success',
                        'nickname' => $invoice->nickname,
                        'sn' => $snOrMessage,
                        'note' => 'Terima kasih telah berbelanja.',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Digiflazz callback notification/refund error', [
                'order_id' => $invoice->order_id,
                'status' => $incomingStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

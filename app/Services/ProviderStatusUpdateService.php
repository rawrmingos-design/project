<?php

namespace App\Services;

use App\Events\InvoiceStatusUpdated;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use App\Support\PembelianStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderStatusUpdateService
{
    public function apply(Pembelian $pembelian, array $providerResult, string $source = 'provider'): bool
    {
        $transitioned = false;
        $orderId = (string) $pembelian->order_id;

        DB::transaction(function () use ($pembelian, $providerResult, $source, &$transitioned): void {
            $locked = Pembelian::query()
                ->whereKey($pembelian->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            $incomingStatus = PembelianStatus::normalize($providerResult['order_status'] ?? PembelianStatus::PENDING);
            if ($incomingStatus === PembelianStatus::UNKNOWN) {
                $incomingStatus = PembelianStatus::PENDING;
            }

            $currentStatus = PembelianStatus::normalize($locked->status);
            if ($currentStatus === PembelianStatus::PROCESSING && $incomingStatus === PembelianStatus::PENDING) {
                $incomingStatus = PembelianStatus::PROCESSING;
            }
            $providerOrderId = trim((string) ($providerResult['transaction_id'] ?? ''));
            $message = trim((string) ($providerResult['message'] ?? ''));
            $sn = trim((string) ($providerResult['sn'] ?? ''));

            if ($this->isStaleAttempt($locked, $providerOrderId)) {
                $locked->forceFill([
                    'log' => $this->appendBoundedLog(
                        $locked->log,
                        $this->logPrefix($source) . ' stale provider status ignored at ' . now()->format('Y-m-d H:i:s') . ': ' . $providerOrderId,
                    ),
                ])->saveQuietly();

                return;
            }

            if (PembelianStatus::shouldIgnoreTransition($locked->status, $incomingStatus)) {
                $locked->forceFill([
                    'log' => $this->appendBoundedLog(
                        $locked->log,
                        $this->logPrefix($source) . ' ignored final status transition at ' . now()->format('Y-m-d H:i:s') . ': ' . $message,
                    ),
                ])->saveQuietly();

                return;
            }

            $nextStatus = PembelianStatus::preferredDatabaseLabel($incomingStatus);
            $data = [
                'status' => $nextStatus,
                'log' => $this->appendBoundedLog(
                    $locked->log,
                    $this->buildLogEntry($source, $incomingStatus, $message),
                ),
                'reset_status' => $this->nextResetStatus($locked, $incomingStatus),
            ];

            if ($providerOrderId !== '') {
                $data['provider_order_id'] = $providerOrderId;
                $data['active_attempt_token'] = $providerOrderId;
            }

            if ($sn !== '') {
                $data['keterangan_sn'] = $sn;
            } elseif (in_array($incomingStatus, [PembelianStatus::PENDING, PembelianStatus::PROCESSING], true)) {
                $data['keterangan_sn'] = $locked->keterangan_sn ?: 'Sedang Diproses';
            } elseif ($message !== '') {
                $data['keterangan_sn'] = $message;
            }

            $locked->forceFill($data)->save();
            $transitioned = $currentStatus !== $incomingStatus;

            if ($transitioned && in_array($incomingStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
                $this->refundFailedOrder($locked->fresh(['pembayaran', 'user']));
            }
        });

        if ($transitioned) {
            InvoiceStatusUpdated::dispatchForOrder($orderId);
        }

        return $transitioned;
    }

    public function appendLog(Pembelian $pembelian, string $entry): void
    {
        DB::transaction(function () use ($pembelian, $entry): void {
            $locked = Pembelian::query()
                ->whereKey($pembelian->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            $locked->forceFill([
                'log' => $this->appendBoundedLog($locked->log, $entry),
            ])->saveQuietly();
        });
    }

    private function isStaleAttempt(Pembelian $pembelian, string $providerOrderId): bool
    {
        if ($providerOrderId === '') {
            return false;
        }

        $activeAttemptToken = trim((string) ($pembelian->active_attempt_token ?? ''));
        $providerOrder = trim((string) ($pembelian->provider_order_id ?? ''));

        if ($activeAttemptToken === '' && $providerOrder === '') {
            return false;
        }

        return $providerOrderId !== $activeAttemptToken && $providerOrderId !== $providerOrder;
    }

    private function nextResetStatus(Pembelian $pembelian, string $incomingStatus): string
    {
        if ((int) ($pembelian->invoice_version ?? 0) <= 0) {
            return $pembelian->reset_status;
        }

        return match ($incomingStatus) {
            PembelianStatus::SUCCESS => 'completed',
            PembelianStatus::FAILED, PembelianStatus::CANCELLED => 'failed',
            default => 'processing',
        };
    }

    private function refundFailedOrder(Pembelian $pembelian): void
    {
        if ($pembelian->refunded_at !== null) {
            return;
        }

        app(PointService::class)->refundRedeemedPoints($pembelian);

        $payment = $pembelian->pembayaran ?: Pembayaran::query()
            ->where('order_id', $pembelian->order_id)
            ->first();

        $shouldRefundSaldo = strtolower(trim((string) ($payment?->metode ?? ''))) === 'saldo'
            || $pembelian->traffic_source === 'reseller_h2h';

        if (! $shouldRefundSaldo) {
            return;
        }

        $refundAmount = (int) $pembelian->harga;
        if ($refundAmount <= 0) {
            return;
        }

        $user = $pembelian->user ?: User::query()
            ->where('username', $pembelian->username)
            ->first();

        if (! $user) {
            Log::error('ProviderStatusUpdateService: user not found for refund', [
                'pembelian_id' => $pembelian->getKey(),
                'order_id' => $pembelian->order_id,
                'username' => $pembelian->username,
            ]);

            return;
        }

        User::query()->whereKey($user->getKey())->increment('balance', $refundAmount);

        Pembelian::query()
            ->whereKey($pembelian->getKey())
            ->whereNull('refunded_at')
            ->update([
                'refunded_at' => now(),
                'refund_amount' => $refundAmount,
            ]);
    }

    private function buildLogEntry(string $source, string $incomingStatus, string $message): string
    {
        $prefix = $this->logPrefix($source);
        $time = now()->format('Y-m-d H:i:s');
        $message = $message !== '' ? ': ' . $message : '';

        if ($source === 'queued_provider_dispatch' && in_array($incomingStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
            return "{$prefix} final failure at {$time}{$message}";
        }

        return "{$prefix} at {$time}{$message}";
    }

    private function logPrefix(string $source): string
    {
        return match ($source) {
            'queued_provider_dispatch' => 'Queued provider dispatch',
            'sufpayment_polling' => 'SufPayment polling',
            default => ucfirst(str_replace('_', ' ', $source)),
        };
    }

    private function appendBoundedLog(?string $existingLog, string $entry, int $limit = 1000): string
    {
        $existingLog = trim((string) $existingLog);
        $entry = trim($entry);

        $combined = $existingLog !== ''
            ? $existingLog . PHP_EOL . $entry
            : $entry;

        if (mb_strlen($combined) <= $limit) {
            return $combined;
        }

        return mb_substr($combined, -$limit);
    }
}

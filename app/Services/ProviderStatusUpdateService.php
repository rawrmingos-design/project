<?php

namespace App\Services;

use App\Events\InvoiceStatusUpdated;
use App\Models\Pembayaran;
use App\Services\InvoiceNotificationDispatcher;
use App\Models\Pembelian;
use App\Models\User;
use App\Support\PembelianStatus;
use App\Support\ProviderTransportError;
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

            $transitioned = $this->applyWithinTransaction($locked, $providerResult, $source);
        });

        if ($transitioned) {
            InvoiceStatusUpdated::dispatchForOrder($orderId);

            $fresh = $pembelian->fresh();
            if (! $fresh) {
                return $transitioned;
            }

            $transition = match (PembelianStatus::normalize($fresh->status)) {
                PembelianStatus::SUCCESS => InvoiceNotificationDispatcher::TRANSITION_PROVIDER_SUCCESS,
                PembelianStatus::FAILED, PembelianStatus::CANCELLED => InvoiceNotificationDispatcher::TRANSITION_PROVIDER_FAILED,
                default => InvoiceNotificationDispatcher::TRANSITION_PAYMENT_PENDING,
            };

            try {
                app(InvoiceNotificationDispatcher::class)->dispatchForTransition($fresh, $transition);
            } catch (\Throwable $exception) {
                Log::error('Provider status invoice notification dispatch failed', [
                    'order_id' => $orderId,
                    'source' => $source,
                    'transition' => $transition,
                    'error' => $exception->getMessage(),
                ]);
            }
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

    /**
     * Inti penerapan status provider. HARUS dipanggil di dalam transaksi dengan
     * baris `$locked` sudah ter-`lockForUpdate()`. Dipakai oleh apply() maupun
     * recordTransportFailure() supaya keputusan yang diambil dari dalam transaksi
     * memakai semantik yang sama persis.
     *
     * @return bool true kalau status order benar-benar berubah.
     */
    private function applyWithinTransaction(Pembelian $locked, array $providerResult, string $source): bool
    {
        $incomingStatus = PembelianStatus::normalize($providerResult['order_status'] ?? PembelianStatus::PENDING);
        if ($incomingStatus === PembelianStatus::UNKNOWN) {
            $incomingStatus = PembelianStatus::PENDING;
        }

        $currentStatus = PembelianStatus::normalize($locked->status);
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

            return false;
        }

        if (PembelianStatus::shouldIgnoreTransition($locked->status, $incomingStatus)) {
            $locked->forceFill([
                'log' => $this->appendBoundedLog(
                    $locked->log,
                    $this->logPrefix($source) . ' ignored final status transition at ' . now()->format('Y-m-d H:i:s') . ': ' . $message,
                ),
            ])->saveQuietly();

            return false;
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
            // Pesan kegagalan transport yang sudah BASI jangan menempel pada status
            // non-final: order pernah gagal dihubungi, lalu poll berikutnya berhasil dan
            // mengembalikan status non-final — admin tidak boleh melihat "Processing"
            // dengan keterangan "Connection Error: cURL error 28 ...".
            $existingNote = (string) ($locked->keterangan_sn ?? '');
            $data['keterangan_sn'] = ProviderTransportError::isStaleFailureMessage($existingNote)
                ? ProviderTransportError::neutralProcessingNote()
                : ($existingNote ?: ProviderTransportError::neutralProcessingNote());
        } elseif ($message !== '') {
            $data['keterangan_sn'] = $message;
        }

        if (($providerResult['transport_failure_reset'] ?? false) === true
            || in_array($incomingStatus, [PembelianStatus::SUCCESS, PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
            // Jawaban DEFINITIF dari provider mereset hitungan kegagalan transport:
            // hitungan hanya boleh menghitung kegagalan beruntun, bukan total.
            $data['transport_failure_count'] = 0;
        }

        $locked->forceFill($data)->save();
        $transitioned = $currentStatus !== $incomingStatus;

        if ($transitioned && in_array($incomingStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
            $this->refundFailedOrder($locked->fresh(['pembayaran', 'user']));
        }

        return $transitioned;
    }

    /**
     * Versi appendLog yang bekerja pada baris yang sudah terkunci di dalam transaksi.
     */
    private function appendLogLocked(Pembelian $locked, string $entry): void
    {
        $locked->forceFill([
            'log' => $this->appendBoundedLog($locked->log, $entry),
        ])->saveQuietly();
    }

    /**
     * Catat satu kegagalan TRANSPORT (timeout/koneksi) untuk sebuah order.
     *
     * Kegagalan transport bukan vonis provider: request-nya tidak pernah sampai,
     * jadi tidak ada informasi status apa pun. Order yang sudah dibayar TIDAK boleh
     * langsung di-Gagal-kan karena satu timeout.
     *
     * Aturannya: 1..(N-1) kegagalan beruntun -> status dibiarkan apa adanya dan
     * hanya dihitung; kegagalan ke-N -> order diputus Gagal. Setiap jawaban
     * definitif dari provider mereset hitungan ini.
     *
     * @return bool true kalau order diputus Gagal pada pemanggilan ini.
     */
    public function recordTransportFailure(
        Pembelian $pembelian,
        ?string $message = null,
        string $source = 'provider_status_polling',
    ): bool {
        $max = ProviderTransportError::maxConsecutive();
        $message = trim((string) $message);
        $failedNow = false;

        DB::transaction(function () use ($pembelian, $message, $source, $max, &$failedNow): void {
            $locked = Pembelian::query()
                ->whereKey($pembelian->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            // Jangan pernah mengubah order yang sudah final.
            if (in_array(PembelianStatus::normalize($locked->status), [
                PembelianStatus::SUCCESS,
                PembelianStatus::CANCELLED,
                PembelianStatus::EXPIRED,
                PembelianStatus::REFUNDED,
            ], true)) {
                return;
            }

            $attempt = (int) $locked->transport_failure_count + 1;

            if ($attempt < $max) {
                $locked->forceFill(['transport_failure_count' => $attempt])->saveQuietly();
                $this->appendLogLocked(
                    $locked,
                    $this->logPrefix($source) . ' transport failure ' . $attempt . '/' . $max
                        . ' at ' . now()->format('Y-m-d H:i:s')
                        . ': status tidak diubah, tunggu percobaan berikutnya.'
                        . ($message !== '' ? ' (' . $message . ')' : ''),
                );

                Log::warning('Provider transport failure below threshold; order status left unchanged.', [
                    'pembelian_id' => $locked->getKey(),
                    'order_id' => $locked->order_id,
                    'source' => $source,
                    'attempt' => $attempt,
                    'max' => $max,
                    'message' => $message,
                ]);

                return;
            }

            $this->applyWithinTransaction($locked, [
                'success' => false,
                'order_status' => PembelianStatus::FAILED,
                'transaction_id' => $locked->provider_order_id,
                'provider_status' => null,
                'message' => 'Provider tidak bisa dihubungi setelah ' . $max . ' percobaan beruntun'
                    . ($message !== '' ? ': ' . $message : '.'),
                'sn' => '',
                'raw' => null,
                'transport_failure_reset' => true,
            ], $source);

            $failedNow = true;

            Log::warning('Provider marked failed after consecutive transport failures.', [
                'pembelian_id' => $locked->getKey(),
                'order_id' => $locked->order_id,
                'source' => $source,
                'attempt' => $attempt,
                'max' => $max,
                'message' => $message,
            ]);
        });

        return $failedNow;
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
        app(\App\Services\VoucherService::class)->restoreStockForOrder($pembelian);

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

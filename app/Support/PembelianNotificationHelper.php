<?php

namespace App\Support;

use App\Models\Pembelian;
use App\Services\EmailNotificationService;
use App\Services\WhatsappNotificationService;

class PembelianNotificationHelper
{
    public static function whatsappTarget(Pembelian $record): ?string
    {
        $record->loadMissing(['pembayaran', 'user']);

        $targets = [
            $record->pembayaran?->no_pembeli,
            $record->user?->no_wa,
        ];

        foreach ($targets as $target) {
            $normalized = trim((string) $target);
            if ($normalized !== '' && $normalized !== '-') {
                return $normalized;
            }
        }

        return null;
    }

    public static function emailTarget(Pembelian $record): ?string
    {
        $record->loadMissing(['user']);

        $targets = [
            $record->email_pembeli,
            $record->user?->email,
        ];

        foreach ($targets as $target) {
            $normalized = trim((string) $target);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    public static function channelOptions(Pembelian $record): array
    {
        $hasWhatsapp = self::whatsappTarget($record) !== null;
        $hasEmail = self::emailTarget($record) !== null;

        $options = [];

        if ($hasWhatsapp) {
            $options['whatsapp'] = 'WhatsApp Only';
        }

        if ($hasEmail) {
            $options['email'] = 'Email Only';
        }

        if ($hasWhatsapp && $hasEmail) {
            $options['both'] = 'WhatsApp + Email';
        }

        return $options;
    }

    public static function availabilityMessage(Pembelian $record): string
    {
        $hasWhatsapp = self::whatsappTarget($record) !== null;
        $hasEmail = self::emailTarget($record) !== null;

        return match (true) {
            $hasWhatsapp && $hasEmail => 'Nomor WhatsApp dan email tersedia. Anda bisa mengirim notifikasi ke salah satu channel atau keduanya sekaligus.',
            $hasWhatsapp => 'Email tidak tersedia. Anda hanya bisa mengirim notifikasi lewat WhatsApp.',
            $hasEmail => 'Nomor WhatsApp tidak tersedia. Anda hanya bisa mengirim notifikasi lewat email.',
            default => 'Order ini tidak memiliki nomor WhatsApp maupun email yang bisa dipakai untuk mengirim notifikasi.',
        };
    }

    public static function slugAndNote(Pembelian $record): array
    {
        $status = strtolower(trim((string) $record->status));
        $slug = 'transaction_pending';
        $note = 'Pesanan sedang menunggu respon provider.';

        if (in_array($status, ['success', 'sukses'], true)) {
            $slug = 'transaction_success';
            $note = 'Terima kasih telah berbelanja.';
        } elseif (in_array($status, ['failed', 'gagal', 'batal', 'expired'], true)) {
            $slug = 'transaction_failed';
            $note = 'Mohon maaf, transaksi Anda gagal atau kadaluarsa.';
        }

        return [$slug, $note];
    }

    public static function payload(Pembelian $record): array
    {
        [, $note] = self::slugAndNote($record);

        return [
            'nickname' => $record->nickname ?? 'Pelanggan',
            'order_id' => $record->order_id,
            'product' => $record->layanan,
            'amount' => 'Rp ' . number_format((int) $record->harga, 0, ',', '.'),
            'status' => $record->status,
            'sn' => $record->keterangan_sn ?: ($record->voucher ?: 'Sedang Diproses'),
            'note' => $note,
        ];
    }

    public static function send(Pembelian $record, string $channel): array
    {
        $availableChannels = array_keys(self::channelOptions($record));

        if (! in_array($channel, $availableChannels, true)) {
            return ['Channel tidak tersedia'];
        }

        [$slug] = self::slugAndNote($record);
        $payload = self::payload($record);
        $targetWa = self::whatsappTarget($record);
        $targetEmail = self::emailTarget($record);
        $results = [];

        $waService = app(WhatsappNotificationService::class);
        $emailService = app(EmailNotificationService::class);

        if (in_array($channel, ['whatsapp', 'both'], true)) {
            if ($targetWa) {
                $waResult = $waService->sendNotification($targetWa, $slug, $payload);
                $results[] = 'WhatsApp: ' . (($waResult['success'] ?? false) ? 'Sent' : 'Failed');
            } else {
                $results[] = 'WhatsApp: No Number';
            }
        }

        if (in_array($channel, ['email', 'both'], true)) {
            if ($targetEmail) {
                $emailResult = $emailService->sendTransactionEmail($targetEmail, $payload);
                $results[] = 'Email: ' . ($emailResult ? 'Sent' : 'Failed');
            } else {
                $results[] = 'Email: No Address';
            }
        }

        return $results;
    }
}

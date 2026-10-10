<?php

namespace App\Tenancy;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceEvent;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/**
 * Penagihan berulang langganan tenant.
 *
 * Alur (keputusan terkunci):
 *  - B1: invoice perpanjangan terbit H-3 sebelum `current_period_end`.
 *  - B7: masuk masa tenggang → denda FLAT sekali (bukan per hari).
 *  - B2: lewat tenggang (3 hari) → tenant disuspend.
 *  - B3: hanya tenant, data TIDAK dihapus.
 *  - B4: subdomain TIDAK dilepas.
 *
 * Yang WAJIB dijaga: nominal invoice tidak pernah diubah selama tautan
 * pembayarannya masih hidup. `SubscriptionWebhookController` menolak callback
 * kalau `amount` berbeda dari yang dibayar user — mengubahnya berarti user bisa
 * sudah membayar tapi langganannya tidak pernah aktif. Karena itu, tagihan fase
 * baru dibuat sebagai invoice BARU (dengan `gateway_ref` baru) setelah invoice
 * lama di-`expired`.
 */
class BillingRenewalService
{
    public const KIND_RENEWAL = 'renewal';

    public function __construct(
        private readonly DuitkuSubscriptionPaymentService $duitku,
    ) {}

    /**
     * Perpanjangan: terbitkan invoice untuk langganan yang mendekati / melewati
     * akhir periode. Idempotent — dijalankan berkali-kali hasilnya sama.
     *
     * @return array{created:int, skipped:int, expired:int}
     */
    public function renewDueSubscriptions(): array
    {
        $daysBefore = (int) config('billing.reminder_days_before', 3);
        $graceDays = (int) config('billing.grace_days', 3);
        $lateFee = (int) config('billing.late_fee', 10000);

        $stats = ['created' => 0, 'skipped' => 0, 'expired' => 0];

        Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', now()->addDays($daysBefore))
            ->with('tenant')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$stats, $graceDays, $lateFee): void {
                foreach ($subscriptions as $subscription) {
                    $hasil = $this->renewOne($subscription, $graceDays, $lateFee);

                    foreach ($hasil as $kunci => $nilai) {
                        $stats[$kunci] += $nilai;
                    }
                }
            });

        return $stats;
    }

    /**
     * @return array{created:int, skipped:int, expired:int}
     */
    private function renewOne(Subscription $subscription, int $graceDays, int $lateFee): array
    {
        $stats = ['created' => 0, 'skipped' => 0, 'expired' => 0];

        $tenant = $subscription->tenant;

        if (! $tenant instanceof Tenant) {
            Log::warning('BillingRenewalService: langganan tanpa tenant, dilewati.', [
                'subscription_id' => $subscription->id,
            ]);

            return $stats;
        }

        // Jangan tagih tenant yang sudah dibatalkan; suspend ditangani command lain.
        if ($tenant->status === Tenant::STATUS_CANCELLED) {
            return $stats;
        }

        $periodEnd = $subscription->current_period_end;

        if (! $periodEnd instanceof \Illuminate\Support\Carbon) {
            return $stats;
        }

        // Sudah lewat tenggang → bukan urusan penagihan lagi, tapi suspend.
        if ($periodEnd->copy()->addDays($graceDays)->lte(now())) {
            return $stats;
        }

        // Di dalam masa tenggang = periode sudah lewat.
        $inGrace = $periodEnd->lte(now());
        $fee = $inGrace ? $lateFee : 0;
        $amount = $this->basePrice($subscription) + $fee;

        $live = $this->livePendingInvoice($subscription);

        // Sudah ada tagihan hidup dengan nominal yang benar → jangan dobel.
        if ($live !== null && (int) $live->amount === $amount) {
            $stats['skipped']++;

            return $stats;
        }

        // Nominal berbeda (mis. naik ke fase berdenda): tautan lama harus mati
        // DULU, karena mengubah amount invoice berlink hidup akan membuat
        // callback ditolak saat user membayar.
        if ($live !== null) {
            $live->forceFill(['status' => SubscriptionInvoice::STATUS_EXPIRED])->save();
            $stats['expired']++;
        }

        $this->expireStalePending($subscription, $live, $stats);

        if ($this->hasRenewalForPeriod($subscription, $periodEnd)) {
            $stats['skipped']++;

            return $stats;
        }

        $created = $this->createRenewalInvoice($subscription, $periodEnd, $amount, $fee);
        $stats['created']++;

        // B6: notifikasi saat invoice terbit — atau peringatan kalau sudah
        // masuk masa tenggang (denda sudah muncul).
        $this->notifyOnce(
            $created,
            $inGrace
                ? \App\Jobs\SendTenantNotificationJob::EVENT_PAYMENT_REMINDER
                : \App\Jobs\SendTenantNotificationJob::EVENT_RENEWAL_INVOICE,
            $inGrace ? 'payment_reminder' : 'renewal_invoice',
        );

        return $stats;
    }

    /**
     * Kirim notifikasi TEPAT SEKALI per invoice per event.
     *
     * Penagihan jalan tiap hari, jadi tanpa kunci ini tenant akan menerima
     * pesan yang sama berulang kali. Penanda disimpan SEBELUM dispatch supaya
     * percobaan ulang queue tidak mengirim dobel (lebih baik satu pesan hilang
     * daripada tenant dihujani pesan).
     */
    private function notifyOnce(SubscriptionInvoice $invoice, string $event, string $key): void
    {
        $metadata = $invoice->metadata ?: [];

        if (isset($metadata['notified'][$key])) {
            return;
        }

        $metadata['notified'][$key] = now()->toIso8601String();
        $invoice->forceFill(['metadata' => $metadata])->save();

        try {
            \App\Jobs\SendTenantNotificationJob::dispatch($invoice->id, $event);
        } catch (\Throwable $e) {
            // Notifikasi tidak boleh menggagalkan penagihan.
            Log::warning('BillingRenewalService: gagal menjadwalkan notifikasi.', [
                'invoice_id' => $invoice->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Suspend tenant yang sudah lewat masa tenggang tanpa pembayaran.
     *
     * @return array{suspended:int, skipped:int}
     */
    public function suspendOverdue(): array
    {
        $graceDays = (int) config('billing.grace_days', 3);
        $cutoff = now()->subDays($graceDays);

        $stats = ['suspended' => 0, 'skipped' => 0];

        Subscription::query()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', $cutoff)
            ->with('tenant')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$stats): void {
                foreach ($subscriptions as $subscription) {
                    $tenant = $subscription->tenant;

                    if (! $tenant instanceof Tenant) {
                        continue;
                    }

                    // Hanya tenant aktif yang disuspend. Yang sudah suspended
                    // atau dibatalkan dilewati → idempotent.
                    if ($tenant->status !== Tenant::STATUS_ACTIVE) {
                        $stats['skipped']++;

                        continue;
                    }

                    // Sudah membayar untuk periode yang lewat → jangan suspend.
                    if ($this->paidSince($subscription, $subscription->current_period_end)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $tenant->forceFill(['status' => Tenant::STATUS_SUSPENDED])->save();

                    // B3/B4: HANYA status. Data tenant dan subdomain tidak disentuh.
                    $this->recordEvent($subscription, 'suspend', [
                        'action' => 'suspend',
                        'reason' => 'overdue_beyond_grace',
                        'grace_days' => (int) config('billing.grace_days', 3),
                        'period_end' => $subscription->current_period_end?->toIso8601String(),
                    ]);

                    // B6: kabari tenant bahwa website-nya ditangguhkan.
                    $terakhir = SubscriptionInvoice::query()
                        ->where('subscription_id', $subscription->id)
                        ->latest('id')
                        ->first();

                    if ($terakhir !== null) {
                        $this->notifyOnce(
                            $terakhir,
                            \App\Jobs\SendTenantNotificationJob::EVENT_SUSPENDED,
                            'suspended',
                        );
                    }

                    $stats['suspended']++;
                }
            });

        return $stats;
    }

    private function basePrice(Subscription $subscription): int
    {
        $price = (int) $subscription->price;

        if ($price > 0) {
            return $price;
        }

        // B5: harga tier SEKARANG, bukan harga saat daftar.
        return (int) (TenantRegistrationService::TIER_PRICES[(string) $subscription->tier] ?? 0);
    }

    /** Invoice pending yang tautannya masih hidup. */
    private function livePendingInvoice(Subscription $subscription): ?SubscriptionInvoice
    {
        return SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', SubscriptionInvoice::STATUS_PENDING)
            ->where(function ($query): void {
                $query->whereNull('due_date')->orWhere('due_date', '>', now());
            })
            ->latest('id')
            ->first();
    }

    /** Invoice pending yang tautannya sudah mati → tak ada gunanya dibiarkan hidup. */
    private function expireStalePending(Subscription $subscription, ?SubscriptionInvoice $sudah, array &$stats): void
    {
        SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', SubscriptionInvoice::STATUS_PENDING)
            ->when($sudah !== null, fn ($query) => $query->whereKeyNot($sudah->id))
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now())
            ->update(['status' => SubscriptionInvoice::STATUS_EXPIRED]);

        $stats['expired'] += 0;
    }

    /** Sudah ada tagihan perpanjangan untuk periode ini? (kunci idempotensi) */
    private function hasRenewalForPeriod(Subscription $subscription, \Illuminate\Support\Carbon $periodEnd): bool
    {
        return SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('metadata->kind', self::KIND_RENEWAL)
            ->where('metadata->period_end', $periodEnd->toIso8601String())
            ->whereIn('status', [SubscriptionInvoice::STATUS_PENDING, SubscriptionInvoice::STATUS_PAID])
            ->exists();
    }

    private function paidSince(Subscription $subscription, ?\Illuminate\Support\Carbon $sejak): bool
    {
        if (! $sejak instanceof \Illuminate\Support\Carbon) {
            return false;
        }

        return SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', SubscriptionInvoice::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $sejak)
            ->exists();
    }

    private function createRenewalInvoice(
        Subscription $subscription,
        \Illuminate\Support\Carbon $periodEnd,
        int $amount,
        int $fee,
    ): SubscriptionInvoice {
        $invoice = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            // Denda flat: satu angka, tidak bertambah per hari.
            'amount' => $amount,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => $amount > 0 ? 'duitku' : 'manual',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDay(),
            'metadata' => [
                'source' => 'billing_renewal',
                'kind' => self::KIND_RENEWAL,
                'period_end' => $periodEnd->toIso8601String(),
                'late_fee' => $fee,
                'base_amount' => $amount - $fee,
                'currency' => 'IDR',
                'store_name' => (string) $subscription->tenant?->name,
                'subdomain' => (string) $subscription->tenant?->subdomain,
            ],
        ]);

        if ($amount > 0) {
            try {
                $invoice = $this->duitku->createAndStoreInvoice($invoice);
            } catch (\Throwable $e) {
                Log::warning('BillingRenewalService: gagal membuat tautan Duitku untuk perpanjangan.', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $invoice;
    }

    private function recordEvent(Subscription $subscription, string $type, array $meta): void
    {
        $invoice = SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->latest('id')
            ->first();

        if ($invoice === null) {
            return;
        }

        SubscriptionInvoiceEvent::record(
            $invoice,
            $type,
            $invoice->status,
            (string) $invoice->gateway_ref,
            null,
            $meta,
        );
    }
}

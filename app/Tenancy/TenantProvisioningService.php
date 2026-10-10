<?php

namespace App\Tenancy;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantProvisioningService
{
    public function markInvoicePaid(SubscriptionInvoice $invoice, ?string $gatewayRef = null, array $metadataMerge = []): SubscriptionInvoice
    {
        return DB::transaction(function () use ($invoice, $gatewayRef, $metadataMerge): SubscriptionInvoice {
            $lockedInvoice = SubscriptionInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $subscription = Subscription::query()
                ->whereKey($lockedInvoice->subscription_id)
                ->lockForUpdate()
                ->firstOrFail();

            $tenant = Tenant::query()
                ->whereKey($subscription->tenant_id)
                ->lockForUpdate()
                ->firstOrFail();

            $invoiceMetadata = array_replace_recursive($lockedInvoice->metadata ?: [], $metadataMerge);
            $shouldNotifyActivated = false;

            if ($lockedInvoice->status !== SubscriptionInvoice::STATUS_PAID) {
                $invoiceMetadata['notifications']['activated_sent_at'] = now()->toIso8601String();
                $shouldNotifyActivated = true;

                $lockedInvoice->forceFill([
                    'status' => SubscriptionInvoice::STATUS_PAID,
                    'gateway_ref' => $gatewayRef ?: $lockedInvoice->gateway_ref,
                    'paid_at' => $lockedInvoice->paid_at ?: now(),
                    'metadata' => $invoiceMetadata,
                ])->save();
            } elseif ($metadataMerge !== []) {
                $lockedInvoice->forceFill([
                    'metadata' => $invoiceMetadata,
                ])->save();
            }

            $periodStart = $subscription->current_period_start ?: now();

            // Periode maju SATU KALI per invoice yang baru saja jadi `paid`.
            // `$shouldNotifyActivated` hanya true saat invoice ini benar-benar
            // berpindah ke paid, jadi callback Duitku yang datang dua kali tidak
            // akan menumpuk bulan.
            if ($shouldNotifyActivated) {
                $basis = ($subscription->current_period_end && $subscription->current_period_end->isFuture())
                    ? $subscription->current_period_end   // masih berjalan → tambah penuh dari akhir periode
                    : ($subscription->current_period_end ?: now()); // sudah lewat/kosong → dari akhir periode lama

                $subscription->forceFill([
                    'current_period_end' => $basis->copy()->addMonth(),
                ])->save();
            }

            $subscription->forceFill([
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_start' => $periodStart,
                'gateway_ref' => $gatewayRef ?: $subscription->gateway_ref,
            ])->save();

            // Periode sudah dibayar → tagihan lain untuk periode yang sama tidak
            // boleh ikut ditagih (bisa jadi invoice berdenda/sisa percobaan bayar).
            $this->cancelTwinInvoices($lockedInvoice);

            $tenant->forceFill([
                'status' => Tenant::STATUS_ACTIVE,
                'margin_config' => $tenant->margin_config ?: app(TenantRegistrationService::class)->defaultMarginConfig(),
                'theme' => $tenant->theme ?: app(TenantRegistrationService::class)->defaultTheme(),
            ])->save();

            if ($tenant->owner && $tenant->owner->role !== 'Admin') {
                $tenant->owner->forceFill([
                    'role' => 'Gold',
                ])->save();
            }

            if ($shouldNotifyActivated) {
                DB::afterCommit(function () use ($lockedInvoice) {
                    \App\Jobs\SendTenantNotificationJob::dispatch(
                        $lockedInvoice->id,
                        \App\Jobs\SendTenantNotificationJob::EVENT_ACTIVATED
                    );
                });
            }

            return $lockedInvoice->fresh(['subscription.tenant.owner']);
        });
    }

    /**
     * Batalkan tagihan pending lain untuk langganan yang sama saat satu invoice
     * sudah dibayar, supaya user tidak ditagih dua kali untuk periode yang sama.
     */
    private function cancelTwinInvoices(SubscriptionInvoice $paid): void
    {
        $periodeLunas = data_get($paid->metadata, 'period_end');

        $query = SubscriptionInvoice::query()
            ->where('subscription_id', $paid->subscription_id)
            ->whereKeyNot($paid->id)
            ->where('status', SubscriptionInvoice::STATUS_PENDING);

        // Kalau periode tagihan diketahui, batalkan hanya untuk periode itu —
        // jangan sentuh tagihan periode lain yang mungkin sudah terbit.
        if (filled($periodeLunas)) {
            $query->where('metadata->period_end', $periodeLunas);
        }

        $query->update(['status' => SubscriptionInvoice::STATUS_CANCELLED]);
    }

    public function markInvoiceExpired(SubscriptionInvoice $invoice, array $metadataMerge = []): SubscriptionInvoice
    {
        return DB::transaction(function () use ($invoice, $metadataMerge): SubscriptionInvoice {
            $lockedInvoice = SubscriptionInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $invoiceMetadata = array_replace_recursive($lockedInvoice->metadata ?: [], $metadataMerge);
            $shouldNotifyExpired = false;

            if ($lockedInvoice->status === SubscriptionInvoice::STATUS_PENDING) {
                $invoiceMetadata['notifications']['expired_sent_at'] = now()->toIso8601String();
                $shouldNotifyExpired = true;

                $lockedInvoice->forceFill([
                    'status' => SubscriptionInvoice::STATUS_EXPIRED,
                    'metadata' => $invoiceMetadata,
                ])->save();
            } elseif ($metadataMerge !== []) {
                $lockedInvoice->forceFill([
                    'metadata' => $invoiceMetadata,
                ])->save();
            }

            if ($shouldNotifyExpired) {
                DB::afterCommit(function () use ($lockedInvoice) {
                    \App\Jobs\SendTenantNotificationJob::dispatch(
                        $lockedInvoice->id,
                        \App\Jobs\SendTenantNotificationJob::EVENT_INVOICE_EXPIRED
                    );
                });
            }

            return $lockedInvoice->fresh(['subscription.tenant.owner']);
        });
    }
}

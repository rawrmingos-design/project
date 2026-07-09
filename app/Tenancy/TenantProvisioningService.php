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
            $periodEnd = $subscription->current_period_end;

            if (! $periodEnd || $periodEnd->lte(now())) {
                $periodEnd = now()->addMonth();
            }

            $subscription->forceFill([
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'gateway_ref' => $gatewayRef ?: $subscription->gateway_ref,
            ])->save();

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

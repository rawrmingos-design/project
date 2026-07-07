<?php

namespace App\Tenancy;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantProvisioningService
{
    public function markInvoicePaid(SubscriptionInvoice $invoice, ?string $gatewayRef = null): SubscriptionInvoice
    {
        return DB::transaction(function () use ($invoice, $gatewayRef): SubscriptionInvoice {
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

            if ($lockedInvoice->status !== SubscriptionInvoice::STATUS_PAID) {
                $lockedInvoice->forceFill([
                    'status' => SubscriptionInvoice::STATUS_PAID,
                    'gateway_ref' => $gatewayRef ?: $lockedInvoice->gateway_ref,
                    'paid_at' => $lockedInvoice->paid_at ?: now(),
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

            return $lockedInvoice->fresh(['subscription.tenant.owner']);
        });
    }
}

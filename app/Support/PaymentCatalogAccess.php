<?php

namespace App\Support;

use App\Tenancy\TenantContext;

/**
 * Centralizes master/tenant detection for payment catalog access control.
 *
 * Master/main admin == no active TenantContext (tenant_id = null).
 * Non-master tenant == TenantContext is active with an actual tenant.
 *
 * All payment catalog Resource, controller, and service authorization
 * must go through this class so the rule is enforced consistently.
 */
class PaymentCatalogAccess
{
    /**
     * Returns true when the current request runs in the main/master context
     * (no tenant scoping active). Only master may create, edit, or delete
     * canonical payment categories and methods.
     */
    public static function isMaster(): bool
    {
        if (! app()->bound(TenantContext::class)) {
            return true;
        }

        return ! app(TenantContext::class)->has();
    }

    /**
     * Returns the active tenant ID, or null when running as master.
     */
    public static function currentTenantId(): ?int
    {
        if (! app()->bound(TenantContext::class)) {
            return null;
        }

        return app(TenantContext::class)->id();
    }
}

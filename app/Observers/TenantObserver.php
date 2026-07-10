<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Services\PaymentDisplayCategoryService;

class TenantObserver
{
    public function __construct(
        private PaymentDisplayCategoryService $categoryService,
    ) {}

    /**
     * Handle the Tenant "created" event.
     *
     * Provisions default payment display categories for the new tenant.
     */
    public function created(Tenant $tenant): void
    {
        $this->categoryService->provisionDefaultsForTenant($tenant);
    }
}

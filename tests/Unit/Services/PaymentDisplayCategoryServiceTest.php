<?php

namespace Tests\Unit\Services;

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Models\TenantPaymentDisplayCategorySetting;
use App\Services\PaymentDisplayCategoryService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase; // Must use Laravel's TestCase for DB tests

class PaymentDisplayCategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_categories_applies_tenant_override()
    {
        $tenant = Tenant::create(['name' => 'T1', 'subdomain' => 't1', 'status' => 'active']);
        $cat1 = PaymentDisplayCategory::create(['label' => 'Cat 1', 'is_visible' => true]);
        $cat2 = PaymentDisplayCategory::create(['label' => 'Cat 2', 'is_visible' => true]);

        Method::create(['payment_display_category_id' => $cat1->id, 'code' => 'M1', 'name' => 'M1', 'images' => 'x', 'keterangan' => 'x', 'tipe' => 'qris', 'payment' => 'manual', 'statuspayment' => true]);
        Method::create(['payment_display_category_id' => $cat2->id, 'code' => 'M2', 'name' => 'M2', 'images' => 'x', 'keterangan' => 'x', 'tipe' => 'qris', 'payment' => 'manual', 'statuspayment' => true]);

        TenantPaymentDisplayCategorySetting::create([
            'tenant_id' => $tenant->id,
            'payment_display_category_id' => $cat2->id,
            'is_visible' => false,
        ]);

        app(TenantContext::class)->set($tenant);
        $service = app(PaymentDisplayCategoryService::class);
        $categories = $service->getCategoriesForOrderPage();

        $labels = $categories->pluck('label')->toArray();
        $this->assertContains('Cat 1', $labels);
        $this->assertNotContains('Cat 2', $labels);
    }
}

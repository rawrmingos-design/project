<?php

namespace Tests\Feature;

use App\Models\Method;
use App\Models\Tenant;
use App\Models\TenantPaymentMethodSetting;
use App\Services\PaymentMethodCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCatalogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_catalog_returns_all_enabled_methods()
    {
        Method::create(['code' => 'M_ACT', 'name' => 'M1', 'images' => 'x', 'keterangan' => 'x', 'tipe' => 'qris', 'payment' => 'manual', 'statuspayment' => true]);
        Method::create(['code' => 'M_DIS', 'name' => 'M2', 'images' => 'x', 'keterangan' => 'x', 'tipe' => 'qris', 'payment' => 'manual', 'statuspayment' => false]);
        Method::create(['code' => 'M_NULL', 'name' => 'M3', 'images' => 'x', 'keterangan' => 'x', 'tipe' => 'qris', 'payment' => 'manual', 'statuspayment' => null]);

        $service = app(PaymentMethodCatalogService::class);
        $methods = $service->getVisibleMethods(null);

        $codes = $methods->pluck('code')->toArray();
        $this->assertContains('M_ACT', $codes);
        $this->assertContains('M_NULL', $codes);
        $this->assertNotContains('M_DIS', $codes);
    }

    public function test_tenant_visibility_override_hides_method()
    {
        $tenant = Tenant::create(['name' => 'T1', 'subdomain' => 't1', 'status' => 'active']);
        Method::create(['code' => 'M_ACT_1', 'name' => 'M1', 'images' => 'x', 'keterangan' => 'x', 'tipe' => 'qris', 'payment' => 'manual', 'statuspayment' => true]);
        $method2 = Method::create(['code' => 'M_ACT_2', 'name' => 'M2', 'images' => 'x', 'keterangan' => 'x', 'tipe' => 'qris', 'payment' => 'manual', 'statuspayment' => true]);

        TenantPaymentMethodSetting::create([
            'tenant_id' => $tenant->id,
            'method_id' => $method2->id,
            'is_visible' => false,
        ]);

        $service = app(PaymentMethodCatalogService::class);
        $methods = $service->getVisibleMethods($tenant->id);

        $codes = $methods->pluck('code')->toArray();
        $this->assertContains('M_ACT_1', $codes);
        $this->assertNotContains('M_ACT_2', $codes);
    }

    public function test_tenant_visibility_cannot_override_global_disabled_method()
    {
        $tenant = Tenant::create(['name' => 'T1', 'subdomain' => 't1', 'status' => 'active']);
        $method = Method::create(['code' => 'M_DIS_1', 'name' => 'M1', 'images' => 'x', 'keterangan' => 'x', 'tipe' => 'qris', 'payment' => 'manual', 'statuspayment' => false]);

        TenantPaymentMethodSetting::create([
            'tenant_id' => $tenant->id,
            'method_id' => $method->id,
            'is_visible' => true,
        ]);

        $service = app(PaymentMethodCatalogService::class);
        $methods = $service->getVisibleMethods($tenant->id);

        $this->assertNotContains('M_DIS_1', $methods->pluck('code')->toArray());
    }

    public function test_tenant_setting_does_not_affect_other_tenants()
    {
        $tenantA = Tenant::create(['name' => 'T1', 'subdomain' => 't1', 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'T2', 'subdomain' => 't2', 'status' => 'active']);
        $method = Method::create(['code' => 'M_SHARED', 'name' => 'M1', 'images' => 'x', 'keterangan' => 'x', 'tipe' => 'qris', 'payment' => 'manual', 'statuspayment' => true]);

        TenantPaymentMethodSetting::create([
            'tenant_id' => $tenantA->id,
            'method_id' => $method->id,
            'is_visible' => false,
        ]);

        $service = app(PaymentMethodCatalogService::class);

        $methodsA = $service->getVisibleMethods($tenantA->id);
        $this->assertNotContains('M_SHARED', $methodsA->pluck('code')->toArray());

        $methodsB = $service->getVisibleMethods($tenantB->id);
        $this->assertContains('M_SHARED', $methodsB->pluck('code')->toArray());
    }
}

<?php

namespace Tests\Feature\PaymentDisplayCategories;

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainSiteEquivalenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_site_storefront_displays_global_payment_methods(): void
    {
        // 1. Create a global category and method (tenant_id = null)
        $category = PaymentDisplayCategory::create([
            'label' => 'Main Site Wallets',
            'display_style' => 'flat',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        Method::create([
            'payment_display_category_id' => $category->id,
            'name' => 'Main Site Dana',
            'code' => 'DANA_MAIN',
            'payment' => 'manual',
            'tipe' => 'e-wallet',
            'images' => 'dana.png',
            'keterangan' => 'Dana Method',
            'statuspayment' => 1,
        ]);

        // 2. Fetch the categories for the order page directly via service (simulating main site request with no TenantContext)
        $service = app(\App\Services\PaymentDisplayCategoryService::class);
        $categories = $service->getCategoriesForOrderPage();

        // 3. Assert that the collection is NOT empty and contains our global method
        $this->assertNotEmpty($categories);
        $this->assertEquals('Main Site Wallets', $categories->first()->label);
        $this->assertCount(1, $categories->first()->methods);
        $this->assertEquals('Main Site Dana', $categories->first()->methods->first()->name);
    }
}

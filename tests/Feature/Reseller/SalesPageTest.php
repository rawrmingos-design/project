<?php

namespace Tests\Feature\Reseller;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SalesPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Anyone (guest or authenticated) can view reseller sales page.
     */
    public function test_anyone_can_view_sales_page(): void
    {
        $response = $this->get('/id/reseller');

        $response->assertStatus(200);
    }

    /**
     * Sales page renders correct Inertia component.
     */
    public function test_sales_page_renders_correct_component(): void
    {
        $response = $this->get('/id/reseller');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/SalesPage', false)
        );
    }

    /**
     * Sales page has CTA configuration with registry URL.
     */
    public function test_sales_page_has_cta_to_registry(): void
    {
        $response = $this->get('/id/reseller');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/SalesPage', false)
            ->has('ctaConfig')
            ->has('ctaConfig.primaryUrl')
            ->has('ctaConfig.docsUrl')
        );
    }

    /**
     * Sales page includes product data for display.
     */
    public function test_sales_page_includes_product_data(): void
    {
        $response = $this->get('/id/reseller');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/SalesPage', false)
            ->has('products')
            ->has('products.data')
            ->has('products.meta')
            ->has('allProducts')
        );
    }

    /**
     * Sales page includes SEO metadata.
     */
    public function test_sales_page_includes_seo_metadata(): void
    {
        $response = $this->get('/id/reseller');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/SalesPage', false)
            ->has('seoMeta')
            ->has('seoMeta.title')
            ->has('seoMeta.description')
        );
    }
}

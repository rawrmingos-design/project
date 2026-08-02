<?php

namespace Tests\Feature\Reseller;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SalesPageTest extends TestCase
{
    use RefreshDatabase;

    /** @var string|false */
    private string|false $savedDocsDomain;

    protected function tearDown(): void
    {
        if (isset($this->savedDocsDomain)) {
            if ($this->savedDocsDomain === false) {
                putenv('DOCS_DOMAIN');
                unset($_ENV['DOCS_DOMAIN'], $_SERVER['DOCS_DOMAIN']);
            } else {
                putenv("DOCS_DOMAIN={$this->savedDocsDomain}");
                $_ENV['DOCS_DOMAIN'] = $this->savedDocsDomain;
                $_SERVER['DOCS_DOMAIN'] = $this->savedDocsDomain;
            }

            Env::enablePutenv();
        }

        parent::tearDown();
    }

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
    public function test_sales_page_uses_the_canonical_docs_domain_for_its_cta(): void
    {
        $this->setDocsDomain('docs.example.test');

        $response = $this->get('/id/reseller');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reseller/SalesPage', false)
            ->where('ctaConfig.primaryUrl', route('reseller.registry.form'))
            ->where('ctaConfig.docsUrl', 'https://docs.example.test')
        );
    }

    public function test_sales_page_omits_docs_cta_url_when_docs_domain_is_missing(): void
    {
        $this->setDocsDomain(null);

        $this->get('/id/reseller')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reseller/SalesPage', false)
                ->where('ctaConfig.docsUrl', null)
            );
    }

    /**
     * Sales page includes product data for display.
     */
    private function setDocsDomain(?string $value): void
    {
        if (! isset($this->savedDocsDomain)) {
            $this->savedDocsDomain = getenv('DOCS_DOMAIN');
        }

        if ($value === null) {
            putenv('DOCS_DOMAIN');
            unset($_ENV['DOCS_DOMAIN'], $_SERVER['DOCS_DOMAIN']);
            Env::enablePutenv();

            return;
        }

        putenv("DOCS_DOMAIN={$value}");
        $_ENV['DOCS_DOMAIN'] = $value;
        $_SERVER['DOCS_DOMAIN'] = $value;
        Env::enablePutenv();
    }

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

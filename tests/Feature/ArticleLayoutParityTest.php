<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArticleLayoutParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->createSettings('bangjeff');
    }

    public function test_default_article_exposes_validated_layout_colors_and_sanitized_content(): void
    {
        $article = $this->createArticle([
            'slug' => 'default-layout',
            'layout' => 'default',
            'primary_color' => '#F70',
            'secondary_color' => '#102030',
            'content' => '<p>Konten <strong>aman</strong>.</p><script>alert(1)</script><a href="javascript:alert(2)">Bahaya</a>',
        ]);

        $this->get("/id/artikel/{$article->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Articles/Show')
                ->where('article.layout', 'default')
                ->where('article.primaryColor', '#f70')
                ->where('article.secondaryColor', '#102030')
                ->where('article.content', fn (string $content) => str_contains($content, '<strong>aman</strong>')
                    && ! str_contains($content, '<script')
                    && ! str_contains(strtolower($content), 'javascript:'))
            );
    }

    public function test_modern_article_exposes_the_modern_layout_and_colors(): void
    {
        $article = $this->createArticle([
            'slug' => 'modern-layout',
            'layout' => 'modern',
            'primary_color' => '#ABCDEF',
            'secondary_color' => '#123',
        ]);

        $this->get("/id/artikel/{$article->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Articles/Show')
                ->where('article.layout', 'modern')
                ->where('article.primaryColor', '#abcdef')
                ->where('article.secondaryColor', '#123')
            );
    }

    public function test_unknown_layout_and_malformed_colors_use_safe_fallback_props(): void
    {
        $article = $this->createArticle([
            'slug' => 'fallback-layout',
            'layout' => 'hero<script>',
            'primary_color' => 'red',
            'secondary_color' => '#12345g',
        ]);

        $this->get("/id/artikel/{$article->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('article.layout', 'default')
                ->where('article.primaryColor', null)
                ->where('article.secondaryColor', null)
            );
    }

    public function test_default_public_theme_continues_to_use_the_legacy_article_view(): void
    {
        SettingWeb::query()->whereKey(1)->update(['public_theme' => 'default']);
        $article = $this->createArticle(['slug' => 'legacy-layout']);

        $this->get("/id/artikel/{$article->slug}")
            ->assertOk()
            ->assertViewIs('template.id.artikel.show-default');
    }

    private function createArticle(array $overrides = []): Artikel
    {
        return Artikel::query()->create(array_merge([
            'title' => 'Artikel Layout Parity',
            'slug' => 'artikel-layout-parity',
            'thumbnail' => 'assets/articles/article.webp',
            'content' => '<p>Konten artikel.</p>',
            'meta_description' => 'Deskripsi artikel.',
            'keywords' => 'game,promo',
            'layout' => 'default',
            'status' => 'active',
            'views' => 0,
        ], $overrides));
    }

    private function createSettings(string $theme): void
    {
        SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Demo storefront',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/testweb',
            'url_tiktok' => 'https://tiktok.com/@testweb',
            'url_youtube' => 'https://youtube.com/@testweb',
            'url_fb' => 'https://facebook.com/testweb',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'TST',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
            'public_theme' => $theme,
        ]);
    }
}

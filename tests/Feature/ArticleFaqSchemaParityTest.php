<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FAQPage structured-data parity between the legacy Blade theme and the
 * Inertia public themes (istanatopup / bangjeff).
 */
class ArticleFaqSchemaParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_istanatopup_article_renders_faqpage_schema_from_content(): void
    {
        $this->createSettings('istanatopup');
        $article = $this->createArticle([
            'slug' => 'faq-parity-istana',
            'content' => $this->faqContent(),
        ]);

        $content = $this->get("/id/artikel/{$article->slug}")->assertOk()->getContent();

        $schemas = $this->schemas($content);
        $types = $this->types($schemas);

        $this->assertContains('Article', $types);
        $this->assertContains('BreadcrumbList', $types);
        $this->assertContains('FAQPage', $types);

        $faq = $this->findType($schemas, 'FAQPage');
        $this->assertNotNull($faq);
        $this->assertCount(2, $faq['mainEntity']);
        $this->assertSame('Apa itu top up?', $faq['mainEntity'][0]['name']);
        $this->assertSame('Top up adalah pengisian ulang.', $faq['mainEntity'][0]['acceptedAnswer']['text']);
        $this->assertSame('Berapa lama prosesnya?', $faq['mainEntity'][1]['name']);
        $this->assertSame('Proses instan 1-3 menit.', $faq['mainEntity'][1]['acceptedAnswer']['text']);
    }

    public function test_legacy_blade_article_renders_the_same_faqpage_schema(): void
    {
        $this->createSettings('default');
        $article = $this->createArticle([
            'slug' => 'faq-parity-legacy',
            'content' => $this->faqContent(),
        ]);

        $content = $this->get("/id/artikel/{$article->slug}")->assertOk()->getContent();

        $faq = $this->findType($this->schemas($content), 'FAQPage');

        $this->assertNotNull($faq);
        $this->assertCount(2, $faq['mainEntity']);
        $this->assertSame('Apa itu top up?', $faq['mainEntity'][0]['name']);
        $this->assertSame('Proses instan 1-3 menit.', $faq['mainEntity'][1]['acceptedAnswer']['text']);
    }

    public function test_article_without_faq_heading_omits_faqpage_schema(): void
    {
        $this->createSettings('istanatopup');
        $article = $this->createArticle([
            'slug' => 'no-faq-istana',
            'content' => '<h2>Tips bermain</h2><p>Main dengan tenang.</p>',
        ]);

        $content = $this->get("/id/artikel/{$article->slug}")->assertOk()->getContent();
        $schemas = $this->schemas($content);

        $this->assertContains('Article', $this->types($schemas));
        $this->assertNotContains('FAQPage', $this->types($schemas));
    }

    public function test_unsafe_markup_in_faq_content_is_sanitized_out_of_schema(): void
    {
        $this->createSettings('istanatopup');
        $article = $this->createArticle([
            'slug' => 'faq-sanitize-istana',
            'content' => '<h2>FAQ</h2><h3>Apakah aman?</h3><p>Aman <script>alert(1)</script> terkendali.</p>',
        ]);

        $content = $this->get("/id/artikel/{$article->slug}")->assertOk()->getContent();

        $this->assertStringNotContainsString('alert(1)', $content);
        $faq = $this->findType($this->schemas($content), 'FAQPage');
        $this->assertNotNull($faq);
        $this->assertStringNotContainsString('<script', $faq['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_article_page_still_exposes_a_single_json_ld_script(): void
    {
        $this->createSettings('istanatopup');
        $article = $this->createArticle([
            'slug' => 'single-jsonld-istana',
            'content' => $this->faqContent(),
        ]);

        $content = $this->get("/id/artikel/{$article->slug}")->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, 'application/ld+json'));
    }

    private function faqContent(): string
    {
        return '<h2>Pertanyaan Umum (FAQ)</h2>'
            . '<h3>Apa itu top up?</h3><p>Top up adalah pengisian ulang.</p>'
            . '<h3>Berapa lama prosesnya?</h3><p>Proses instan 1-3 menit.</p>'
            . '<h2>Penutup</h2><p>Terima kasih.</p>';
    }

    /** @return array<int, array<string, mixed>> */
    private function schemas(string $content): array
    {
        preg_match_all('/<script\b[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $content, $matches);

        $schemas = [];
        foreach ($matches[1] ?? [] as $raw) {
            $decoded = json_decode(trim($raw), true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON-LD harus valid JSON');
            foreach (is_array($decoded) && array_is_list($decoded) ? $decoded : [$decoded] as $item) {
                if (is_array($item)) {
                    $schemas[] = $item;
                }
            }
        }

        return $schemas;
    }

    /** @param array<int, array<string, mixed>> $schemas */
    private function types(array $schemas): array
    {
        return array_values(array_filter(array_map(static fn (array $item) => $item['@type'] ?? null, $schemas)));
    }

    /** @param array<int, array<string, mixed>> $schemas */
    private function findType(array $schemas, string $type): ?array
    {
        foreach ($schemas as $item) {
            if (($item['@type'] ?? null) === $type) {
                return $item;
            }
        }

        return null;
    }

    private function createArticle(array $overrides = []): Artikel
    {
        return Artikel::query()->create(array_merge([
            'title' => 'FAQ Parity Artikel',
            'slug' => 'faq-parity-artikel',
            'thumbnail' => 'assets/articles/article.webp',
            'content' => '<p>Konten artikel.</p>',
            'meta_description' => 'Deskripsi artikel FAQ.',
            'keywords' => 'faq,top up',
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

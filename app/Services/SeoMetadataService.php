<?php

namespace App\Services;

use App\Helpers\HtmlSanitizer;
use App\Support\CanonicalUrl;
use App\Support\SeoRoutePolicy;
use Illuminate\Http\Request;

final class SeoMetadataService
{
    public function __construct(
        private readonly PublicSiteConfigService $siteConfigService,
    ) {
    }

    /**
     * Build the shared SEO contract consumed by every Inertia public theme.
     * Page-specific values override site defaults, while robots remain route-aware.
     */
    public function page(array $overrides = [], ?Request $request = null): array
    {
        $settings = $this->siteConfigService->getSettings();
        $description = HtmlSanitizer::toPlainText((string) ($settings->deskripsi_web ?? ''), 180);
        $image = $this->siteConfigService->normalizeAssetPath($settings->logo_favicon ?? null);
        $canonicalSource = $overrides['canonical'] ?? ($request?->url() ?? url()->current());
        $canonical = CanonicalUrl::normalize($canonicalSource);
        $page = (int) (($request?->query('page')) ?? 0);
        if ($page > 1 && ! str_contains($canonical, '?')) {
            $canonical .= '?page=' . $page;
        }

        return array_filter([
            'title' => trim((string) ($overrides['title'] ?? $settings->judul_web ?? config('app.name'))),
            'description' => trim((string) ($overrides['description'] ?? $description)),
            'keywords' => trim((string) ($overrides['keywords'] ?? ($settings->keywords ?? ''))),
            'canonical' => $canonical,
            'image' => $overrides['image'] ?? url($image),
            'robots' => $overrides['robots'] ?? SeoRoutePolicy::robots($request),
            'ogType' => $overrides['ogType'] ?? 'website',
            'author' => $overrides['author'] ?? ($settings->judul_web ?? config('app.name')),
            'ogTitle' => $overrides['ogTitle'] ?? null,
            'ogDescription' => $overrides['ogDescription'] ?? null,
            'imageAlt' => $overrides['imageAlt'] ?? null,
            'twitterCard' => $overrides['twitterCard'] ?? null,
            'schemaMarkup' => $overrides['schemaMarkup'] ?? null,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    public function homepage(?Request $request = null): array
    {
        $settings = $this->siteConfigService->getSettings();
        $siteName = (string) ($settings->judul_web ?? config('app.name'));
        $canonical = url('/id');

        return $this->page([
            'title' => $siteName,
            'description' => HtmlSanitizer::toPlainText((string) $settings->deskripsi_web, 180),
            'keywords' => $settings->keywords,
            'canonical' => $canonical,
            'ogType' => 'website',
            'schemaMarkup' => $this->homepageSchema($siteName, $canonical),
        ], $request);
    }

    public function homepageSchema(string $siteName, string $canonical, ?string $logo = null): array
    {
        $settings = $this->siteConfigService->getSettings();
        $logo ??= url($this->siteConfigService->normalizeAssetPath($settings->logo_header ?? null));
        $websiteId = rtrim($canonical, '/') . '#website';
        $organizationId = rtrim($canonical, '/') . '#organization';

        return [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'name' => $siteName,
                'url' => $canonical,
                'inLanguage' => 'id-ID',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => rtrim($canonical, '/') . '/search/products?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                '@id' => $organizationId,
                'name' => $siteName,
                'url' => $canonical,
                'logo' => ['@type' => 'ImageObject', 'url' => $logo],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                '@id' => $canonical . '#webpage',
                'url' => $canonical,
                'name' => $siteName,
                'isPartOf' => ['@id' => $websiteId],
                'about' => ['@id' => $organizationId],
                'inLanguage' => 'id-ID',
            ],
        ];
    }

    public function articleSchema(array $article, string $canonical, string $siteName, ?string $logo = null, ?string $content = null): array
    {
        $canonical = CanonicalUrl::normalize($canonical);
        $settings = $this->siteConfigService->getSettings();
        $logo ??= url($this->siteConfigService->normalizeAssetPath($settings->logo_header ?? null));
        $articleSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            '@id' => $canonical . '#article',
            'headline' => trim((string) ($article['title'] ?? '')),
            'description' => trim((string) ($article['description'] ?? '')),
            'image' => ! empty($article['image']) ? [$article['image']] : null,
            'author' => ['@type' => 'Organization', 'name' => $article['author'] ?? 'Tim Editorial'],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'logo' => ['@type' => 'ImageObject', 'url' => $logo],
            ],
            'datePublished' => $article['datePublished'] ?? null,
            'dateModified' => $article['dateModified'] ?? ($article['datePublished'] ?? null),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            'inLanguage' => 'id-ID',
        ], static fn ($value): bool => $value !== null && $value !== '');

        $schemas = [$articleSchema, $this->breadcrumbSchema([
            ['name' => $siteName, 'url' => url('/id')],
            ['name' => 'Artikel', 'url' => url('/id/artikel')],
            ['name' => $article['title'] ?? 'Artikel', 'url' => $canonical],
        ])];

        // Paritas dengan legacy Blade: bagian FAQ di dalam konten artikel
        // juga dipublikasikan sebagai FAQPage agar kaya rich result.
        $faq = $this->faqSchema($content, $canonical);
        if ($faq !== null) {
            $schemas[] = $faq;
        }

        return $schemas;
    }

    /**
     * Bangun schema FAQPage dari bagian "FAQ" di dalam konten artikel.
     *
     * Konvensi konten: sebuah <h2> yang memuat kata "faq", lalu pasangan
     * <h3>pertanyaan</h3> diikuti <p>jawaban</p>. Bagian berhenti saat
     * menemukan <h2> berikutnya. Mengembalikan null bila tidak ada FAQ,
     * sehingga halaman biasa tidak ikut mendapat schema FAQPage.
     */
    public function faqSchema(?string $content, ?string $canonical = null): ?array
    {
        $questions = $this->faqQuestions($content);

        if ($questions === []) {
            return null;
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => $canonical !== null ? CanonicalUrl::normalize($canonical) . '#faq' : null,
            'mainEntity' => $questions,
        ], static fn ($value): bool => $value !== null);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function faqQuestions(?string $content): array
    {
        $content = trim((string) $content);

        if ($content === '' || ! str_contains(strtolower($content), 'faq')) {
            return [];
        }

        $document = new \DOMDocument();
        $previousUseErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $content);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        $faqHeading = null;

        foreach ($document->getElementsByTagName('h2') as $heading) {
            $headingText = strtolower(trim((string) preg_replace('/\s+/', ' ', $heading->textContent)));

            if (str_contains($headingText, 'faq')) {
                $faqHeading = $heading;
                break;
            }
        }

        if ($faqHeading === null) {
            return [];
        }

        $questions = [];

        for ($node = $faqHeading->nextSibling; $node !== null; $node = $node->nextSibling) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $nodeName = strtolower($node->nodeName);

            if ($nodeName === 'h2') {
                break;
            }

            if ($nodeName !== 'h3') {
                continue;
            }

            $question = trim((string) preg_replace('/\s+/', ' ', $node->textContent));
            $answerNode = $node->nextSibling;

            while ($answerNode && $answerNode->nodeType !== XML_ELEMENT_NODE) {
                $answerNode = $answerNode->nextSibling;
            }

            if ($question === '' || $answerNode === null || strtolower($answerNode->nodeName) !== 'p') {
                continue;
            }

            $answer = trim((string) preg_replace('/\s+/', ' ', $answerNode->textContent));

            if ($answer === '') {
                continue;
            }

            $questions[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        return $questions;
    }

    public function collectionSchema(string $name, string $canonical, ?string $image = null): array
    {
        $canonical = CanonicalUrl::normalize($canonical);

        $settings = $this->siteConfigService->getSettings();
        $siteName = trim((string) ($settings->judul_web ?? config('app.name')));

        return [
            array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                '@id' => $canonical . '#collection',
                'name' => $name,
                'url' => $canonical,
                'image' => $image,
                'inLanguage' => 'id-ID',
            ], static fn ($value): bool => $value !== null && $value !== ''),
            $this->breadcrumbSchema([
                ['name' => $siteName, 'url' => url('/id')],
                ['name' => $name, 'url' => $canonical],
            ]),
        ];
    }

    private function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    public function privatePage(array $overrides = [], ?Request $request = null): array
    {
        return $this->page(array_merge([
            'robots' => SeoRoutePolicy::PRIVATE_ROBOTS,
        ], $overrides), $request);
    }

    public function article(array $overrides = [], ?Request $request = null): array
    {
        return $this->page(array_merge([
            'ogType' => 'article',
        ], $overrides), $request);
    }

    public function category(array $overrides = [], ?Request $request = null): array
    {
        return $this->page(array_merge([
            'ogType' => 'website',
        ], $overrides), $request);
    }

    public function calculator(string $name, array $overrides = [], ?Request $request = null): array
    {
        return $this->page(array_merge([
            'title' => $name,
            'ogType' => 'website',
        ], $overrides), $request);
    }
}

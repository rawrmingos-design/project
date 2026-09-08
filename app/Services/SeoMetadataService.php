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
        $canonical = CanonicalUrl::normalize($overrides['canonical'] ?? ($request?->url() ?? url()->current()));

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

        return $this->page([
            'title' => $settings->judul_web,
            'description' => HtmlSanitizer::toPlainText((string) $settings->deskripsi_web, 180),
            'keywords' => $settings->keywords,
            'canonical' => url('/id'),
            'ogType' => 'website',
        ], $request);
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


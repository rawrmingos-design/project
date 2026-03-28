<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
use App\Models\Kategori;
use App\Models\MediaAsset;
use App\Models\SettingWeb;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $settings = $this->getSeoSettings();

        if (! $settings['robots_enabled']) {
            return response("User-agent: *\nDisallow: /\n", 200)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /livewire',
            'Disallow: /callback',
            'Disallow: /wejizy',
            'Disallow: /cronjob',
            'Disallow: /ipay88',
        ];

        $customLines = preg_split('/\R+/', (string) $settings['robots_custom_lines']) ?: [];
        foreach ($customLines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $lines[] = $line;
        }

        if ($settings['sitemap_enabled']) {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . url('/sitemap.xml');
        }

        return response(implode("\n", $lines) . "\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $settings = $this->getSeoSettings();
        $cacheMinutes = $settings['sitemap_cache_minutes'];

        $xml = Cache::remember('seo:sitemap:index:v3', now()->addMinutes($cacheMinutes), function () use ($settings): string {
            if (! $settings['sitemap_enabled']) {
                return $this->buildSitemapIndexXml([]);
            }

            if ($settings['sitemap_mode'] === 'custom_upload') {
                $custom = $this->readCustomSitemapXmlFromAssetId($settings['sitemap_index_asset_id']);

                if ($custom !== null) {
                    return $custom;
                }
            }

            $entries = [
                [
                    'loc' => url('/sitemap-main.xml'),
                    'lastmod' => now()->toAtomString(),
                ],
            ];

            if ($settings['sitemap_include_categories']) {
                $entries[] = [
                    'loc' => url('/sitemap-categories.xml'),
                    'lastmod' => now()->toAtomString(),
                ];
            }

            return $this->buildSitemapIndexXml($entries);
        });

        return $this->xmlResponse($xml);
    }

    public function sitemapMain(): Response
    {
        $settings = $this->getSeoSettings();
        $cacheMinutes = $settings['sitemap_cache_minutes'];

        $xml = Cache::remember('seo:sitemap:main:v3', now()->addMinutes($cacheMinutes), function () use ($settings): string {
            if (! $settings['sitemap_enabled']) {
                return $this->buildUrlSetXml([]);
            }

            if ($settings['sitemap_mode'] === 'custom_upload') {
                $custom = $this->readCustomSitemapXmlFromAssetId($settings['sitemap_main_asset_id']);

                if ($custom !== null) {
                    return $custom;
                }
            }

            $urls = [];
            $pushUrl = function (string $loc, ?Carbon $lastmod, string $changefreq, string $priority) use (&$urls): void {
                $urls[] = [
                    'loc' => $loc,
                    'lastmod' => $lastmod?->toAtomString(),
                    'changefreq' => $changefreq,
                    'priority' => $priority,
                ];
            };

            $pushUrl(url('/id'), now(), 'hourly', '1.0');
            $pushUrl(url('/id/price-list'), now(), 'daily', '0.8');
            $pushUrl(url('/id/invoices'), now(), 'daily', '0.6');
            $pushUrl(url('/id/reviews'), now(), 'weekly', '0.6');
            $pushUrl(url('/id/terms-and-condition'), now(), 'monthly', '0.4');
            $pushUrl(url('/id/privacy-policy'), now(), 'monthly', '0.4');
            $pushUrl(url('/id/artikel'), now(), 'daily', '0.7');

            if ($settings['sitemap_include_articles']) {
                Artikel::query()
                    ->where('status', 'active')
                    ->select(['slug', 'updated_at'])
                    ->orderByDesc('updated_at')
                    ->get()
                    ->each(function (Artikel $artikel) use ($pushUrl): void {
                        $pushUrl(url('/id/artikel/' . ltrim((string) $artikel->slug, '/')), $artikel->updated_at, 'weekly', '0.7');
                    });
            }

            return $this->buildUrlSetXml($urls);
        });

        return $this->xmlResponse($xml);
    }

    public function sitemapCategories(): Response
    {
        $settings = $this->getSeoSettings();
        $cacheMinutes = $settings['sitemap_cache_minutes'];

        $xml = Cache::remember('seo:sitemap:categories:v3', now()->addMinutes($cacheMinutes), function () use ($settings): string {
            if (! $settings['sitemap_enabled'] || ! $settings['sitemap_include_categories']) {
                return $this->buildUrlSetXml([]);
            }

            if ($settings['sitemap_mode'] === 'custom_upload') {
                $custom = $this->readCustomSitemapXmlFromAssetId($settings['sitemap_categories_asset_id']);

                if ($custom !== null) {
                    return $custom;
                }
            }

            $urls = [];

            Kategori::query()
                ->where('status', 'active')
                ->select(['kode', 'updated_at'])
                ->orderByDesc('updated_at')
                ->get()
                ->each(function (Kategori $kategori) use (&$urls): void {
                    $urls[] = [
                        'loc' => url('/id/' . ltrim((string) $kategori->kode, '/')),
                        'lastmod' => $kategori->updated_at?->toAtomString(),
                        'changefreq' => 'daily',
                        'priority' => '0.9',
                    ];
                });

            return $this->buildUrlSetXml($urls);
        });

        return $this->xmlResponse($xml);
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function buildSitemapIndexXml(array $entries): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($entries as $entry) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . e((string) ($entry['loc'] ?? '')) . "</loc>\n";

            if (! empty($entry['lastmod'])) {
                $xml .= '    <lastmod>' . e((string) $entry['lastmod']) . "</lastmod>\n";
            }

            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    private function buildUrlSetXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . e((string) ($url['loc'] ?? '')) . "</loc>\n";

            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>' . e((string) $url['lastmod']) . "</lastmod>\n";
            }

            if (! empty($url['changefreq'])) {
                $xml .= '    <changefreq>' . e((string) $url['changefreq']) . "</changefreq>\n";
            }

            if (! empty($url['priority'])) {
                $xml .= '    <priority>' . e((string) $url['priority']) . "</priority>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    private function readCustomSitemapXmlFromAssetId(?int $assetId): ?string
    {
        if (! $assetId) {
            return null;
        }

        $asset = MediaAsset::query()->find($assetId);

        if (! $asset) {
            return null;
        }

        $absolutePath = $asset->resolveAbsolutePath();

        if (! $absolutePath || ! is_file($absolutePath)) {
            return null;
        }

        $contents = @file_get_contents($absolutePath);

        if (! is_string($contents) || trim($contents) === '') {
            return null;
        }

        return $this->isCustomSitemapXmlSafe($contents) ? $contents : null;
    }

    private function isCustomSitemapXmlSafe(string $xmlRaw): bool
    {
        $previousInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string($xmlRaw);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousInternalErrors);

        if ($xml === false || $errors !== []) {
            return false;
        }

        $root = Str::lower((string) $xml->getName());
        if (! in_array($root, ['sitemapindex', 'urlset'], true)) {
            return false;
        }

        $locNodes = $root === 'sitemapindex'
            ? ($xml->xpath('//*[local-name()="sitemap"]/*[local-name()="loc"]') ?: [])
            : ($xml->xpath('//*[local-name()="url"]/*[local-name()="loc"]') ?: []);

        if (count($locNodes) < 1) {
            return false;
        }

        $expectedHost = Str::lower((string) parse_url(url('/'), PHP_URL_HOST));
        if ($expectedHost === '') {
            return true;
        }

        foreach ($locNodes as $locNode) {
            $loc = trim((string) $locNode);
            if ($loc === '') {
                continue;
            }

            $host = Str::lower((string) parse_url($loc, PHP_URL_HOST));
            $path = (string) (parse_url($loc, PHP_URL_PATH) ?? '');

            if ($host !== '' && $host !== $expectedHost) {
                return false;
            }

            if (
                Str::startsWith($host, 'admin.')
                || Str::contains($host, '.admin.')
                || Str::startsWith($path, '/admin')
            ) {
                return false;
            }
        }

        return true;
    }

    private function getSeoSettings(): array
    {
        $defaults = [
            'robots_enabled' => true,
            'robots_custom_lines' => null,
            'sitemap_enabled' => true,
            'sitemap_include_categories' => true,
            'sitemap_include_articles' => true,
            'sitemap_cache_minutes' => 30,
            'sitemap_mode' => 'dynamic',
            'sitemap_index_asset_id' => null,
            'sitemap_main_asset_id' => null,
            'sitemap_categories_asset_id' => null,
        ];

        try {
            $settings = SettingWeb::query()->first([
                'seo_robots_enabled',
                'seo_robots_custom_lines',
                'seo_sitemap_enabled',
                'seo_sitemap_include_categories',
                'seo_sitemap_include_articles',
                'seo_sitemap_cache_minutes',
                'seo_sitemap_mode',
                'seo_sitemap_index_asset_id',
                'seo_sitemap_main_asset_id',
                'seo_sitemap_categories_asset_id',
            ]);

            if (! $settings) {
                return $defaults;
            }

            return [
                'robots_enabled' => (bool) ($settings->seo_robots_enabled ?? true),
                'robots_custom_lines' => $settings->seo_robots_custom_lines,
                'sitemap_enabled' => (bool) ($settings->seo_sitemap_enabled ?? true),
                'sitemap_include_categories' => (bool) ($settings->seo_sitemap_include_categories ?? true),
                'sitemap_include_articles' => (bool) ($settings->seo_sitemap_include_articles ?? true),
                'sitemap_cache_minutes' => max(5, (int) ($settings->seo_sitemap_cache_minutes ?? 30)),
                'sitemap_mode' => in_array((string) $settings->seo_sitemap_mode, ['dynamic', 'custom_upload'], true)
                    ? (string) $settings->seo_sitemap_mode
                    : 'dynamic',
                'sitemap_index_asset_id' => $settings->seo_sitemap_index_asset_id ? (int) $settings->seo_sitemap_index_asset_id : null,
                'sitemap_main_asset_id' => $settings->seo_sitemap_main_asset_id ? (int) $settings->seo_sitemap_main_asset_id : null,
                'sitemap_categories_asset_id' => $settings->seo_sitemap_categories_asset_id ? (int) $settings->seo_sitemap_categories_asset_id : null,
            ];
        } catch (Throwable) {
            return $defaults;
        }
    }
}

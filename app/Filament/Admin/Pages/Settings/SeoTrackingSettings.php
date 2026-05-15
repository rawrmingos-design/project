<?php

namespace App\Filament\Admin\Pages\Settings;

class SeoTrackingSettings extends SettingsSectionPage
{
    protected static ?string $slug = 'settings/seo-tracking';

    protected static ?string $navigationLabel = 'SEO & Tracking';

    protected static ?int $navigationSort = 13;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    /**
     * @return array<string>
     */
    protected function getVisibleSectionHeadings(): ?array
    {
        return [
            'Analytics & Tracking',
            'SEO Crawling',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return [
            'google_analytics_id',
            'facebook_pixel_id',
            'google_tag_manager_id',
            'gtm_custom_head_script',
            'gtm_custom_body_noscript',
            'seo_robots_enabled',
            'seo_sitemap_enabled',
            'seo_sitemap_mode',
            'seo_sitemap_include_categories',
            'seo_sitemap_include_articles',
            'seo_sitemap_cache_minutes',
            'seo_sitemap_index_asset_id',
            'seo_sitemap_main_asset_id',
            'seo_sitemap_categories_asset_id',
            'seo_robots_custom_lines',
        ];
    }
}


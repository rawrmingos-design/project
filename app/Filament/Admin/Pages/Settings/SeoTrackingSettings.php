<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Models\SettingWeb;
use Filament\Notifications\Notification;

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
            'Pelacakan Konversi & Analitik',
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
            'tiktok_tracking_enabled',
            'tiktok_pixel_id',
            'tiktok_access_token',
            'tiktok_test_event_code',
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

    protected function mutateSettingsDataBeforeSave(array $data, SettingWeb $settings): ?array
    {
        $pixelId = trim((string) ($data['tiktok_pixel_id'] ?? ''));
        $newToken = trim((string) ($data['tiktok_access_token'] ?? ''));
        $storedToken = $settings->decryptedTiktokAccessToken();
        $fallbackToken = trim((string) config('services.tiktok.access_token'));
        $effectiveToken = $newToken !== '' ? $newToken : ($storedToken !== '' ? $storedToken : $fallbackToken);

        if ((bool) ($data['tiktok_tracking_enabled'] ?? false)) {
            if ($pixelId === '' || preg_match('/^[A-Z0-9]{15,30}$/i', $pixelId) !== 1) {
                Notification::make()
                    ->title('TikTok Pixel ID belum valid')
                    ->body('Isi Pixel ID TikTok yang valid sebelum mengaktifkan tracking.')
                    ->danger()
                    ->send();

                return null;
            }

            if ($effectiveToken === '') {
                Notification::make()
                    ->title('TikTok Access Token belum tersedia')
                    ->body('Isi token baru atau konfigurasi fallback environment sebelum mengaktifkan tracking.')
                    ->danger()
                    ->send();

                return null;
            }
        }

        $data['tiktok_pixel_id'] = $pixelId !== '' ? $pixelId : null;
        $data['tiktok_test_event_code'] = filled($data['tiktok_test_event_code'] ?? null)
            ? trim((string) $data['tiktok_test_event_code'])
            : null;

        if ($newToken === '') {
            unset($data['tiktok_access_token']);
        }

        return $data;
    }
}


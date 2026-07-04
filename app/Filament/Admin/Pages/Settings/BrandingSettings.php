<?php

namespace App\Filament\Admin\Pages\Settings;

class BrandingSettings extends SettingsSectionPage
{
    protected static ?string $slug = 'settings/branding';

    protected static ?string $navigationLabel = 'Branding';

    protected static ?int $navigationSort = 12;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    /**
     * @return array<string>
     */
    protected function getVisibleSectionHeadings(): ?array
    {
        return [
            'Logo & Warna',
            'Tema Musiman',
            'Link Sosial Media',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return [
            'logo_header',
            'logo_footer',
            'logo_favicon',
            'pwa_icon_source',
            'pwa_icon_generated_at',
            'warna1',
            'warna2',
            'warna3',
            'warna4',
            'seasonal_enabled',
            'seasonal_mode',
            'seasonal_theme',
            'seasonal_effect_intensity',
            'seasonal_background_image',
            'seasonal_background_opacity',
            'seasonal_starts_at',
            'seasonal_ends_at',
            'url_wa',
            'url_ig',
            'url_tiktok',
            'url_youtube',
            'url_fb',
        ];
    }
}


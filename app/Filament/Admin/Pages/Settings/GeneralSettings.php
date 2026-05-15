<?php

namespace App\Filament\Admin\Pages\Settings;

class GeneralSettings extends SettingsSectionPage
{
    protected static ?string $slug = 'settings/general';

    protected static ?string $navigationLabel = 'General';

    protected static ?int $navigationSort = 11;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    /**
     * @return array<string>
     */
    protected function getVisibleSectionHeadings(): ?array
    {
        return [
            'Website Information',
            'Homepage Popup',
            'Live Sales Toast',
            'Admin Login CAPTCHA',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return [
            'judul_web',
            'order_prefik',
            'public_theme',
            'deskripsi_web',
            'keywords',
            'home_popup_enabled',
            'live_sales_enabled',
            'captcha_enabled',
            'captcha_bypass',
            'captcha_site_key',
            'captcha_secret',
            'google_client_id',
        ];
    }
}


<?php

namespace App\Filament\Admin\Pages\Settings;

class ProvidersApiSettings extends SettingsSectionPage
{
    protected static ?string $slug = 'settings/providers-api';

    protected static ?string $navigationLabel = 'Providers & API';

    protected static ?int $navigationSort = 15;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    /**
     * @return array<string>
     */
    protected function getVisibleSectionHeadings(): ?array
    {
        return [
            'TopUpIndo',
            'BangJeff',
            'Aoshi',
            'Mobile Game Store',
            'VIP Reseller',
            'Digiflazz',
            'API Games',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return [
            'topupindo_api',
            'apikey_bangjeff',
            'apikey_aoshi',
            'api_mobilegamestore',
            'vip_apiid',
            'vip_apikey',
            'vip_sign',
            'username_digi',
            'api_key_digi',
            'apigames_merchant',
            'apigames_secret',
        ];
    }
}


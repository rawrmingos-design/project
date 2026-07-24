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
            'BangJeff',
            'VIP Reseller',
            'Digiflazz',
            'API Games',
            'SufPayment',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return [
            'apikey_bangjeff',
            'vip_apiid',
            'vip_apikey',
            'vip_sign',
            'username_digi',
            'api_key_digi',
            'apigames_merchant',
            'apigames_secret',
            'sufpayment_api_id',
            'sufpayment_api_key',
            'sufpayment_secret_key',
        ];
    }
}


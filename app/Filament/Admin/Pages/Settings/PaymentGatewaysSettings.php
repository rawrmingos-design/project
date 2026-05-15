<?php

namespace App\Filament\Admin\Pages\Settings;

class PaymentGatewaysSettings extends SettingsSectionPage
{
    protected static ?string $slug = 'settings/payment-gateways';

    protected static ?string $navigationLabel = 'Payment Gateways';

    protected static ?int $navigationSort = 14;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    /**
     * @return array<string>
     */
    protected function getVisibleSectionHeadings(): ?array
    {
        return [
            'Deposit Configuration',
            'PayDisini',
            'Tripay',
            'TokoPay',
            'Duitku',
            'E-Wallet Accounts',
            'Bank Account',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return [
            'deposit_jalur',
            'paydisini_apikey',
            'tripay_api',
            'tripay_merchant_code',
            'tripay_private_key',
            'tokopay_merchant_id',
            'tokopay_secret_key',
            'duitku_merchant_code',
            'duitku_merchant_key',
            'duitku_callback_url',
            'duitku_return_url',
            'duitku_mode',
            'duitku_enabled',
            'ovo_admin',
            'ovo1_admin',
            'gopay_admin',
            'gopay1_admin',
            'dana_admin',
            'shopeepay_admin',
            'bca_admin',
        ];
    }
}


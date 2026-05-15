<?php

namespace App\Filament\Admin\Pages\Settings;

class NotificationsSettings extends SettingsSectionPage
{
    protected static ?string $slug = 'settings/notifications';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?int $navigationSort = 16;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    /**
     * @return array<string>
     */
    protected function getVisibleSectionHeadings(): ?array
    {
        return [
            'WhatsApp Configuration',
            'Mail Configuration',
            'Invoice Delivery Channels',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return [
            'wa_provider',
            'nomor_admin',
            'wa_key',
            'wa_number',
            'easywa_email',
            'easywa_secret_key',
            'easywa_send_type',
            'easywa_send_delay',
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_encryption',
            'mail_username',
            'mail_password',
            'mail_from_address',
            'mail_from_name',
            'invoice_notify_via_whatsapp',
            'invoice_notify_via_email',
            'affiliate_notify_via_whatsapp',
            'affiliate_notify_via_email',
        ];
    }
}


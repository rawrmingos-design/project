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
        $headings = [
            'Konfigurasi WhatsApp',
            'Konfigurasi Email',
            'Channel Notifikasi',
        ];

        if (config('bot.order_enabled', env('BOT_ORDER_ENABLED', false))) {
            array_splice($headings, 1, 0, ['Konfigurasi Telegram']);
            array_splice($headings, 1, 0, ['Konfigurasi Bot Order WhatsApp']);
        }

        return $headings;
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
            'bot_order_wa_enabled',
            'use_separate_bot_wa',
            'wa_bot_key',
            'wa_bot_number',
            'openwa_session_id',
            'openwa_webhook_secret',
            'telegram_bot_token',
            'telegram_webhook_secret',
            'bot_order_tg_enabled',
            'easywa_email',
            'easywa_secret_key',
            'easywa_send_type',
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
            'tenant_notify_via_whatsapp',
            'tenant_notify_via_email',
        ];
    }
}


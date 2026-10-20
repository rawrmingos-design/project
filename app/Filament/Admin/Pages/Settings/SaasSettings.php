<?php

namespace App\Filament\Admin\Pages\Settings;

/**
 * Kebijakan SaaS & penagihan tenant.
 *
 * Halaman ini tidak menambah tabel baru: kolomnya ada di `setting_webs` dan
 * sudah diterapkan oleh DatabaseSettingsBridge (web + queue worker + command
 * billing). Tujuannya satu — pemilik platform bisa mematikan SaaS atau
 * mengubah aturan billing tanpa deploy dan tanpa menyentuh .env.
 */
class SaasSettings extends SettingsSectionPage
{
    protected static ?string $slug = 'settings/saas';

    protected static ?string $navigationLabel = 'Kebijakan SaaS';

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    /**
     * @return array<string>
     */
    protected function getVisibleSectionHeadings(): ?array
    {
        return [
            'Kebijakan SaaS',
            'Penagihan Tenant',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return [
            'saas_enabled',
            'subdomain_guard_enabled',
            'billing_renewal_enabled',
            'billing_reminder_days_before',
            'billing_grace_days',
            'billing_late_fee',
            'billing_payment_link_minutes',
        ];
    }
}

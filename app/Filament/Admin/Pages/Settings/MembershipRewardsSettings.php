<?php

namespace App\Filament\Admin\Pages\Settings;

class MembershipRewardsSettings extends SettingsSectionPage
{
    protected static ?string $slug = 'settings/membership-rewards';

    protected static ?string $navigationLabel = 'Membership & Rewards';

    protected static ?int $navigationSort = 17;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    /**
     * @return array<string>
     */
    protected function getVisibleSectionHeadings(): ?array
    {
        return [
            'Tier Markup Settings',
            'Tier System Configuration',
            'Point System Configuration',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return [
            'profit_member',
            'profit_gold',
            'profit_platinum',
            'trx_count_gold',
            'trx_count_platinum',
            'point_per_nominal',
            'point_value',
            'max_point_usage_percent',
        ];
    }
}


<?php

namespace App\Filament\Admin\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use UnitEnum;

class Integrations extends Cluster
{
    protected static ?string $navigationLabel = 'Integrations';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static UnitEnum|string|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return static::canAccessClusteredComponents();
    }
}

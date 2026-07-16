<?php

namespace App\Filament\Admin\Resources\Pembelians\Pages;

use App\Filament\Admin\Resources\Pembelians\PembelianResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListPembelians extends ListRecords
{
    protected static string $resource = PembelianResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction removed - orders are read-only
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Resources\Pembelians\Widgets\RegularOrderStatsOverview::class,
            \App\Filament\Admin\Resources\Pembelians\Widgets\PaymentStatusStatsOverview::class,
            \App\Filament\Admin\Resources\Pembelians\Widgets\PembelianStatusStatsOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}

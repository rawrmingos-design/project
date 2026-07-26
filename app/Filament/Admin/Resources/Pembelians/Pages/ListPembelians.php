<?php

namespace App\Filament\Admin\Resources\Pembelians\Pages;

use App\Filament\Admin\Resources\Pembelians\PembelianResource;
use App\Services\Payments\ExpirePendingPayments;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;

class ListPembelians extends ListRecords
{
    protected static string $resource = PembelianResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    public function mount(): void
    {
        parent::mount();

        try {
            app(ExpirePendingPayments::class)->expire(batchSize: 50, limit: 50);
        } catch (\Throwable $exception) {
            Log::warning('Failed to sync expired payments before rendering Pembelians list', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

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

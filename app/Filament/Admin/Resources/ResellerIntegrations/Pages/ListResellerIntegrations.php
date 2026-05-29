<?php

namespace App\Filament\Admin\Resources\ResellerIntegrations\Pages;

use App\Filament\Admin\Resources\ResellerIntegrations\ResellerIntegrationResource;
use App\Filament\Admin\Resources\ResellerIntegrations\Widgets\ConnectionsReadinessOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResellerIntegrations extends ListRecords
{
    protected static string $resource = ResellerIntegrationResource::class;

    public function getTitle(): string
    {
        return 'Connections';
    }

    public function getSubheading(): ?string
    {
        return 'Ringkasan connection live dan sandbox, termasuk incoming shared rules dan outgoing webhooks per partner.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ConnectionsReadinessOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }
}

<?php

namespace App\Filament\Admin\Resources\AffiliateRequestResource\Pages;

use App\Filament\Admin\Resources\AffiliateRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAffiliateRequests extends ListRecords
{
    protected static string $resource = AffiliateRequestResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make('Permintaan')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('affiliate_status', 'pending')),
            'active' => Tab::make('Affiliate Aktif')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('affiliate_status', 'active')),
            'inactive' => Tab::make('Belum Affiliate')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('affiliate_status', 'inactive')),
            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('affiliate_status', 'rejected')),
        ];
    }

    protected function getActions(): array
    {
        return [
            // No create action needed
        ];
    }
}

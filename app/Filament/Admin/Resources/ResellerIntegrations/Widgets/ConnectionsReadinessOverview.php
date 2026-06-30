<?php

namespace App\Filament\Admin\Resources\ResellerIntegrations\Widgets;

use App\Filament\Admin\Resources\ResellerIntegrations\ResellerIntegrationResource;
use App\Models\ResellerCallbackDelivery;
use App\Models\ResellerIntegration;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConnectionsReadinessOverview extends BaseWidget
{
    protected ?string $heading = 'Connections Summary';

    protected function getStats(): array
    {
        $incoming = ResellerIntegrationResource::sharedIncomingSnapshot();

        $activeConnectionCount = ResellerIntegration::query()
            ->where('is_active', true)
            ->count();

        $outgoingReady = ResellerIntegration::query()
            ->where('is_active', true)
            ->whereHas('callbackProfile', function ($query): void {
                $query
                    ->where('is_enabled', true)
                    ->whereNotNull('callback_url')
                    ->where('callback_url', '!=', '')
                    ->whereNotNull('webhook_secret_encrypted')
                    ->where('webhook_secret_encrypted', '!=', '');
            })
            ->count();

        $recentFailures = ResellerCallbackDelivery::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return [
            Stat::make('Active Connections', number_format($activeConnectionCount))
                ->description('Partner live connections yang aktif')
                ->descriptionIcon('heroicon-m-link')
                ->color($activeConnectionCount > 0 ? 'success' : 'gray'),
            Stat::make('Incoming Rules', number_format($incoming['protected_rules']))
                ->description(sprintf('%d active rules / %d allowed IPs', $incoming['active_rules'], $incoming['allowed_ips']))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($incoming['configured'] ? 'success' : 'warning'),
            Stat::make('Outgoing Ready', sprintf('%d / %d', $outgoingReady, $activeConnectionCount))
                ->description('Connection dengan webhook live yang siap dipakai')
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->color($outgoingReady > 0 ? 'success' : 'warning'),
            Stat::make('Recent Failed Deliveries', number_format($recentFailures))
                ->description('Gagal kirim callback dalam 24 jam terakhir')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($recentFailures > 0 ? 'danger' : 'success'),
        ];
    }
}

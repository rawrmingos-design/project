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

        $activeConnections = ResellerIntegration::query()
            ->with('callbackProfile')
            ->where('is_active', true)
            ->get();

        $outgoingReady = $activeConnections
            ->filter(fn (ResellerIntegration $integration): bool => $integration->outboundReadinessSummary()['state'] === 'ready')
            ->count();

        $recentFailures = ResellerCallbackDelivery::query()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return [
            Stat::make('Active Connections', number_format($activeConnections->count()))
                ->description('Partner live connections yang aktif')
                ->descriptionIcon('heroicon-m-link')
                ->color($activeConnections->isNotEmpty() ? 'success' : 'gray'),
            Stat::make('Incoming Rules', number_format($incoming['protected_rules']))
                ->description(sprintf('%d active rules / %d allowed IPs', $incoming['active_rules'], $incoming['allowed_ips']))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($incoming['configured'] ? 'success' : 'warning'),
            Stat::make('Outgoing Ready', sprintf('%d / %d', $outgoingReady, $activeConnections->count()))
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

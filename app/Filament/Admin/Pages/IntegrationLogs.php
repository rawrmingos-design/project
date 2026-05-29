<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Clusters\Integrations;
use App\Filament\Admin\Resources\InboundSourcePolicies\InboundSourcePolicyResource;
use App\Filament\Admin\Resources\ResellerCallbackProfiles\ResellerCallbackProfileResource;
use App\Filament\Admin\Resources\ResellerIntegrations\ResellerIntegrationResource;
use App\Models\InboundSourceEvent;
use App\Models\ResellerCallbackDelivery;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class IntegrationLogs extends Page
{
    protected static ?string $cluster = Integrations::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Logs';

    protected string $view = 'filament.admin.pages.integration-logs';

    public function getTitle(): string
    {
        return 'Integration Logs';
    }

    public function getViewData(): array
    {
        $incomingEvents = InboundSourceEvent::query()
            ->latest('id')
            ->limit(12)
            ->get();

        $outgoingDeliveries = ResellerCallbackDelivery::query()
            ->with(['integration.user', 'pembelian'])
            ->latest('id')
            ->limit(12)
            ->get();

        return [
            'incomingSummary' => $this->incomingSummary($incomingEvents),
            'outgoingSummary' => $this->outgoingSummary($outgoingDeliveries),
            'incomingEvents' => $incomingEvents,
            'outgoingDeliveries' => $outgoingDeliveries,
            'connectionsUrl' => ResellerIntegrationResource::getUrl(),
            'incomingRulesUrl' => InboundSourcePolicyResource::getUrl(),
            'outgoingWebhooksUrl' => ResellerCallbackProfileResource::getUrl(),
        ];
    }

    private function incomingSummary(Collection $incomingEvents): array
    {
        return [
            'total' => $incomingEvents->count(),
            'denied' => $incomingEvents->where('decision', 'deny')->count(),
            'matched' => $incomingEvents->where('decision', 'allow')->count(),
            'warnings' => $incomingEvents->filter(fn (InboundSourceEvent $event): bool => in_array($event->decision, ['log_only_no_match', 'error'], true))->count(),
        ];
    }

    private function outgoingSummary(Collection $outgoingDeliveries): array
    {
        return [
            'total' => $outgoingDeliveries->count(),
            'delivered' => $outgoingDeliveries->where('status', 'delivered')->count(),
            'failed' => $outgoingDeliveries->where('status', 'failed')->count(),
            'pending' => $outgoingDeliveries->where('status', 'pending')->count(),
            'sandbox' => $outgoingDeliveries->where('environment', 'sandbox')->count(),
        ];
    }

    public static function inboundDecisionColor(?string $decision): string
    {
        return match ($decision) {
            'allow' => 'success',
            'deny' => 'danger',
            'log_only_no_match' => 'warning',
            'error' => 'gray',
            default => 'gray',
        };
    }

    public static function outboundStatusColor(?string $status): string
    {
        return match ($status) {
            'delivered' => 'success',
            'failed' => 'danger',
            'pending' => 'warning',
            default => 'gray',
        };
    }

    public static function headline(?string $value, string $fallback = '-'): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        return Str::headline($value);
    }
}

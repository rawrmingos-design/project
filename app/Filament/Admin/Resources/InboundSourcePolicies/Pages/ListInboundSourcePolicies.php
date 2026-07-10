<?php

namespace App\Filament\Admin\Resources\InboundSourcePolicies\Pages;

use App\Filament\Admin\Resources\InboundSourcePolicies\InboundSourcePolicyResource;
use App\Models\InboundSourcePolicy;
use App\Services\BulkModeSwitchService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListInboundSourcePolicies extends ListRecords
{
    protected static string $resource = InboundSourcePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            $this->getSwitchAllToEnforceAction(),
            $this->getSwitchAllToLogOnlyAction(),
        ];
    }

    protected function getSwitchAllToEnforceAction(): Action
    {
        return Action::make('switch_all_to_enforce')
            ->label('Switch All to Enforce')
            ->icon('heroicon-o-shield-exclamation')
            ->color('danger')
            ->form(function (): array {
                $policies = $this->getEnforceCandidatePolicies();
                $emptyPolicies = app(BulkModeSwitchService::class)->findEmptyPolicies($policies);
                $filterLabel = $this->getActiveSourceDomainFilterLabel();

                $components = [];

                // Info placeholder showing count and filter context
                $description = "{$policies->count()} policy akan diubah ke mode Blokir Jika Tidak Cocok.";
                if ($filterLabel) {
                    $description .= "\nFilter aktif — Jenis Callback: {$filterLabel}";
                }

                $components[] = Placeholder::make('confirmation_info')
                    ->label('')
                    ->content(new HtmlString(
                        '<div class="rounded-lg border border-gray-300 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-800">'
                        . '<p class="text-sm text-gray-800 dark:text-gray-200">' . nl2br(e($description)) . '</p>'
                        . '</div>'
                    ));

                // Empty policy warning (two-phase pattern)
                if ($emptyPolicies->isNotEmpty()) {
                    $policyNames = $emptyPolicies->map(
                        fn (InboundSourcePolicy $policy) => "{$policy->source_domain} — {$policy->source_name}"
                    )->implode(', ');

                    $components[] = Placeholder::make('empty_policy_warning')
                        ->label('')
                        ->content(new HtmlString(
                            '<div class="rounded-lg border border-danger-300 bg-danger-50 p-4 dark:border-danger-600 dark:bg-danger-950">'
                            . '<p class="text-sm font-medium text-danger-800 dark:text-danger-200">'
                            . '⚠️ Perhatian: Policy berikut tidak memiliki IP aktif dan akan memblokir SEMUA trafik masuk:'
                            . '</p>'
                            . '<p class="mt-1 text-xs text-danger-600 dark:text-danger-400">'
                            . e($policyNames)
                            . '</p>'
                            . '</div>'
                        ));

                    $components[] = Checkbox::make('acknowledge_empty_risk')
                        ->label('Saya memahami risiko memblokir semua trafik pada policy tanpa IP aktif')
                        ->required();
                }

                return $components;
            })
            ->action(function (array $data, Action $action): void {
                $service = app(BulkModeSwitchService::class);
                $policies = $this->getEnforceCandidatePolicies();

                if ($policies->isEmpty()) {
                    Notification::make()
                        ->title('Tidak ada policy yang perlu diubah')
                        ->body('Semua policy aktif sudah dalam mode yang sesuai.')
                        ->warning()
                        ->send();

                    return;
                }

                $emptyPolicies = $service->findEmptyPolicies($policies);

                // Safety validation: require acknowledgment if empty policies exist
                if ($emptyPolicies->isNotEmpty() && empty($data['acknowledge_empty_risk'])) {
                    Notification::make()
                        ->title('Konfirmasi diperlukan')
                        ->body('Centang kotak konfirmasi untuk melanjutkan.')
                        ->danger()
                        ->persistent()
                        ->send();

                    $action->halt();

                    return;
                }

                $operator = auth()->user()?->email ?? auth()->user()?->name ?? 'unknown';
                $updatedCount = $service->execute($policies, 'enforce', $operator, $emptyPolicies);

                Notification::make()
                    ->title('Mode berhasil diubah ke Blokir')
                    ->body("{$updatedCount} policy diperbarui ke mode Blokir Jika Tidak Cocok.")
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading('Switch All to Enforce')
            ->modalDescription('Anda akan mengubah semua policy aktif yang sedang dalam mode Pantau Saja ke mode Blokir Jika Tidak Cocok.')
            ->modalSubmitActionLabel('Ya, Switch ke Enforce')
            ->visible(fn (): bool => $this->getEnforceCandidatePolicies()->isNotEmpty());
    }

    protected function getSwitchAllToLogOnlyAction(): Action
    {
        return Action::make('switch_all_to_log_only')
            ->label('Switch All to Log Only')
            ->icon('heroicon-o-eye')
            ->color('warning')
            ->form(function (): array {
                $policies = $this->getLogOnlyCandidatePolicies();
                $filterLabel = $this->getActiveSourceDomainFilterLabel();

                $components = [];

                // Info placeholder showing count and filter context
                $description = "{$policies->count()} policy akan diubah ke mode Pantau Saja.";
                if ($filterLabel) {
                    $description .= "\nFilter aktif — Jenis Callback: {$filterLabel}";
                }

                $components[] = Placeholder::make('confirmation_info')
                    ->label('')
                    ->content(new HtmlString(
                        '<div class="rounded-lg border border-gray-300 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-800">'
                        . '<p class="text-sm text-gray-800 dark:text-gray-200">' . nl2br(e($description)) . '</p>'
                        . '</div>'
                    ));

                return $components;
            })
            ->action(function (array $data): void {
                $service = app(BulkModeSwitchService::class);
                $policies = $this->getLogOnlyCandidatePolicies();

                if ($policies->isEmpty()) {
                    Notification::make()
                        ->title('Tidak ada policy yang perlu diubah')
                        ->body('Semua policy aktif sudah dalam mode yang sesuai.')
                        ->warning()
                        ->send();

                    return;
                }

                $operator = auth()->user()?->email ?? auth()->user()?->name ?? 'unknown';
                $updatedCount = $service->execute($policies, 'log_only', $operator);

                Notification::make()
                    ->title('Mode berhasil diubah ke Pantau Saja')
                    ->body("{$updatedCount} policy diperbarui ke mode Pantau Saja.")
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading('Switch All to Log Only')
            ->modalDescription('Anda akan mengubah semua policy aktif yang sedang dalam mode Blokir Jika Tidak Cocok ke mode Pantau Saja.')
            ->modalSubmitActionLabel('Ya, Switch ke Log Only')
            ->visible(fn (): bool => $this->getLogOnlyCandidatePolicies()->isNotEmpty());
    }

    /**
     * Get active policies currently in log_only mode, scoped by source_domain filter if active.
     */
    protected function getEnforceCandidatePolicies(): \Illuminate\Support\Collection
    {
        $query = InboundSourcePolicy::query()
            ->where('is_active', true)
            ->where('mode', 'log_only');

        // Apply source_domain filter if active
        if ($sourceDomainFilter = $this->getTableFilterState('source_domain')['value'] ?? null) {
            $query->where('source_domain', $sourceDomainFilter);
        }

        return $query->get();
    }

    /**
     * Get active policies currently in enforce mode, scoped by source_domain filter if active.
     */
    protected function getLogOnlyCandidatePolicies(): \Illuminate\Support\Collection
    {
        $query = InboundSourcePolicy::query()
            ->where('is_active', true)
            ->where('mode', 'enforce');

        // Apply source_domain filter if active
        if ($sourceDomainFilter = $this->getTableFilterState('source_domain')['value'] ?? null) {
            $query->where('source_domain', $sourceDomainFilter);
        }

        return $query->get();
    }

    /**
     * Get the human-readable label for the active source_domain filter.
     */
    protected function getActiveSourceDomainFilterLabel(): ?string
    {
        $value = $this->getTableFilterState('source_domain')['value'] ?? null;

        if (! $value) {
            return null;
        }

        return match ($value) {
            'supplier_callback' => 'Callback Supplier',
            'payment_gateway' => 'Payment Gateway',
            default => $value,
        };
    }
}

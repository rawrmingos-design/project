<?php

namespace App\Filament\Admin\Resources\ResellerIntegrations\Pages;

use App\Filament\Admin\Resources\ResellerIntegrations\ResellerIntegrationResource;
use App\Services\SandboxApiKeyService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditResellerIntegration extends EditRecord
{
    protected static string $resource = ResellerIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rotateApiKey')
                ->label(fn (): string => 'Rotate ' . ucfirst($this->record->mode) . ' API Key')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => 'Rotate ' . ucfirst($this->record->mode) . ' API Key')
                ->modalDescription('Akan membuat API key baru untuk integrasi ini. Raw key hanya ditampilkan sekali setelah action dijalankan.')
                ->action(function (): void {
                    $prefix = $this->record->mode === 'sandbox' ? 'egysbx_' : 'egylive_';
                    $rawKey = $prefix . \Illuminate\Support\Str::random(40);

                    $this->record->api_key = $rawKey;
                    $this->record->api_key_rotated_at = now();
                    $this->record->save();

                    Notification::make()
                        ->title(ucfirst($this->record->mode) . ' API key rotated')
                        ->body("Copy key ini sekarang: {$rawKey}")
                        ->warning()
                        ->persistent()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}

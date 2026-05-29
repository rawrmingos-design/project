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
            Action::make('rotateSandboxApiKey')
                ->label('Rotate Sandbox API Key')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->visible(fn (): bool => $this->record->mode === 'sandbox')
                ->requiresConfirmation()
                ->modalHeading('Rotate Sandbox API Key')
                ->modalDescription('Akan membuat API key sandbox baru untuk user partner ini. Raw key hanya ditampilkan sekali setelah action dijalankan.')
                ->action(function (SandboxApiKeyService $sandboxApiKeyService): void {
                    $rawKey = $sandboxApiKeyService->rotateForUser($this->record->user);

                    Notification::make()
                        ->title('Sandbox API key rotated')
                        ->body("Copy key ini sekarang: {$rawKey}")
                        ->warning()
                        ->persistent()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}

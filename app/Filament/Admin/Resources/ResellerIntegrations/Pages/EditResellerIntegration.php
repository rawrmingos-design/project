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
                    $prefix = $this->record->mode === 'sandbox' ? 'rsbx_' : 'rliv_';
                    $rawKey = $prefix . \Illuminate\Support\Str::random(40);

                    $this->record->api_key = $rawKey;
                    $this->record->api_key_rotated_at = now();
                    $this->record->save();

                    // Flash new key to session for one-time display
                    $sessionKey = $this->record->mode === 'sandbox' ? 'new_sandbox_api_key' : 'new_live_api_key';
                    session()->flash($sessionKey, $rawKey);

                    // Also dispatch notification job to send key via email/WhatsApp
                    $liveKey = $this->record->mode === 'live' ? $rawKey : null;
                    $sandboxKey = $this->record->mode === 'sandbox' ? $rawKey : null;
                    
                    \App\Jobs\NotifyResellerKeysJob::dispatch(
                        $this->record->user,
                        $liveKey,
                        $sandboxKey,
                        'rotation'
                    );

                    Notification::make()
                        ->title(ucfirst($this->record->mode) . ' API key rotated')
                        ->body("New key has been sent to your email/WhatsApp and is available to copy on the credentials page.")
                        ->success()
                        ->persistent()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class MaintenanceToggle extends Component implements HasActions
{
    use InteractsWithActions;

    public $isDown = false;

    public function mount()
    {
        $this->checkStatus();
    }

    public function checkStatus()
    {
        $this->isDown = app()->isDownForMaintenance();
    }

    public function toggleAction(): Action
    {
        if ($this->isDown) {
            return Action::make('toggle')
                ->label('Maintenance Aktif')
                ->color('danger')
                ->icon('heroicon-m-exclamation-triangle')
                ->requiresConfirmation()
                ->modalHeading('Kembalikan Website ke Mode Live?')
                ->modalDescription('Pengguna publik akan dapat mengakses kembali seluruh fitur website.')
                ->modalSubmitActionLabel('Ya, Go Live!')
                ->action(function () {
                    Artisan::call('up');
                    $this->checkStatus();
                    Notification::make()
                        ->title('Website is now Live')
                        ->success()
                        ->send();
                });
        }

        return Action::make('toggle')
            ->label('Live')
            ->color('success')
            ->icon('heroicon-m-globe-alt')
            ->requiresConfirmation()
            ->modalHeading('Aktifkan Mode Maintenance?')
            ->modalDescription('Pengunjung selain Admin akan melihat halaman "Sedang Perbaikan" (503 Service Unavailable). Anda bisa memasukkan Secret Key jika ingin bisa mem-bypass halaman depan dari luar.')
            ->form([
                TextInput::make('secret')
                    ->label('Secret Key (Opsional)')
                    ->placeholder('Misal: buka-pintu-rahasia')
                    ->helperText('Bila diisi, Anda bisa mengakses web depan melalui URL /namarightkey untuk mem-bypass page 503 saat testing luar.')
            ])
            ->modalSubmitActionLabel('Ya, Aktifkan Maintenance!')
            ->action(function (array $data) {
                // Konfigurasi argumen artisan
                $args = [];
                if (!empty($data['secret'])) {
                    $args['--secret'] = $data['secret'];
                }

                Artisan::call('down', $args);
                $this->checkStatus();
                
                $notif = Notification::make()
                    ->title('Maintenance Mode Diaktifkan')
                    ->warning();

                if (!empty($data['secret'])) {
                    $notif->body('Secret URL anda: /' . $data['secret']);
                }
                
                $notif->send();
            });
    }

    public function render()
    {
        return view('livewire.maintenance-toggle');
    }
}

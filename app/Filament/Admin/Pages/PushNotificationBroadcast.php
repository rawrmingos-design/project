<?php

namespace App\Filament\Admin\Pages;

use App\Services\PublicWebPushService;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use UnitEnum;

class PushNotificationBroadcast extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static UnitEnum|string|null $navigationGroup = 'Notification Management';

    protected static ?string $navigationLabel = 'Push Broadcast';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'push-broadcast';

    protected string $view = 'filament.admin.pages.push-notification-broadcast';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'title' => '',
            'body' => '',
            'target_url' => url('/id'),
            'icon_url' => asset('assets/pwa/icon-192.png'),
        ]);
    }

    public function getTitle(): string
    {
        return 'Push Notification Broadcast';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Notification Title')
                    ->required()
                    ->maxLength(120),
                Textarea::make('body')
                    ->label('Notification Body')
                    ->required()
                    ->rows(4)
                    ->maxLength(240),
                TextInput::make('target_url')
                    ->label('Target URL')
                    ->required()
                    ->url()
                    ->maxLength(255),
                TextInput::make('icon_url')
                    ->label('Icon URL')
                    ->url()
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $state = $this->form->getState();
        $service = app(PublicWebPushService::class);

        if (! $service->isConfigured()) {
            Notification::make()
                ->title('Konfigurasi VAPID web push belum lengkap.')
                ->danger()
                ->send();

            return;
        }

        $result = $service->broadcastToActiveSubscriptions([
            'title' => $state['title'],
            'body' => $state['body'],
            'url' => $state['target_url'],
            'icon' => $state['icon_url'] ?: asset('assets/pwa/icon-192.png'),
            'badge' => asset('assets/pwa/icon-192.png'),
            'tag' => 'public-broadcast',
        ]);

        Notification::make()
            ->title('Push broadcast selesai')
            ->body('Berhasil: ' . $result['success_count'] . ' • Gagal: ' . $result['failed_count'] . ' • Total subscriber aktif: ' . $result['total'])
            ->success()
            ->send();
    }
}

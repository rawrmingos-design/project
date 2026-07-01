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
        ]);
    }

    public function getTitle(): string
    {
        return 'Kirim Notifikasi PWA';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul Notifikasi')
                    ->helperText('Contoh: Promo Diamond Hari Ini, Maintenance Server, atau Info Pesanan.')
                    ->placeholder('Tulis judul singkat yang langsung dipahami user')
                    ->required()
                    ->maxLength(120),
                Textarea::make('body')
                    ->label('Isi Pesan')
                    ->helperText('Jelaskan isi notifikasi dengan bahasa singkat dan jelas. Hindari pesan terlalu panjang.')
                    ->placeholder('Contoh: Promo Mobile Legends diskon sampai malam ini. Klik untuk lihat detailnya.')
                    ->required()
                    ->rows(4)
                    ->maxLength(240),
                TextInput::make('target_url')
                    ->label('Halaman Tujuan Saat Diklik')
                    ->helperText('Saat user menekan notifikasi, mereka akan dibuka ke halaman ini. Contoh: halaman utama, halaman promo, atau invoice.')
                    ->placeholder(url('/id'))
                    ->required()
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
            'icon' => asset('assets/pwa/icon-192.png'),
            'badge' => asset('assets/pwa/icon-192.png'),
            'tag' => 'public-broadcast',
        ]);

        Notification::make()
            ->title('Notifikasi berhasil diproses')
            ->body('Terkirim: ' . $result['success_count'] . ' • Gagal: ' . $result['failed_count'] . ' • Total device aktif: ' . $result['total'])
            ->success()
            ->send();
    }
}

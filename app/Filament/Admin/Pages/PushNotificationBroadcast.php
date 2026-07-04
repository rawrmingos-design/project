<?php

namespace App\Filament\Admin\Pages;

use App\Jobs\SendPublicPushBroadcastJob;
use App\Models\PublicPushBroadcast;
use App\Services\PublicWebPushService;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
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
            'send_mode' => 'now',
            'scheduled_at' => null,
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
                Select::make('send_mode')
                    ->label('Waktu Pengiriman')
                    ->options([
                        'now' => 'Kirim sekarang',
                        'scheduled' => 'Jadwalkan',
                    ])
                    ->default('now')
                    ->required()
                    ->native(false)
                    ->live(),
                DateTimePicker::make('scheduled_at')
                    ->label('Tanggal & Jam Kirim')
                    ->helperText('Minimal 5 menit dari sekarang agar jadwal pengiriman lebih aman diproses.')
                    ->seconds(false)
                    ->native(false)
                    ->visible(fn (Get $get): bool => $get('send_mode') === 'scheduled')
                    ->required(fn (Get $get): bool => $get('send_mode') === 'scheduled')
                    ->minDate(now()->addMinutes(5)),
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

        $sendAt = ($state['send_mode'] ?? 'now') === 'scheduled'
            ? now()->parse($state['scheduled_at'])
            : now();

        if (($state['send_mode'] ?? 'now') === 'scheduled' && $sendAt->lt(now()->addMinutes(5))) {
            Notification::make()
                ->title('Jadwal terlalu dekat')
                ->body('Pilih waktu minimal 5 menit dari sekarang agar jadwal pengiriman bisa diproses dengan aman.')
                ->danger()
                ->send();

            return;
        }

        if ($this->exceedsBroadcastWindowLimit($sendAt)) {
            Notification::make()
                ->title('Batas pengiriman tercapai')
                ->body('Maksimal 2 notifikasi PWA dalam rentang 2 jam agar tidak dianggap spam.')
                ->danger()
                ->send();

            return;
        }

        $payload = [
            'title' => $state['title'],
            'body' => $state['body'],
            'url' => $state['target_url'],
            'icon' => asset('assets/pwa/icon-192.png'),
            'badge' => asset('assets/pwa/badge-72.png'),
            'tag' => 'public-broadcast-' . now()->timestamp,
        ];

        $broadcast = PublicPushBroadcast::query()->create([
            'created_by' => auth()->id(),
            'send_mode' => $state['send_mode'] ?? 'now',
            'status' => ($state['send_mode'] ?? 'now') === 'scheduled' ? 'scheduled' : 'queued',
            'title' => $state['title'],
            'body' => $state['body'],
            'target_url' => $state['target_url'],
            'payload' => $payload,
            'scheduled_at' => $sendAt,
        ]);

        SendPublicPushBroadcastJob::dispatch($broadcast->getKey())->delay($sendAt);

        Notification::make()
            ->title(($state['send_mode'] ?? 'now') === 'scheduled' ? 'Notifikasi dijadwalkan' : 'Notifikasi masuk antrian')
            ->body(($state['send_mode'] ?? 'now') === 'scheduled'
                ? 'Akan dikirim pada ' . $sendAt->format('d M Y H:i') . '.'
                : 'Notifikasi akan diproses beberapa saat lagi.')
            ->success()
            ->send();

        $this->form->fill([
            'title' => '',
            'body' => '',
            'target_url' => url('/id'),
            'send_mode' => 'now',
            'scheduled_at' => null,
        ]);
    }

    private function exceedsBroadcastWindowLimit(CarbonInterface $sendAt): bool
    {
        return PublicPushBroadcast::query()
            ->whereNotIn('status', ['failed', 'cancelled'])
            ->whereBetween('scheduled_at', [
                $sendAt->copy()->subHours(2),
                $sendAt->copy()->addHours(2),
            ])
            ->count() >= 2;
    }
}

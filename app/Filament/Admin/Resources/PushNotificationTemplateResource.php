<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PushNotificationTemplateResource\Pages;
use App\Models\PushNotificationTemplate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class PushNotificationTemplateResource extends Resource
{
    protected static ?string $model = PushNotificationTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|UnitEnum|null $navigationGroup = 'Notification Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nama Template')
                    ->helperText('Nama ini hanya untuk admin, supaya mudah mengenali template.')
                    ->required()
                    ->maxLength(255),
                Select::make('slug')
                    ->label('Trigger Saat Event')
                    ->options([
                        'order_created' => 'Saat order dibuat',
                        'payment_success' => 'Saat pembayaran berhasil',
                        'order_success' => 'Saat pesanan sukses',
                    ])
                    ->helperText('Pilih kapan notifikasi otomatis ini akan dikirim oleh sistem.')
                    ->required()
                    ->native(false)
                    ->unique(ignoreRecord: true),
                TextInput::make('title')
                    ->label('Judul Notifikasi')
                    ->helperText('Judul yang muncul di notifikasi user.')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('body')
                    ->label('Isi Pesan')
                    ->helperText('Isi pesan yang muncul di notifikasi. Kamu bisa memakai variabel seperti {product} atau {display_order_id}.')
                    ->required()
                    ->columnSpanFull()
                    ->rows(4)
                    ->maxLength(500),
                Placeholder::make('variable_guide')
                    ->label('Panduan Variabel')
                    ->content(new HtmlString('
                        <div style="line-height:1.65">
                            <strong>Variabel yang bisa dipakai di Judul Notifikasi dan Isi Pesan:</strong><br>
                            <code>{order_id}</code> = ID order asli<br>
                            <code>{display_order_id}</code> = ID order/invoice yang tampil ke user<br>
                            <code>{product}</code> = nama produk yang dibeli<br>
                            <code>{amount}</code> = nominal transaksi<br>
                            <code>{nickname}</code> = nickname tujuan jika tersedia<br>
                            <code>{status}</code> = status pesanan<br>
                            <code>{sn}</code> = serial number/voucher jika tersedia
                        </div>
                    '))
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Trigger Event')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'order_created' => 'Saat order dibuat',
                        'payment_success' => 'Saat pembayaran berhasil',
                        'order_success' => 'Saat pesanan sukses',
                        default => $state ?? '-',
                    })
                    ->searchable()
                    ->sortable()
                    ->color('gray'),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('body')
                    ->label('Isi Pesan')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->body),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPushNotificationTemplates::route('/'),
            'create' => Pages\CreatePushNotificationTemplate::route('/create'),
            'edit' => Pages\EditPushNotificationTemplate::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PushNotificationTemplateResource\Pages;
use App\Models\PushNotificationTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;

class PushNotificationTemplateResource extends Resource
{
    protected static ?string $model = PushNotificationTemplate::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static UnitEnum | string | null $navigationGroup = 'Notification Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Template')
                    ->helperText('Nama ini hanya untuk admin, supaya mudah mengenali template.')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('slug')
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
                Forms\Components\TextInput::make('title')
                    ->label('Judul Notifikasi')
                    ->helperText('Judul yang muncul di notifikasi user.')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('body')
                    ->label('Isi Pesan')
                    ->helperText('Isi pesan yang muncul di notifikasi. Kamu bisa memakai variabel seperti {product} atau {display_order_id}.')
                    ->required()
                    ->columnSpanFull()
                    ->rows(4)
                    ->maxLength(500),
                Forms\Components\Placeholder::make('variable_guide')
                    ->label('Panduan Variabel')
                    ->content(new \Illuminate\Support\HtmlString('
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
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
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
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('body')
                    ->label('Isi Pesan')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->body),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
                Tables\Columns\TextColumn::make('updated_at')
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

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WhatsappTemplateResource\Pages;
use App\Models\WhatsappTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use BackedEnum;

class WhatsappTemplateResource extends Resource
{
    protected static ?string $model = WhatsappTemplate::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

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
                    ->label('Trigger Pesan')
                    ->options([
                        'transaction_pending' => 'Saat transaksi menunggu pembayaran',
                        'transaction_success' => 'Saat pesanan sukses',
                        'transaction_failed' => 'Saat pesanan gagal / dibatalkan',
                    ])
                    ->helperText('Pilih kapan pesan WhatsApp otomatis ini dikirim oleh sistem.')
                    ->required()
                    ->native(false)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('content')
                    ->label('Isi Pesan WhatsApp')
                    ->helperText('Tulis pesan yang dikirim ke pelanggan. Kamu bisa memakai variabel seperti {order_id}, {product}, atau {sn}.')
                    ->required()
                    ->columnSpanFull()
                    ->rows(5),
                Forms\Components\Placeholder::make('variable_guide')
                    ->label('Panduan Variabel')
                    ->content(new \Illuminate\Support\HtmlString('
                        <div style="line-height:1.65">
                            <strong>Variabel yang bisa dipakai di Isi Pesan WhatsApp:</strong><br>
                            <code>{order_id}</code> = nomor invoice/order<br>
                            <code>{nickname}</code> = nickname tujuan jika tersedia<br>
                            <code>{product}</code> = nama produk yang dibeli<br>
                            <code>{amount}</code> = nominal transaksi<br>
                            <code>{status}</code> = status pesanan<br>
                            <code>{sn}</code> = serial number/voucher jika tersedia<br>
                            <code>{reason}</code> = alasan gagal/batal jika tersedia
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
                    ->label('Trigger Pesan')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'transaction_pending' => 'Menunggu pembayaran',
                        'transaction_success' => 'Pesanan sukses',
                        'transaction_failed' => 'Gagal / dibatalkan',
                        default => $state ?? '-',
                    })
                    ->searchable()
                    ->sortable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('content')
                    ->label('Isi Pesan')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->content),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsappTemplates::route('/'),
            'create' => Pages\CreateWhatsappTemplate::route('/create'),
            'edit' => Pages\EditWhatsappTemplate::route('/{record}/edit'),
        ];
    }
}

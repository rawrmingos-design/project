<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
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

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-envelope';

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
                    ->label('Trigger Email')
                    ->options([
                        'transaction_pending' => 'Saat transaksi menunggu pembayaran',
                        'transaction_success' => 'Saat pesanan sukses',
                        'transaction_failed' => 'Saat pesanan gagal / dibatalkan',
                    ])
                    ->helperText('Pilih kapan email otomatis ini dikirim oleh sistem.')
                    ->required()
                    ->native(false)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('subject')
                    ->label('Judul Email')
                    ->helperText('Judul yang muncul di inbox pelanggan. Bisa memakai variabel seperti {order_id}.')
                    ->required()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('content')
                    ->label('Isi Email')
                    ->helperText('Tulis isi email yang dikirim ke pelanggan. Variabel bisa dipakai di judul dan isi email.')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('variable_guide')
                    ->label('Panduan Variabel')
                    ->content(new \Illuminate\Support\HtmlString('
                        <div style="line-height:1.65">
                            <strong>Variabel yang bisa dipakai di Judul Email dan Isi Email:</strong><br>
                            <code>{order_id}</code> = nomor invoice/order<br>
                            <code>{nickname}</code> = nickname tujuan jika tersedia<br>
                            <code>{product}</code> = nama produk yang dibeli<br>
                            <code>{amount}</code> = nominal transaksi<br>
                            <code>{status}</code> = status pesanan<br>
                            <code>{sn}</code> = serial number/voucher jika tersedia<br>
                            <code>{note}</code> = catatan atau alasan tambahan jika tersedia
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
                    ->label('Trigger Email')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'transaction_pending' => 'Menunggu pembayaran',
                        'transaction_success' => 'Pesanan sukses',
                        'transaction_failed' => 'Gagal / dibatalkan',
                        default => $state ?? '-',
                    })
                    ->searchable()
                    ->sortable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Judul Email')
                    ->searchable()
                    ->limit(40),
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
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}

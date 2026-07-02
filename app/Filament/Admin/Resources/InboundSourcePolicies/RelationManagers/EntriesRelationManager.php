<?php

namespace App\Filament\Admin\Resources\InboundSourcePolicies\RelationManagers;

use App\Models\InboundSourceEntry;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $recordTitleAttribute = 'value';

    protected static ?string $title = 'Daftar IP Diizinkan';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('value')
                    ->label('IP / CIDR')
                    ->placeholder('203.0.113.10 atau 203.0.113.0/24')
                    ->helperText('Masukkan IP tunggal atau range CIDR dari provider/gateway.')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('value_type', InboundSourceEntry::detectValueType($state) ?? 'ipv4'))
                    ->rules([
                        fn () => function (string $attribute, $value, \Closure $fail): void {
                            if (! InboundSourceEntry::isValidValue($value)) {
                                $fail('Masukkan IP atau CIDR yang valid. Contoh: 203.0.113.10 atau 203.0.113.0/24.');
                            }
                        },
                    ])
                    ->required()
                    ->maxLength(255),
                Select::make('value_type')
                    ->label('Jenis IP')
                    ->options([
                        'ipv4' => 'IPv4',
                        'ipv6' => 'IPv6',
                        'cidr_ipv4' => 'CIDR IPv4',
                        'cidr_ipv6' => 'CIDR IPv6',
                    ])
                    ->default('ipv4')
                    ->disabled()
                    ->dehydrated(false)
                    ->native(false)
                    ->helperText('Otomatis terdeteksi dari format IP / CIDR.'),
                TextInput::make('label')
                    ->label('Label')
                    ->placeholder('Contoh: IP utama TriPay')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                DateTimePicker::make('last_verified_at')
                    ->label('Terakhir Diverifikasi')
                    ->seconds(false),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value')
                    ->label('IP / CIDR')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('value_type')
                    ->label('Jenis IP')
                    ->badge(),
                TextColumn::make('label')
                    ->label('Label')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Aktif'),
                TextColumn::make('last_verified_at')
                    ->label('Terverifikasi')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->label('Update')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Tambah IP'),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->label('Edit'),
                Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

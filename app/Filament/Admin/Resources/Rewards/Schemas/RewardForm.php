<?php

namespace App\Filament\Admin\Resources\Rewards\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RewardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Hadiah')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Hadiah')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('category')
                            ->label('Kategori')
                            ->maxLength(255)
                            ->placeholder('mis. Mouse, Headset, Aksesoris')
                            ->helperText('Kelompok hadiah untuk filter di katalog.'),

                        FileUpload::make('image')
                            ->label('Gambar')
                            ->image()
                            ->disk('public')
                            ->directory('rewards')
                            ->maxSize(2048)
                            ->helperText('Maks 2MB. Disarankan rasio 1:1.'),

                        Textarea::make('description')
                            ->label('Deskripsi / Spesifikasi')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Tulis spesifikasi hadiah di sini (akan tampil ke member).'),
                    ])
                    ->collapsible(),

                Section::make('Poin & Stok')
                    ->columns(3)
                    ->schema([
                        TextInput::make('point_cost')
                            ->label('Harga Poin')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffix('poin')
                            ->helperText('Patokan aman: (modal barang + ongkir) ÷ 25.'),

                        TextInput::make('stock')
                            ->label('Stok')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        TextInput::make('weight_gram')
                            ->label('Berat')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('gram')
                            ->helperText('Opsional (info ongkir).'),
                    ])
                    ->collapsible(),

                Section::make('Pengaturan')
                    ->columns(2)
                    ->schema([
                        Select::make('min_tier')
                            ->label('Khusus Tier')
                            ->options([
                                'Gold'     => 'Gold ke atas',
                                'Platinum' => 'Platinum saja',
                            ])
                            ->placeholder('Semua member')
                            ->helperText('Kosongkan = semua member boleh menukar.'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->inline(false),

                        DateTimePicker::make('starts_at')
                            ->label('Mulai')
                            ->seconds(false)
                            ->native(false)
                            ->helperText('Opsional. Kosongkan jika langsung aktif.'),

                        DateTimePicker::make('expires_at')
                            ->label('Berakhir')
                            ->seconds(false)
                            ->native(false)
                            ->helperText('Opsional. Kosongkan jika tanpa batas.'),

                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0)
                            ->helperText('Makin kecil makin atas.'),
                    ])
                    ->collapsible(),
            ]);
    }
}

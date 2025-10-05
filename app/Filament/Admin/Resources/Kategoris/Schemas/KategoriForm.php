<?php

namespace App\Filament\Admin\Resources\Kategoris\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;

class KategoriForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dasar')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('sub_nama')
                            ->label('Sub Nama')
                            ->required()
                            ->maxLength(225),
                            
                        TextInput::make('kode')
                            ->label('Kode')
                            ->maxLength(255),
                            
                        Select::make('tipe')
                            ->label('Tipe')
                            ->options([
                                'game' => 'Game',
                                'voucher' => 'Voucher',
                                'pulsa' => 'Pulsa',
                                'data' => 'Data',
                            ])
                            ->default('game')
                            ->required(),
                            
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
                    
                Section::make('Media')
                    ->schema([
                        FileUpload::make('thumbnail')
                            ->label('Thumbnail')
                            ->image()
                            ->disk('asset')
                            ->directory('kategoris/thumbnails')
                            ->visibility('public'),
                            
                        FileUpload::make('banner')
                            ->label('Banner')
                            ->image()
                            ->disk('asset')
                            ->directory('kategoris/banners')
                            ->visibility('public'),
                    ])
                    ->columns(2),
                    
                Section::make('Konfigurasi')
                    ->schema([
                        Textarea::make('brand')
                            ->label('Brand')
                            ->rows(3),
                            
                        Toggle::make('server_id')
                            ->label('Memerlukan Server ID')
                            ->default(false),
                            
                        TextInput::make('petunjuk')
                            ->label('Petunjuk')
                            ->maxLength(255),
                            
                        TextInput::make('keterangan_input_satu')
                            ->label('Keterangan Input 1')
                            ->maxLength(255),
                    ]),
                    
                Section::make('Deskripsi')
                    ->schema([
                        Textarea::make('deskripsi_game')
                            ->label('Deskripsi Game')
                            ->rows(4),
                            
                        Textarea::make('deskripsi_field')
                            ->label('Deskripsi Field')
                            ->rows(4),
                    ])
                    ->columns(1),
            ]);
    }
}

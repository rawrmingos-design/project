<?php

namespace App\Filament\Admin\Resources\Kategoris\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;


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
                                'app' => 'App',
                                'vilogml' => 'VilogML',
                                'joki' => 'Joki',
                                'populer' => 'Populer',
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
                            ->disk('assets')
                            ->directory('thumbnails')
                            ->visibility('public')
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left'),
                            
                        FileUpload::make('banner')
                            ->label('Banner')
                            ->image()
                            ->disk('assets')
                            ->directory('banners')
                            ->visibility('public')
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('3:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left'),
                    ])
                    ->columns(2),
                    
                Section::make('Konfigurasi')
                    ->schema([
                            
                        Toggle::make('server_id')
                            ->label('Memerlukan Server ID')
                            ->default(false),
                    ]),
                    
                Section::make('Deskripsi')
                    ->schema([
                        RichEditor::make('deskripsi_game')
                            ->label('Deskripsi Game')
                            ->required(),
                            
                        RichEditor::make('deskripsi_field')
                            ->label('Deskripsi Field')
                            ->required(),
                    ])
                    ->columns(1),
            ]);
    }
}

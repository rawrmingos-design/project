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
                            ->maxLength(255)
                            ->helperText('Contoh: Mobile Legends'),
                            
                        TextInput::make('sub_nama')
                            ->label('Sub Nama')
                            ->required()
                            ->maxLength(225)
                            ->helperText('Contoh: Moonton'),
                            
                        TextInput::make('kode')
                            ->label('Kode')
                            ->maxLength(255)
                            ->helperText('Kode unik, contoh: mlbb'),
                            
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
                            ->required()
                            ->helperText('Jenis kategori produk'),

                        Select::make('category_type_id')
                            ->label('Tab Kategori (Category Sequence)')
                            ->relationship('categoryType', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()->maxLength(255),
                                TextInput::make('slug')
                                    ->required()->maxLength(255),
                                TextInput::make('sort')
                                    ->numeric()->default(0),
                            ]),
                            
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
                            ->directory('assets/thumbnail')
                            ->visibility('public')
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->required(),
                            
                        FileUpload::make('banner')
                            ->label('Banner')
                            ->image()
                            ->disk('assets')
                            ->directory('assets/banner_game')
                            ->visibility('public')
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('3:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                    ])
                    ->columns(2),
                    
                Section::make('Konfigurasi')
                    ->schema([
                            
                        Toggle::make('server_id')
                            ->label('Memerlukan Server ID')
                            ->default(false),

                        Toggle::make('require_user_id')
                            ->label('Wajib Mengisi User ID')
                            ->helperText('Nonaktifkan jika produk tidak memerlukan ID game pelanggan. Contoh: Roblox (kode redeem), Google Play Gift Card.')
                            ->default(true),
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

                Section::make('Advanced SEO Configuration')
                    ->description('Optimasi SEO manual untuk halaman games ini. Kosongkan jika ingin auto-generated.')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->placeholder('Ex: Top Up Mobile Legends Murah & Cepat - ' . config('app.name'))
                            ->maxLength(255),
                            
                        Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->placeholder('Ex: Beli Diamond MLBB termurah dengan proses instan 24 jam...')
                            ->rows(3),
                            
                        Textarea::make('schema_markup')
                            ->label('Custom Schema Markup (JSON-LD)')
                            ->placeholder('<script type="application/ld+json">{ ... }</script>')
                            ->rows(5)
                            ->helperText('Pastikan format JSON valid. Sertakan tag <script> jika perlu.'),
                    ])
                    ->collapsible(),
            ]);
    }
}

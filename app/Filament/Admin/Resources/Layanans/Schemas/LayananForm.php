<?php

namespace App\Filament\Admin\Resources\Layanans\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DateTimePicker;
use App\Models\Kategori;

class LayananForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Layanan')
                    ->schema([
                        Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(Kategori::pluck('nama', 'id'))
                            ->required()
                            ->searchable(),
                            
                        TextInput::make('layanan')
                            ->label('Nama Layanan')
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('provider_id')
                            ->label('Provider ID')
                            ->required()
                            ->maxLength(255),
                            
                        Select::make('provider')
                            ->label('Provider')
                            ->options([
                                'digiflazz' => 'Digiflazz',
                                'apigames' => 'API Games',
                                'vip' => 'VIP Reseller',
                                'manual' => 'Manual',
                            ])
                            ->required(),
                            
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'maintenance' => 'Maintenance',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2),
                    
                Section::make('Harga')
                    ->schema([
                        TextInput::make('harga')
                            ->label('Harga Normal')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),
                            
                        TextInput::make('harga_member')
                            ->label('Harga Member')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),
                            
                        TextInput::make('harga_platinum')
                            ->label('Harga Platinum')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),
                            
                        TextInput::make('harga_gold')
                            ->label('Harga Gold')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),
                    ])
                    ->columns(2),
                    
                Section::make('Profit')
                    ->schema([
                        TextInput::make('profit')
                            ->label('Profit Normal')
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                            
                        TextInput::make('profit_member')
                            ->label('Profit Member')
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                            
                        TextInput::make('profit_platinum')
                            ->label('Profit Platinum')
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                            
                        TextInput::make('profit_gold')
                            ->label('Profit Gold')
                            ->numeric()
                            ->required()
                            ->suffix('%'),
                    ])
                    ->columns(2),
                    
                Section::make('Flash Sale')
                    ->schema([
                        Toggle::make('is_flash_sale')
                            ->label('Aktifkan Flash Sale')
                            ->reactive(),
                            
                        TextInput::make('harga_flash_sale')
                            ->label('Harga Flash Sale')
                            ->numeric()
                            ->prefix('Rp')
                            ->visible(fn ($get) => $get('is_flash_sale')),
                            
                        TextInput::make('judul_flash_sale')
                            ->label('Judul Flash Sale')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('is_flash_sale')),
                            
                        TextInput::make('stock_flash_sale')
                            ->label('Stock Flash Sale')
                            ->numeric()
                            ->visible(fn ($get) => $get('is_flash_sale')),
                            
                        DateTimePicker::make('expired_flash_sale')
                            ->label('Expired Flash Sale')
                            ->visible(fn ($get) => $get('is_flash_sale')),
                            
                        FileUpload::make('banner_flash_sale')
                            ->label('Banner Flash Sale')
                            ->image()
                            ->directory('flash-sale/banners')
                            ->visible(fn ($get) => $get('is_flash_sale')),
                    ])
                    ->columns(2),
                    
                Section::make('Lainnya')
                    ->schema([
                        FileUpload::make('product_logo')
                            ->label('Logo Produk')
                            ->image()
                            ->directory('products/logos'),
                            
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(4),
                    ])
                    ->columns(1),
            ]);
    }
}

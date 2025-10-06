<?php

namespace App\Filament\Admin\Resources\Produks\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use App\Models\Kategori;

class ProdukForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Informasi Produk')
                    ->schema([
                        Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(Kategori::pluck('nama', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('nama')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('sub_nama')
                                    ->required()
                                    ->maxLength(225),
                            ]),
                            
                        TextInput::make('layanan')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('provider_id', strtoupper(str_replace(' ', '_', $state)));
                            }),
                            
                        TextInput::make('provider_id')
                            ->label('Provider ID')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                            
                        Grid::make(2)
                            ->schema([
                                Select::make('provider')
                                    ->label('Provider')
                                    ->options([
                                        'digiflazz' => 'Digiflazz',
                                        'apigames' => 'API Games',
                                        'vip' => 'VIP Reseller',
                                        'bangjeff' => 'BangJeff',
                                        'topupedia' => 'Topupedia',
                                        'manual' => 'Manual',
                                    ])
                                    ->required()
                                    ->live(),
                                    
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'maintenance' => 'Maintenance',
                                        'out_of_stock' => 'Out of Stock',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),
                    ])
                    ->columnSpan(2),
                    
                Section::make('Media')
                    ->schema([
                        FileUpload::make('product_logo')
                            ->label('Logo Produk')
                            ->image()
                            ->disk('assets')
                            ->directory('products/logos')
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left'),
                            
                        Placeholder::make('preview')
                            ->label('Current Logo')
                            ->content(fn ($record) => $record?->product_logo ? 
                                new \Illuminate\Support\HtmlString('<img src="' . asset('assets/' . $record->product_logo) . '" class="w-32 h-32 object-cover rounded-lg">') : 
                                'No logo uploaded'
                            )
                            ->visible(fn ($record) => $record !== null),
                    ])
                    ->columnSpan(1),
                    
                Section::make('Pricing & Profit')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('harga')
                                    ->label('Harga Normal')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $profit = $get('profit') ?? 0;
                                        if ($state && $profit) {
                                            $memberPrice = $state - ($state * $profit / 100);
                                            $set('harga_member', $memberPrice);
                                        }
                                    }),
                                    
                                TextInput::make('profit')
                                    ->label('Profit (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $basePrice = $get('harga') ?? 0;
                                        if ($basePrice && $state) {
                                            $memberPrice = $basePrice - ($basePrice * $state / 100);
                                            $set('harga_member', $memberPrice);
                                        }
                                    }),
                            ]),
                            
                        Grid::make(3)
                            ->schema([
                                TextInput::make('harga_member')
                                    ->label('Harga Member')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp')
                                    ->readOnly(),
                                    
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
                            ]),
                            
                        Grid::make(3)
                            ->schema([
                                TextInput::make('profit_member')
                                    ->label('Profit Member (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%'),
                                    
                                TextInput::make('profit_platinum')
                                    ->label('Profit Platinum (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%'),
                                    
                                TextInput::make('profit_gold')
                                    ->label('Profit Gold (%)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%'),
                            ]),
                    ])
                    ->columnSpan(3),
                    
                Section::make('Flash Sale Configuration')
                    ->schema([
                        Toggle::make('is_flash_sale')
                            ->label('Enable Flash Sale')
                            ->live(),
                            
                        Grid::make(3)
                            ->schema([
                                TextInput::make('harga_flash_sale')
                                    ->label('Flash Sale Price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->visible(fn ($get) => $get('is_flash_sale')),
                                    
                                TextInput::make('stock_flash_sale')
                                    ->label('Flash Sale Stock')
                                    ->numeric()
                                    ->visible(fn ($get) => $get('is_flash_sale')),
                                    
                                DateTimePicker::make('expired_flash_sale')
                                    ->label('Flash Sale Expires')
                                    ->visible(fn ($get) => $get('is_flash_sale')),
                            ]),
                            
                        TextInput::make('judul_flash_sale')
                            ->label('Flash Sale Title')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('is_flash_sale')),
                            
                        FileUpload::make('banner_flash_sale')
                            ->label('Flash Sale Banner')
                            ->image()
                            ->disk('assets')
                            ->directory('flash-sale/banners')
                            ->imagePreviewHeight('100')
                            ->panelAspectRatio('3:1')
                            ->panelLayout('integrated')
                            ->visible(fn ($get) => $get('is_flash_sale')),
                    ])
                    ->columnSpan(3)
                    ->collapsible(),
                    
                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('catatan')
                            ->label('Catatan/Deskripsi')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(3)
                    ->collapsible(),
            ]);
    }
}

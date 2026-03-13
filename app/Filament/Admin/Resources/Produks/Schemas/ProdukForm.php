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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use App\Models\Kategori;
use App\Models\PaketLayanan;

class ProdukForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Informasi Produk')
                    ->columns(2)
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
                            ])
                            ->columnSpanFull(),
                            
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
                            })
                            ->columnSpanFull(),
                            
                        TextInput::make('provider_id')
                            ->label('Provider ID')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Contoh: ml5')
                            ->columnSpanFull(),
                            
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
                    ])
                    ->columnSpan(2),
                    
                Section::make('Media')
                    ->schema([
                        FileUpload::make('product_logo')
                            ->label('Logo Produk')
                            ->image()
                            ->disk('assets')
                            ->directory('assets/product_logo')
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->formatStateUsing(function ($state, $record) {
                                if (!empty($state)) {
                                    return $state;
                                }

                                if (!$record) {
                                    return null;
                                }

                                return PaketLayanan::where('layanan_id', $record->id)->value('product_logo');
                            }),
                            
                        Placeholder::make('preview_status')
                        ->label('Status File')
                         ->content(function ($record) {
                            if (!$record) {
                                return 'No record';
                            }

                            $logoPath = $record->product_logo
                                ?: PaketLayanan::where('layanan_id', $record->id)->value('product_logo');

                            if (!$logoPath) {
                                return 'No path in DB';
                            }

                            $normalizedPath = ltrim($logoPath, '/');
                
                            // Cek apakah file benar-benar ada di disk assets
                            $exists = \Storage::disk('assets')->exists($normalizedPath);
                             return $exists ? '✅ File exists on server' : '❌ File NOT found at: ' . $logoPath;
                        }),
                    ])
                    ->columnSpan(1),
                    
                Section::make('Pricing & Profit')
                    ->columns([
                        'sm' => 2,
                        'lg' => 3,
                    ])
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
                    ])
                    ->columnSpan(3),
                    
                Section::make('Flash Sale Configuration')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_flash_sale')
                            ->label('Enable Flash Sale')
                            ->live()
                            ->columnSpanFull(),
                            
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
                            
                        TextInput::make('judul_flash_sale')
                            ->label('Flash Sale Title')
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('is_flash_sale'))
                            ->columnSpanFull(),
                            
                        FileUpload::make('banner_flash_sale')
                            ->label('Flash Sale Banner')
                            ->image()
                            ->disk('assets')
                            ->directory('flash-sale/banners')
                            ->imagePreviewHeight('100')
                            ->panelAspectRatio('3:1')
                            ->panelLayout('integrated')
                            ->visible(fn ($get) => $get('is_flash_sale'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(3)
                    ->collapsible(),
                    

                Section::make('Multi-Provider Sources')
                    ->columns(1)
                    ->columnSpan(3)
                    ->description('Atur sumber provider untuk layanan ini. Sistem akan otomatis memilih harga termurah & status available dari list ini.')
                    ->schema([
                        Repeater::make('provider_paths')
                            ->relationship()
                            ->label('Provider Sources')
                            ->schema([
                                Grid::make()
                                    ->columns([
                                        'default' => 1,
                                        'sm' => 2,
                                        'xl' => 4,
                                    ])
                                    ->schema([
                                        Select::make('provider_code')
                                            ->label('Provider')
                                            ->options([
                                                'digiflazz' => 'Digiflazz',
                                                'bangjeff' => 'BangJeff',
                                                'vip' => 'VIP Reseller',
                                                'apigames' => 'API Games',
                                                'manual' => 'Manual / Joki',
                                            ])
                                            ->required(),

                                        TextInput::make('provider_sku')
                                            ->label('Kode SKU Provider')
                                            ->required(),

                                        TextInput::make('modal_price')
                                            ->label('Harga Modal')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->required(),
                                            
                                        TextInput::make('priority')
                                            ->label('Prioritas')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->helperText('1 = Utama'),
                                    ]),
                                    
                                Grid::make(1)
                                    ->schema([
                                        Select::make('status')
                                            ->options([
                                                'available' => 'Available',
                                                'empty' => 'Kosong',
                                                'maintenance' => 'Gangguan',
                                                'error' => 'Error',
                                            ])
                                            ->default('available')
                                            ->required(),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['provider_code'] ?? '') . ' - ' . ($state['provider_sku'] ?? '')),
                    ]),

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

<?php

namespace App\Filament\Admin\Resources\Produks\Schemas;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\Kategori;
use App\Models\PaketLayanan;
use App\Filament\Admin\Resources\Kategoris\Schemas\KategoriForm;
use App\Filament\Admin\Resources\Pakets\Schemas\PaketForm;
use App\Http\Controllers\DigiFlazzController;
use App\Http\Controllers\provider\VipResellerController;
use App\Services\Providers\BangJeffService;
use App\Models\MediaAsset;
use App\Support\KategoriFormDataHandler;
use App\Support\MediaAssetPicker;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

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
                            ->relationship('kategori', 'nama')
                            ->required()
                            ->searchable()
                            ->createOptionForm(KategoriForm::getFormComponents())
                            ->createOptionUsing(fn (array $data): int => app(KategoriFormDataHandler::class)->create($data)->getKey())
                            ->createOptionModalHeading('Buat Kategori Baru')
                            ->columnSpanFull(),
                            
                        TextInput::make('layanan')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, $set, $get) {
                                if ($operation !== 'create') {
                                    return;
                                }

                                if (in_array($get('provider'), ['digiflazz', 'bangjeff', 'vip', 'apigames'], true)) {
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
                            ->options(fn () => \App\Models\Provider::pluck('name', 'code')->toArray())
                            ->required()
                            ->live()
                            ->dehydrated()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state !== 'digiflazz') {
                                    $set('digiflazz_category_filter', null);
                                    $set('digiflazz_brand_filter', null);
                                    $set('digiflazz_product', null);
                                }

                                if ($state !== 'bangjeff') {
                                    $set('bangjeff_product_code_filter', null);
                                    $set('bangjeff_variant', null);
                                }

                                if ($state !== 'vip') {
                                    $set('vip_reseller_tab', null);
                                    $set('vip_reseller_game_filter', null);
                                    $set('vip_reseller_status_filter', 'available');
                                    $set('vip_reseller_service', null);
                                }

                                if ($state !== 'sufpayment') {
                                    $set('sufpayment_category_filter', null);
                                    $set('sufpayment_product', null);
                                }
                            }),

                        Select::make('sufpayment_category_filter')
                            ->label('Filter Kategori SufPayment')
                            ->options(fn (): array => static::getSufPaymentCategoryOptions())
                            ->placeholder('Semua kategori')
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'sufpayment')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('sufpayment_product', null);
                            }),

                        Select::make('sufpayment_product')
                            ->label('Pilih Produk SufPayment')
                            ->helperText('Cari produk SufPayment lalu pilih untuk mengisi nama produk, Provider ID, status, dan harga modal otomatis.')
                            ->searchable()
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'sufpayment')
                            ->getSearchResultsUsing(fn (string $search, Get $get): array => static::getSufPaymentProductSearchResults(
                                $search,
                                $get('sufpayment_category_filter'),
                            ))
                            ->getOptionLabelUsing(fn ($value): ?string => static::getSufPaymentProductOptionLabel($value))
                            ->hintAction(
                                Action::make('refreshSufPaymentCache')
                                    ->label('Refresh Cache')
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(function (Set $set) {
                                        static::refreshSufPaymentCache();
                                        $set('sufpayment_product', null);

                                        Notification::make()
                                            ->title('Pricelist SufPayment diperbarui')
                                            ->success()
                                            ->send();
                                    })
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::applySufPaymentProductSelection($state, $set, $get);
                            })
                            ->columnSpanFull(),

                        Select::make('digiflazz_category_filter')
                            ->label('Filter Kategori DigiFlazz')
                            ->options(fn (): array => static::getDigiflazzCategoryOptions())
                            ->placeholder('Semua kategori')
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'digiflazz')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('digiflazz_brand_filter', null);
                                $set('digiflazz_product', null);
                            }),

                        Select::make('digiflazz_brand_filter')
                            ->label('Filter Brand DigiFlazz')
                            ->options(fn (Get $get): array => static::getDigiflazzBrandOptions($get('digiflazz_category_filter')))
                            ->placeholder('Semua brand')
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'digiflazz')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('digiflazz_product', null);
                            }),

                        Select::make('digiflazz_product')
                            ->label('Pilih Produk DigiFlazz')
                            ->helperText('Cari produk DigiFlazz lalu pilih untuk mengisi nama produk, Provider ID, status, dan harga modal otomatis.')
                            ->searchable()
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'digiflazz')
                            ->getSearchResultsUsing(fn (string $search, Get $get): array => static::getDigiflazzProductSearchResults(
                                $search,
                                $get('digiflazz_category_filter'),
                                $get('digiflazz_brand_filter'),
                            ))
                            ->getOptionLabelUsing(fn ($value): ?string => static::getDigiflazzProductOptionLabel($value))
                            ->hintAction(
                                Action::make('refreshDigiflazzCache')
                                    ->label('Refresh Cache')
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(function (Set $set) {
                                        static::refreshDigiflazzCache();
                                        $set('digiflazz_brand_filter', null);
                                        $set('digiflazz_product', null);

                                        Notification::make()
                                            ->title('Pricelist DigiFlazz diperbarui')
                                            ->success()
                                            ->send();
                                    })
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::applyDigiflazzProductSelection($state, $set, $get);
                            })
                            ->columnSpanFull(),

                        Select::make('bangjeff_product_code_filter')
                            ->label('Filter Produk BangJeff')
                            ->options(fn (): array => static::getBangJeffProductCodeOptions())
                            ->placeholder('Pilih productCode...')
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'bangjeff')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('bangjeff_variant', null);
                            }),

                        Select::make('bangjeff_variant')
                            ->label('Pilih Variant BangJeff')
                            ->helperText('Pilih variant dari BangJeff untuk mengisi nama produk, Provider ID, status, dan harga modal otomatis.')
                            ->options(fn (Get $get): array => static::getBangJeffVariantOptions($get('bangjeff_product_code_filter')))
                            ->searchable()
                            ->dehydrated(false)
                            ->disabled(fn (Get $get) => blank($get('bangjeff_product_code_filter')))
                            ->visible(fn (Get $get) => $get('provider') === 'bangjeff')
                            ->hintAction(
                                Action::make('refreshBangJeffCache')
                                    ->label('Refresh Cache')
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(function (Set $set, Get $get) {
                                        static::refreshBangJeffCache($get('bangjeff_product_code_filter'));
                                        $set('bangjeff_variant', null);

                                        Notification::make()
                                            ->title('Variant BangJeff diperbarui')
                                            ->success()
                                            ->send();
                                    })
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::applyBangJeffVariantSelection($state, $set, $get);
                            })
                            ->columnSpanFull(),

                        Select::make('vip_reseller_tab')
                            ->label('Tab VIP Reseller')
                            ->options([
                                'game_streaming' => 'Game & Streaming',
                                'sosmed' => 'Sosmed',
                            ])
                            ->placeholder('Pilih tab...')
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'vip')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('vip_reseller_game_filter', null);
                                $set('vip_reseller_service', null);
                            }),

                        Select::make('vip_reseller_game_filter')
                            ->label('Filter Kategori VIP Reseller')
                            ->options(fn (Get $get): array => static::getVipResellerGameOptions($get('vip_reseller_tab')))
                            ->placeholder('Pilih kategori...')
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'vip')
                            ->disabled(fn (Get $get) => blank($get('vip_reseller_tab')))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('vip_reseller_service', null);
                            }),

                        Select::make('vip_reseller_status_filter')
                            ->label('Filter Status VIP')
                            ->options([
                                'available' => 'Available',
                                'empty' => 'Empty',
                            ])
                            ->default('available')
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'vip')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('vip_reseller_service', null);
                            }),

                        Select::make('vip_reseller_service')
                            ->label('Pilih Layanan VIP Reseller')
                            ->helperText('Cari layanan VIP untuk mengisi nama produk, Provider ID, status, dan harga modal otomatis.')
                            ->searchable()
                            ->dehydrated(false)
                            ->visible(fn (Get $get) => $get('provider') === 'vip')
                            ->disabled(fn (Get $get) => blank($get('vip_reseller_game_filter')))
                            ->getSearchResultsUsing(fn (string $search, Get $get): array => static::getVipResellerServiceSearchResults(
                                $search,
                                $get('vip_reseller_game_filter'),
                                $get('vip_reseller_status_filter'),
                            ))
                            ->getOptionLabelUsing(fn ($value): ?string => static::getVipResellerServiceOptionLabel($value))
                            ->hintAction(
                                Action::make('refreshVipResellerCache')
                                    ->label('Refresh Cache')
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(function (Set $set, Get $get) {
                                        static::refreshVipResellerCache(
                                            $get('vip_reseller_game_filter'),
                                            $get('vip_reseller_status_filter'),
                                        );
                                        $set('vip_reseller_service', null);

                                        Notification::make()
                                            ->title('Layanan VIP Reseller diperbarui')
                                            ->success()
                                            ->send();
                                    })
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::applyVipResellerServiceSelection($state, $set, $get);
                            })
                            ->columnSpanFull(),
                            
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'available' => 'Available',
                                'inactive' => 'Inactive',
                                'maintenance' => 'Maintenance',
                                'out_of_stock' => 'Out of Stock',
                            ])
                            ->default('available')
                            ->required(),

                        Select::make('paket')
                            ->label('Masuk Ke Paket Layanan')
                            ->relationship('paket', 'nama')
                            ->multiple()
                            ->searchable()
                            ->createOptionForm(PaketForm::getFormComponents())
                            ->createOptionModalHeading('Buat Paket Baru')
                            ->helperText('Pilih satu atau lebih paket agar produk bisa langsung terhubung saat dibuat.')
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(2),
                    
                Section::make('Media')
                    ->schema([
                        Placeholder::make('product_logo_current_preview')
                            ->label('Gambar Aktif')
                            ->content(fn (?Model $record) => MediaAssetPicker::renderCurrentPreview($record, 'product_logo')),

                        Radio::make('product_logo_input_mode')
                            ->label('Sumber Gambar')
                            ->options([
                                'library' => 'Media Library',
                                'upload' => 'Upload Baru',
                            ])
                            ->default('upload')
                            ->inline()
                            ->inlineLabel(false)
                            ->live()
                            ->dehydrated(),

                        Hidden::make('product_logo_media_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Placeholder::make('product_logo_media_asset_picker')
                            ->label('Logo dari Media Library')
                            ->visible(fn (Get $get) => $get('product_logo_input_mode') === 'library')
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseProductLogoMediaAsset',
                                    'product_logo_media_asset_id',
                                    'Pilih dari Media Library',
                                    ['produk', 'logo', 'lainnya'],
                                    'produk',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearProductLogoMediaAsset',
                                    'product_logo_media_asset_id',
                                ),
                            ])
                            ->content(fn (Get $get, ?Model $record) => MediaAssetPicker::renderSelectedOrCurrentPreview(
                                $get('product_logo_media_asset_id'),
                                $record,
                                'product_logo',
                            )),

                        SpatieMediaLibraryFileUpload::make('product_logo')
                            ->label('Logo Produk')
                            ->image()
                            ->disk(config('uploads.disk', 'assets'))
                            ->collection('product_logo')
                            ->visible(fn (Get $get) => $get('product_logo_input_mode') === 'upload')
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('integrated')
                            ->previewable(false)
                            ->openable(false)
                            ->downloadable(false)
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->helperText('Upload baru otomatis disimpan ke Media Library dan tetap sinkron ke kolom logo lama.'),
                    ])
                    ->columnSpan(1),
                    
                Section::make('Pricing')
                    ->columns([
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->schema([
                        Hidden::make('pricing_sync_source')
                            ->dehydrated(false)
                            ->default(null),

                        TextInput::make('harga')
                            ->label('Harga Modal')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (filled($get('pricing_sync_source'))) {
                                    return;
                                }

                                static::runPricingSync('modal', $set, function () use ($state, $set, $get): void {
                                    static::syncTierPricesFromProfit($state, $set, $get);
                                });
                            }),
                            
                        TextInput::make('harga_member')
                            ->label('Harga Member / Publik')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (filled($get('pricing_sync_source'))) {
                                    return;
                                }

                                static::runPricingSync('harga_member', $set, function () use ($state, $set, $get): void {
                                    static::syncProfitFromTierPrice('member', $state, $set, $get);
                                });
                            }),

                        TextInput::make('profit_member')
                            ->label('Profit Member / Publik')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (filled($get('pricing_sync_source'))) {
                                    return;
                                }

                                static::runPricingSync('profit_member', $set, function () use ($get, $set): void {
                                    static::syncTierPricesFromProfit($get('harga'), $set, $get);
                                });
                            }),
                            
                        TextInput::make('harga_platinum')
                            ->label('Harga Platinum')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (filled($get('pricing_sync_source'))) {
                                    return;
                                }

                                static::runPricingSync('harga_platinum', $set, function () use ($state, $set, $get): void {
                                    static::syncProfitFromTierPrice('platinum', $state, $set, $get);
                                });
                            }),

                        TextInput::make('profit_platinum')
                            ->label('Profit Platinum')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (filled($get('pricing_sync_source'))) {
                                    return;
                                }

                                static::runPricingSync('profit_platinum', $set, function () use ($get, $set): void {
                                    static::syncTierPricesFromProfit($get('harga'), $set, $get);
                                });
                            }),
                            
                        TextInput::make('harga_gold')
                            ->label('Harga Gold')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (filled($get('pricing_sync_source'))) {
                                    return;
                                }

                                static::runPricingSync('harga_gold', $set, function () use ($state, $set, $get): void {
                                    static::syncProfitFromTierPrice('gold', $state, $set, $get);
                                });
                            }),

                            TextInput::make('profit_gold')
                            ->label('Profit Gold')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (filled($get('pricing_sync_source'))) {
                                    return;
                                }

                                static::runPricingSync('profit_gold', $set, function () use ($get, $set): void {
                                    static::syncTierPricesFromProfit($get('harga'), $set, $get);
                                });
                            }),
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
                            ->disk(config('uploads.disk', 'assets'))
                            ->directory('flash-sale/banners')
                            ->imagePreviewHeight('100')
                            ->panelAspectRatio('3:1')
                            ->panelLayout('integrated')
                            ->visible(fn ($get) => $get('is_flash_sale'))
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(3)
                    ->collapsible(),
                    

                Section::make('Check ID / Inquiry')
                    ->columns(2)
                    ->columnSpan(3)
                    ->description('Atur SKU khusus untuk cek ID/nickname. SKU ini tidak dipakai untuk fulfillment order.')
                    ->schema([
                        Toggle::make('check_id_enabled')
                            ->label('Aktifkan Check ID Provider')
                            ->live()
                            ->default(false)
                            ->helperText('Jika aktif, sistem memakai SKU inquiry ini saat validasi ID game untuk produk ini.')
                            ->afterStateUpdated(function (bool $state, Set $set): void {
                                if ($state) {
                                    $set('check_id_provider', 'digiflazz');

                                    return;
                                }

                                $set('check_id_provider', null);
                                $set('check_id_provider_sku', null);
                            }),

                        Select::make('check_id_provider')
                            ->label('Provider Check ID')
                            ->options([
                                'digiflazz' => 'Digiflazz',
                            ])
                            ->default('digiflazz')
                            ->visible(fn (Get $get) => (bool) $get('check_id_enabled'))
                            ->required(fn (Get $get) => (bool) $get('check_id_enabled'))
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state !== 'digiflazz') {
                                    $set('check_id_provider_sku', null);
                                }
                            }),

                        Select::make('check_id_provider_sku')
                            ->label('SKU Inquiry Digiflazz')
                            ->helperText('SKU ini dipakai hanya untuk cek ID/nickname, bukan untuk order fulfillment.')
                            ->searchable()
                            ->visible(fn (Get $get) => (bool) $get('check_id_enabled') && $get('check_id_provider') === 'digiflazz')
                            ->required(fn (Get $get) => (bool) $get('check_id_enabled') && $get('check_id_provider') === 'digiflazz')
                            ->getSearchResultsUsing(fn (string $search, Get $get): array => static::getDigiflazzProductSearchResults(
                                $search,
                                $get('digiflazz_category_filter'),
                                $get('digiflazz_brand_filter'),
                            ))
                            ->getOptionLabelUsing(fn ($value): ?string => static::getDigiflazzProductOptionLabel($value))
                            ->columnSpanFull(),
                    ])
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
                                            ->options(fn () => \App\Models\Provider::pluck('name', 'code')->toArray())
                                            ->required(),

                                        TextInput::make('provider_sku')
                                            ->label('Kode SKU Provider')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, Set $set) => $set('provider_sku', trim((string) $state)))
                                            ->required(),

                                        TextInput::make('modal_price')
                                            ->label('Harga Modal')
                                            ->numeric()
                                            ->minValue(0)
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
                                        Hidden::make('metadata')
                                            ->dehydrated(true),
                                        Placeholder::make('metadata_preview')
                                            ->label('Metadata Provider')
                                            ->visible(fn (Get $get) => filled($get('metadata')))
                                            ->content(fn (Get $get): string => static::formatProviderPathMetadataSummary($get('metadata'))),
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

    protected static function getDigiflazzProducts(): array
    {
        return Cache::remember('filament.digiflazz.pricelist', now()->addMinutes(5), function () {
            $response = app(DigiFlazzController::class)->harga();
            $products = $response['data'] ?? [];

            if (!is_array($products)) {
                return [];
            }

            return array_values(array_filter($products, fn ($product) => isset($product['buyer_sku_code'], $product['product_name'], $product['price'])));
        });
    }

    protected static function getSufPaymentProducts(): array
    {
        $cacheKey = 'filament.sufpayment.products';
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $settings = \App\Models\SettingWeb::query()->first();
            if (!$settings || blank($settings->sufpayment_api_key)) {
                return [];
            }

            $route = app(\App\Services\ProviderRoutingService::class)->resolveExplicitProvider('sufpayment', 'dummy');
            $response = (new \App\Services\Providers\SufPaymentService($route['credentials'] ?? []))->products();

            if (!($response['result'] ?? false)) {
                return [];
            }

            $products = is_array($response['data'] ?? null) ? $response['data'] : [];
            Cache::put($cacheKey, $products, now()->addMinutes(5));

            return $products;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected static function refreshSufPaymentCache(): void
    {
        Cache::forget('filament.sufpayment.products');
    }

    protected static function getSufPaymentCategoryOptions(): array
    {
        $products = static::getSufPaymentProducts();
        $categories = [];

        foreach ($products as $product) {
            $category = trim((string) ($product['category'] ?? $product['kategori'] ?? $product['game'] ?? $product['brand'] ?? ''));
            if ($category !== '') {
                $categories[$category] = $category;
            }
        }

        asort($categories);
        return $categories;
    }

    protected static function getSufPaymentProductSearchResults(string $search, ?string $categoryFilter = null): array
    {
        $products = static::getSufPaymentProducts();
        $options = [];

        $search = strtolower(trim($search));

        foreach ($products as $product) {
            $code = trim((string) ($product['code'] ?? $product['sku'] ?? $product['id'] ?? ''));
            $name = trim((string) ($product['name'] ?? $product['layanan'] ?? $product['service'] ?? ''));
            $category = trim((string) ($product['category'] ?? $product['kategori'] ?? $product['game'] ?? $product['brand'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            if (filled($categoryFilter) && $category !== $categoryFilter) {
                continue;
            }

            if ($search !== '' && !str_contains(strtolower($name), $search) && !str_contains(strtolower($code), $search)) {
                continue;
            }

            $price = (int) ($product['price'] ?? $product['harga'] ?? 0);
            $status = strtolower(trim((string) ($product['status'] ?? '')));
            $statusLabel = in_array($status, ['normal', 'available', 'active', 'aktif', '1', 'true', 1, true], true) ? 'Tersedia' : 'Gangguan';

            $options[$code] = "{$name} - Rp " . number_format($price, 0, ',', '.') . " [{$statusLabel}] ({$code})";
        }

        return $options;
    }

    protected static function getSufPaymentProductOptionLabel($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        foreach (static::getSufPaymentProducts() as $product) {
            $code = trim((string) ($product['code'] ?? $product['sku'] ?? $product['id'] ?? ''));

            if ($code === (string) $value) {
                $name = trim((string) ($product['name'] ?? $product['layanan'] ?? $product['service'] ?? ''));
                $price = (int) ($product['price'] ?? $product['harga'] ?? 0);
                $status = strtolower(trim((string) ($product['status'] ?? '')));
                $statusLabel = in_array($status, ['normal', 'available', 'active', 'aktif', '1', 'true', 1, true], true) ? 'Tersedia' : 'Gangguan';

                return "{$name} - Rp " . number_format($price, 0, ',', '.') . " [{$statusLabel}] ({$code})";
            }
        }

        return $value;
    }

    protected static function applySufPaymentProductSelection(?string $code, $set, $get): void
    {
        if (blank($code)) {
            return;
        }

        foreach (static::getSufPaymentProducts() as $product) {
            $productCode = trim((string) ($product['code'] ?? $product['sku'] ?? $product['id'] ?? ''));

            if ($productCode === $code) {
                $name = trim((string) ($product['name'] ?? $product['layanan'] ?? $product['service'] ?? ''));
                $price = (int) ($product['price'] ?? $product['harga'] ?? 0);
                $status = strtolower(trim((string) ($product['status'] ?? '')));
                $isAvailable = in_array($status, ['normal', 'available', 'active', 'aktif', '1', 'true', 1, true], true);

                $category = trim((string) ($product['category'] ?? $product['kategori'] ?? $product['game'] ?? $product['brand'] ?? ''));

                if ($name !== '') {
                    $set('layanan', $name);
                }

                $set('provider_id', $productCode);
                $set('status', $isAvailable ? 'available' : 'inactive');
                $set('harga', $price);

                static::syncTierPricesFromProfit($price, $set, $get);

                $noteText = "SufPayment: Kategori {$category}";
                if (isset($product['note']) || isset($product['catatan']) || isset($product['description'])) {
                    $note = trim((string) ($product['note'] ?? $product['catatan'] ?? $product['description'] ?? ''));
                    if ($note !== '') {
                        $noteText .= "\n" . $note;
                    }
                }
                $set('catatan', $noteText);

                static::syncSuggestedProviderPath(
                    'sufpayment',
                    $productCode,
                    $price,
                    $isAvailable ? 'available' : 'inactive',
                    ['category' => $category],
                    $set,
                    $get
                );

                static::syncTierPricesFromProfit($price, $set, $get);

                break;
            }
        }
    }

    protected static function refreshDigiflazzCache(): void
    {
        Cache::forget('filament.digiflazz.pricelist');
    }

    protected static function resolveBangJeffProductCode(array $product): string
    {
        return trim((string) ($product['productCode'] ?? $product['product_code'] ?? $product['code'] ?? $product['id'] ?? ''));
    }

    protected static function resolveBangJeffVariantCode(array $variant): string
    {
        return trim((string) ($variant['variantCode'] ?? $variant['variant_code'] ?? $variant['sku_code'] ?? $variant['code'] ?? $variant['id'] ?? ''));
    }

    protected static function resolveBangJeffVariantName(array $variant): string
    {
        return trim((string) ($variant['variantName'] ?? $variant['variant_name'] ?? $variant['name'] ?? $variant['title'] ?? ''));
    }

    protected static function resolveBangJeffVariantPrice(array $variant): int
    {
        $price = $variant['price']['value'] ?? $variant['price_value'] ?? $variant['price'] ?? 0;

        return is_numeric($price) ? (int) $price : 0;
    }

    protected static function resolveBangJeffVariantStatus(array $variant): string
    {
        return strtoupper(trim((string) ($variant['status'] ?? $variant['statusCode'] ?? 'INACTIVE')));
    }

    protected static function getBangJeffProductCodeOptions(): array
    {
        $products = static::getBangJeffProducts();
        $options = [];

        foreach ($products as $product) {
            $code = static::resolveBangJeffProductCode($product);
            $name = trim((string) ($product['name'] ?? $product['productName'] ?? $product['title'] ?? ''));

            if ($code === '') {
                continue;
            }

            $options[$code] = $name !== '' ? "{$name} ({$code})" : $code;
        }

        asort($options);

        return $options;
    }

    protected static function getBangJeffProducts(): array
    {
        $cacheKey = 'filament.bangjeff.products';
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $response = app(BangJeffService::class)->getProductsRaw();

        if (($response['error'] ?? false) === true || (($response['rc'] ?? '00') !== '00')) {
            return [];
        }

        $products = $response['data'] ?? [];
        $products = is_array($products) ? $products : [];

        Cache::put($cacheKey, $products, now()->addMinutes(5));

        return $products;
    }

    protected static function getBangJeffVariants(string $productCode): array
    {
        $normalizedCode = trim($productCode);

        if ($normalizedCode === '') {
            return [];
        }

        $cacheKey = 'filament.bangjeff.variants.' . strtoupper($normalizedCode);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $response = app(BangJeffService::class)->listVariant($normalizedCode);

        if (($response['error'] ?? false) === true || (($response['rc'] ?? '00') !== '00')) {
            return [];
        }

        $variants = $response['data'] ?? [];
        $variants = is_array($variants) ? $variants : [];

        Cache::put($cacheKey, $variants, now()->addMinutes(5));

        return $variants;
    }

    protected static function refreshBangJeffCache(?string $productCode = null): void
    {
        Cache::forget('filament.bangjeff.products');

        if (filled($productCode)) {
            Cache::forget('filament.bangjeff.variants.' . strtoupper((string) $productCode));
        }
    }

    protected static function getBangJeffVariantOptions(?string $productCode): array
    {
        if (blank($productCode)) {
            return [];
        }

        $options = [];

        foreach (static::getBangJeffVariants((string) $productCode) as $variant) {
            $code = static::resolveBangJeffVariantCode($variant);

            if ($code === '') {
                continue;
            }

            $options[$code] = static::formatBangJeffVariantOptionLabel($variant);
        }

        asort($options);

        return $options;
    }

    protected static function applyBangJeffVariantSelection(?string $variantCode, $set, $get): void
    {
        if (blank($variantCode)) {
            return;
        }

        $productCode = (string) ($get('bangjeff_product_code_filter') ?? '');

        if ($productCode === '') {
            return;
        }

        foreach (static::getBangJeffVariants($productCode) as $variant) {
            $resolvedVariantCode = static::resolveBangJeffVariantCode($variant);

            if ($resolvedVariantCode !== $variantCode) {
                continue;
            }

            $price = static::resolveBangJeffVariantPrice($variant);
            $duration = (int) ($variant['duration'] ?? 0);
            $region = (string) ($variant['region'] ?? '');
            $status = static::resolveBangJeffVariantStatus($variant);
            $durationLabel = $duration > 0 ? "{$duration} menit" : 'instan';
            $regionLabel = $region !== '' ? $region : '-';
            $variantName = static::resolveBangJeffVariantName($variant);

            $set('layanan', $variantName);
            $set('provider_id', $resolvedVariantCode);
            $set('status', $status === 'ACTIVE' ? 'available' : 'inactive');
            $set('harga', $price);
            $set('catatan', "BangJeff productCode: {$productCode} | Region: {$regionLabel} | Durasi: {$durationLabel}");

            static::syncSuggestedProviderPath(
                'bangjeff',
                $resolvedVariantCode,
                $price,
                $status === 'ACTIVE' ? 'available' : 'inactive',
                [
                    'source' => 'bangjeff_variant',
                    'product_code' => $productCode,
                    'variant_name' => $variantName,
                    'region' => $region,
                    'duration' => $duration,
                    'summary' => "BangJeff productCode: {$productCode} | Region: {$regionLabel} | Durasi: {$durationLabel}",
                ],
                $set,
                $get,
            );

            static::syncTierPricesFromProfit($price, $set, $get);

            return;
        }
    }

    protected static function getVipResellerServices(?string $filterGame = null, ?string $filterStatus = null): array
    {
        $cacheKey = static::getVipResellerCacheKey($filterGame, $filterStatus);

        $services = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filterGame, $filterStatus) {
            $response = app(VipResellerController::class)->services($filterGame, $filterStatus);

            if (($response['result'] ?? false) !== true) {
                return [];
            }

            $rows = $response['data'] ?? [];

            return is_array($rows) ? $rows : [];
        });

        if (! is_array($services)) {
            return [];
        }

        return array_values(array_filter($services, fn ($service): bool => is_array($service)));
    }

    protected static function refreshVipResellerCache(?string $filterGame = null, ?string $filterStatus = null): void
    {
        Cache::forget(static::getVipResellerCacheKey($filterGame, $filterStatus));
    }

    protected static function getVipResellerGameOptions(?string $tab): array
    {
        $tab = trim((string) $tab);

        if ($tab === '') {
            return [];
        }

        $categories = match ($tab) {
            'game_streaming' => array_merge(
                static::getVipResellerCategorySeed('data-category-game.json'),
                static::getVipResellerCategorySeed('data-category-apps-premium.json'),
            ),
            'sosmed' => static::getVipResellerCategorySeed('data-category-sosmed.json'),
            default => [],
        };

        if ($categories === []) {
            return [];
        }

        $normalized = [];
        foreach ($categories as $category) {
            $label = trim((string) $category);

            if ($label === '') {
                continue;
            }

            $normalized[$label] = $label;
        }

        asort($normalized);

        return $normalized;
    }

    protected static function getVipResellerCategorySeed(string $filename): array
    {
        $path = base_path($filename);
        $cacheKey = 'filament.vipreseller.category-seed.' . md5($path);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($path): array {
            if (! is_file($path)) {
                return [];
            }

            $contents = file_get_contents($path);
            if ($contents === false) {
                return [];
            }

            $decoded = json_decode($contents, true);
            if (! is_array($decoded)) {
                return [];
            }

            return array_values(array_filter(
                $decoded,
                static fn ($value): bool => is_string($value) && trim($value) !== ''
            ));
        });
    }

    protected static function getVipResellerCacheKey(?string $filterGame = null, ?string $filterStatus = null): string
    {
        $game = mb_strtolower(trim((string) $filterGame));
        $status = mb_strtolower(trim((string) $filterStatus));

        return 'filament.vipreseller.services.' . md5($game . '|' . $status);
    }

    protected static function getVipResellerServiceSearchResults(string $search, ?string $filterGame = null, ?string $filterStatus = null): array
    {
        $search = mb_strtolower(trim($search));
        $results = [];

        foreach (static::getVipResellerServices($filterGame, $filterStatus) as $service) {
            $code = trim((string) ($service['code'] ?? ''));

            if ($code === '') {
                continue;
            }

            $haystack = mb_strtolower(
                implode(' ', [
                    $service['code'] ?? '',
                    $service['game'] ?? '',
                    $service['name'] ?? '',
                    strip_tags((string) ($service['description'] ?? '')),
                ])
            );

            if ($search !== '' && ! str_contains($haystack, $search)) {
                continue;
            }

            $results[$code] = static::formatVipResellerOptionLabel($service);

            if (count($results) >= 50) {
                break;
            }
        }

        return $results;
    }

    protected static function getVipResellerServiceOptionLabel(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        foreach (static::getVipResellerServices() as $service) {
            if (($service['code'] ?? null) === $value) {
                return static::formatVipResellerOptionLabel($service);
            }
        }

        return (string) $value;
    }

    protected static function applyVipResellerServiceSelection(?string $serviceCode, $set, $get): void
    {
        if (blank($serviceCode)) {
            return;
        }

        foreach (static::getVipResellerServices($get('vip_reseller_game_filter'), $get('vip_reseller_status_filter')) as $service) {
            if (($service['code'] ?? null) !== $serviceCode) {
                continue;
            }

            $game = trim((string) ($service['game'] ?? ''));
            $name = trim((string) ($service['name'] ?? ''));
            $status = mb_strtolower(trim((string) ($service['status'] ?? '')));
            $description = trim((string) ($service['description'] ?? ''));
            $server = trim((string) ($service['server'] ?? ''));
            $modalPrice = (int) ($service['price']['basic'] ?? $service['price']['premium'] ?? $service['price']['special'] ?? 0);

            $label = $name !== '' ? $name : (string) $serviceCode;
            if ($game !== '' && stripos($label, $game) === false) {
                $label = $game . ' - ' . $label;
            }

            $catatanParts = array_filter([
                $game !== '' ? "VIP Game: {$game}" : null,
                $server !== '' ? "Server required: {$server}" : null,
                $description !== '' ? trim(preg_replace('/\s+/', ' ', strip_tags($description)) ?? '') : null,
            ]);

            $set('layanan', $label);
            $set('provider_id', (string) $serviceCode);
            $set('status', $status === 'available' ? 'available' : 'inactive');
            $set('harga', $modalPrice);
            $set('catatan', implode(' | ', $catatanParts));

            static::syncSuggestedProviderPath(
                'vip',
                (string) $serviceCode,
                $modalPrice,
                $status === 'available' ? 'available' : 'inactive',
                [
                    'source' => 'vip_reseller_service',
                    'game' => $game,
                    'service_name' => $name,
                    'server' => $server,
                    'description' => $description,
                    'summary' => implode(' | ', $catatanParts),
                ],
                $set,
                $get,
            );

            static::syncTierPricesFromProfit($modalPrice, $set, $get);

            return;
        }
    }

    protected static function getDigiflazzCategoryOptions(): array
    {
        $options = [];

        foreach (static::getDigiflazzProducts() as $product) {
            $category = trim((string) ($product['category'] ?? ''));

            if ($category === '') {
                continue;
            }

            $options[$category] = $category;
        }

        asort($options);

        return $options;
    }

    protected static function getDigiflazzBrandOptions(?string $category = null): array
    {
        $options = [];

        foreach (static::getDigiflazzProducts() as $product) {
            $productCategory = trim((string) ($product['category'] ?? ''));
            $brand = trim((string) ($product['brand'] ?? ''));

            if (($category !== null) && ($category !== '') && ($productCategory !== $category)) {
                continue;
            }

            if ($brand === '') {
                continue;
            }

            $options[$brand] = $brand;
        }

        asort($options);

        return $options;
    }

    protected static function getDigiflazzProductSearchResults(string $search, ?string $category = null, ?string $brand = null): array
    {
        $search = mb_strtolower(trim($search));
        $results = [];

        foreach (static::getDigiflazzProducts() as $product) {
            $productCategory = trim((string) ($product['category'] ?? ''));
            $productBrand = trim((string) ($product['brand'] ?? ''));

            if (($category !== null) && ($category !== '') && ($productCategory !== $category)) {
                continue;
            }

            if (($brand !== null) && ($brand !== '') && ($productBrand !== $brand)) {
                continue;
            }

            $haystack = mb_strtolower(
                implode(' ', [
                    $product['product_name'] ?? '',
                    $product['buyer_sku_code'] ?? '',
                    $product['brand'] ?? '',
                    $product['category'] ?? '',
                ])
            );

            if ($search !== '' && !str_contains($haystack, $search)) {
                continue;
            }

            $results[$product['buyer_sku_code']] = static::formatDigiflazzOptionLabel($product);

            if (count($results) >= 50) {
                break;
            }
        }

        return $results;
    }

    protected static function getDigiflazzProductOptionLabel(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        foreach (static::getDigiflazzProducts() as $product) {
            if (($product['buyer_sku_code'] ?? null) === $value) {
                return static::formatDigiflazzOptionLabel($product);
            }
        }

        return $value;
    }

    protected static function applyDigiflazzProductSelection(?string $sku, $set, $get): void
    {
        if (blank($sku)) {
            return;
        }

        foreach (static::getDigiflazzProducts() as $product) {
            if (($product['buyer_sku_code'] ?? null) !== $sku) {
                continue;
            }

            $set('layanan', $product['product_name'] ?? '');
            $set('provider_id', $product['buyer_sku_code'] ?? '');
            $set('status', !empty($product['buyer_product_status']) ? 'available' : 'inactive');
            $set('harga', (int) ($product['price'] ?? 0));
            $set('catatan', $product['desc'] ?? '');

            static::syncSuggestedProviderPath(
                'digiflazz',
                (string) ($product['buyer_sku_code'] ?? ''),
                (int) ($product['price'] ?? 0),
                !empty($product['buyer_product_status']) ? 'available' : 'inactive',
                [
                    'source' => 'digiflazz_pricelist',
                    'product_name' => (string) ($product['product_name'] ?? ''),
                    'brand' => (string) ($product['brand'] ?? ''),
                    'category' => (string) ($product['category'] ?? ''),
                    'description' => (string) ($product['desc'] ?? ''),
                    'summary' => trim(implode(' | ', array_filter([
                        $product['product_name'] ?? null,
                        $product['brand'] ?? null,
                        $product['category'] ?? null,
                    ]))),
                ],
                $set,
                $get,
            );

            static::syncTierPricesFromProfit((int) ($product['price'] ?? 0), $set, $get);
            return;
        }
    }

    protected static function syncSuggestedProviderPath(string $providerCode, string $providerSku, int $modalPrice, string $status, array $metadata, $set, $get): void
    {
        $providerCode = strtolower(trim($providerCode));
        $providerSku = trim($providerSku);

        if ($providerCode === '' || $providerSku === '') {
            return;
        }

        $paths = collect($get('provider_paths') ?? [])
            ->filter(fn ($row): bool => is_array($row))
            ->values();

        $matchIndex = $paths->search(function (array $row) use ($providerCode, $providerSku): bool {
            return strtolower(trim((string) ($row['provider_code'] ?? ''))) === $providerCode
                && trim((string) ($row['provider_sku'] ?? '')) === $providerSku;
        });

        if ($matchIndex === false) {
            $matchIndex = $paths->search(function (array $row) use ($providerCode): bool {
                return strtolower(trim((string) ($row['provider_code'] ?? ''))) === $providerCode;
            });
        }

        if ($matchIndex !== false) {
            $current = (array) $paths->get($matchIndex);
            $current['provider_code'] = $providerCode;
            $current['provider_sku'] = $providerSku;
            $current['modal_price'] = max(0, $modalPrice);
            $current['status'] = $status;
            $current['priority'] = max(1, (int) ($current['priority'] ?? 1));
            if ($metadata !== []) {
                $current['metadata'] = $metadata;
            }
            $paths->put($matchIndex, $current);
        } else {
            $nextPriority = $paths->isEmpty()
                ? 1
                : ((int) $paths->map(fn ($row): int => max(1, (int) ($row['priority'] ?? 1)))->max()) + 1;

            $paths->push([
                'provider_code' => $providerCode,
                'provider_sku' => $providerSku,
                'modal_price' => max(0, $modalPrice),
                'priority' => $nextPriority,
                'status' => $status,
                'metadata' => $metadata !== [] ? $metadata : null,
            ]);
        }

        $set('provider_paths', $paths->all());
    }

    protected static function formatProviderPathMetadataSummary(mixed $metadata): string
    {
        if (! is_array($metadata) || $metadata === []) {
            return '-';
        }

        if (filled($metadata['summary'] ?? null)) {
            return trim((string) $metadata['summary']);
        }

        $parts = [];

        foreach ($metadata as $key => $value) {
            if (is_array($value) || $value === null || $value === '') {
                continue;
            }

            $parts[] = str_replace('_', ' ', (string) $key) . ': ' . $value;
        }

        return $parts !== [] ? implode(' | ', $parts) : '-';
    }

    protected static function syncTierPricesFromProfit($modalPrice, $set, $get): void
    {
        $modal = max(0, (int) round((float) ($modalPrice ?? 0)));

        $memberProfit = (float) ($get('profit_member') ?? 0);
        $platinumProfit = (float) ($get('profit_platinum') ?? 0);
        $goldProfit = (float) ($get('profit_gold') ?? 0);

        $set('harga_member', static::calculateTierPrice($modal, $memberProfit));
        $set('harga_platinum', static::calculateTierPrice($modal, $platinumProfit));
        $set('harga_gold', static::calculateTierPrice($modal, $goldProfit));
    }

    protected static function syncProfitFromTierPrice(string $tier, $sellingPrice, Set $set, Get $get): void
    {
        $modal = max(0, (int) round((float) ($get('harga') ?? 0)));
        $sellingPrice = max($modal, (int) round((float) ($sellingPrice ?? 0)));

        $priceField = match ($tier) {
            'member' => 'harga_member',
            'platinum' => 'harga_platinum',
            'gold' => 'harga_gold',
            default => null,
        };

        $profitField = match ($tier) {
            'member' => 'profit_member',
            'platinum' => 'profit_platinum',
            'gold' => 'profit_gold',
            default => null,
        };

        if (! $priceField || ! $profitField) {
            return;
        }

        $set($priceField, $sellingPrice);
        $set($profitField, static::calculateMarginPercent($modal, $sellingPrice));
    }

    protected static function calculateTierPrice(int $modal, float $profit): int
    {
        if ($modal <= 0) {
            return 0;
        }

        return (int) ceil($modal + ($modal * ($profit / 100)));
    }

    protected static function calculateMarginPercent(int $modal, int $sellingPrice): int
    {
        if ($modal <= 0 || $sellingPrice <= $modal) {
            return 0;
        }

        return (int) round((($sellingPrice - $modal) / $modal) * 100);
    }

    protected static function runPricingSync(string $source, Set $set, callable $callback): void
    {
        $set('pricing_sync_source', $source);

        try {
            $callback();
        } finally {
            $set('pricing_sync_source', null);
        }
    }

    protected static function formatVipResellerOptionLabel(array $service): string
    {
        $game = trim((string) ($service['game'] ?? ''));
        $name = trim((string) ($service['name'] ?? ''));
        $code = trim((string) ($service['code'] ?? '-'));
        $status = strtoupper(trim((string) ($service['status'] ?? 'UNKNOWN')));
        $price = (int) ($service['price']['basic'] ?? $service['price']['premium'] ?? $service['price']['special'] ?? 0);
        $priceLabel = number_format($price, 0, ',', '.');

        $title = $name !== '' ? $name : $code;
        if ($game !== '' && stripos($title, $game) === false) {
            $title = "{$game} - {$title}";
        }

        return "{$title} ({$code}) - {$status} - Rp {$priceLabel}";
    }

    protected static function formatDigiflazzOptionLabel(array $product): string
    {
        $productName = $product['product_name'] ?? 'Unknown Product';
        $sku = $product['buyer_sku_code'] ?? '-';
        $brand = $product['brand'] ?? '-';
        $price = number_format((int) ($product['price'] ?? 0), 0, ',', '.');

        return "{$productName} ({$sku}) - {$brand} - Rp {$price}";
    }

    protected static function formatBangJeffVariantOptionLabel(array $variant): string
    {
        $name = static::resolveBangJeffVariantName($variant) ?: 'Unknown Variant';
        $code = static::resolveBangJeffVariantCode($variant) ?: '-';
        $status = static::resolveBangJeffVariantStatus($variant);
        $price = number_format(static::resolveBangJeffVariantPrice($variant), 0, ',', '.');

        return "{$name} ({$code}) - {$status} - Rp {$price}";
    }
}

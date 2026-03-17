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
                            ->preload()
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

                                if ($get('provider') === 'digiflazz') {
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
                            ->live()
                            ->dehydrated()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state !== 'digiflazz') {
                                    $set('digiflazz_category_filter', null);
                                    $set('digiflazz_brand_filter', null);
                                    $set('digiflazz_product', null);
                                }
                            }),

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
                            ->preload()
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
                            
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'available' => 'Active',
                                'inactive' => 'Inactive',
                                'maintenance' => 'Maintenance',
                                'out_of_stock' => 'Out of Stock',
                            ])
                            ->default('active')
                            ->required(),

                        Select::make('paket')
                            ->label('Masuk Ke Paket Layanan')
                            ->relationship('paket', 'nama')
                            ->multiple()
                            ->searchable()
                            ->preload()
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
                            ->disk('assets')
                            ->collection('product_logo')
                            ->visible(fn (Get $get) => $get('product_logo_input_mode') === 'upload')
                            ->imagePreviewHeight('150')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('integrated')
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
                        TextInput::make('harga')
                            ->label('Harga Modal')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::syncTierPricesFromProfit($state, $set, $get);
                            }),
                            
                        TextInput::make('harga_member')
                            ->label('Harga Member / Publik')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),

                        TextInput::make('profit_member')
                            ->label('Profit Member / Publik')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::syncTierPricesFromProfit($get('harga'), $set, $get);
                            }),
                            
                        TextInput::make('harga_platinum')
                            ->label('Harga Platinum')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),

                        TextInput::make('profit_platinum')
                            ->label('Profit Platinum')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::syncTierPricesFromProfit($get('harga'), $set, $get);
                            }),
                            
                        TextInput::make('harga_gold')
                            ->label('Harga Gold')
                            ->numeric()
                            ->required()
                            ->prefix('Rp'),

                            TextInput::make('profit_gold')
                            ->label('Profit Gold')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                static::syncTierPricesFromProfit($get('harga'), $set, $get);
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

    protected static function refreshDigiflazzCache(): void
    {
        Cache::forget('filament.digiflazz.pricelist');
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
            $set('status', !empty($product['buyer_product_status']) ? 'active' : 'inactive');
            $set('harga', (int) ($product['price'] ?? 0));
            $set('catatan', $product['desc'] ?? '');

            static::syncTierPricesFromProfit((int) ($product['price'] ?? 0), $set, $get);
            return;
        }
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

    protected static function calculateTierPrice(int $modal, float $profit): int
    {
        if ($modal <= 0) {
            return 0;
        }

        return (int) ceil($modal + ($modal * ($profit / 100)));
    }

    protected static function formatDigiflazzOptionLabel(array $product): string
    {
        $productName = $product['product_name'] ?? 'Unknown Product';
        $sku = $product['buyer_sku_code'] ?? '-';
        $brand = $product['brand'] ?? '-';
        $price = number_format((int) ($product['price'] ?? 0), 0, ',', '.');

        return "{$productName} ({$sku}) - {$brand} - Rp {$price}";
    }
}

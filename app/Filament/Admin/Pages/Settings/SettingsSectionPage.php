<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Models\MediaAsset;
use App\Models\SettingWeb;
use App\Services\EmailNotificationService;
use App\Services\OptimizedImageService;
use App\Services\PwaIconGeneratorService;
use App\Services\WhatsappNotificationService;
use App\Support\PublicThemeRegistry;
use App\Support\MediaAssetPicker;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use UnitEnum;

abstract class SettingsSectionPage extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static UnitEnum|string|null $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 10;
    
    protected static ?string $navigationParentItem = 'Settings';

    protected string $view = 'filament.admin.pages.settings-form';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill($this->getSettingsData());
    }

    public function form(Schema $schema): Schema
    {
        $sections = [
                // Website Information
                Section::make('Informasi Website')
                    ->description('Informasi dasar toko yang tampil ke pengunjung dan mesin pencari.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('judul_web')
                            ->label('Nama Website')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama toko yang tampil di browser dan halaman publik.'),
                            
                        TextInput::make('order_prefik')
                            ->label('Prefix Order')
                            ->helperText('Awalan invoice/order. Contoh: INV atau ORD.'),

                        Select::make('public_theme')
                            ->label('Tema Storefront')
                            ->options(PublicThemeRegistry::options())
                            ->default(PublicThemeRegistry::DEFAULT)
                            ->native(false)
                            ->helperText('Pilih tampilan halaman publik yang aktif.'),
                            
                        RichEditor::make('deskripsi_web')
                            ->label('Deskripsi Website')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike', 'link',
                                'bulletList', 'orderedList', 'h2', 'h3',
                                'blockquote', 'undo', 'redo',
                            ])
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Konten rich text untuk footer SEO. Meta description dan preview link otomatis memakai versi teks biasa.'),
                            
                        Textarea::make('keywords')
                            ->label('Kata Kunci SEO')
                            ->rows(2)
                            ->helperText('Pisahkan dengan koma. Contoh: topup, game, murah.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->extraAttributes($this->onboardingSectionAttributes('website-information')),

                // Analytics & Tracking
                Section::make('Pelacakan Konversi & Analitik')
                    ->description('Integrasikan pelacakan performa, konversi, dan analitik toko Anda.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('google_analytics_id')
                            ->label('ID Google Analytics 4')
                            ->placeholder('G-XXXXXXXXXX')
                            ->helperText('Measurement ID dari GA4.'),
                        
                        TextInput::make('facebook_pixel_id')
                            ->label('ID Facebook Pixel')
                            ->placeholder('XXXXXXXXXXXXXXX')
                            ->helperText('Isi angka Pixel ID dari Meta.'),

                        TextInput::make('google_tag_manager_id')
                            ->label('ID Google Tag Manager')
                            ->placeholder('GTM-XXXXXXX')
                            ->helperText('Container ID GTM. Diabaikan jika custom snippet diisi.'),

                        Textarea::make('gtm_custom_head_script')
                            ->label('Script GTM di Head')
                            ->rows(6)
                            ->columnSpanFull()
                            ->helperText('Opsional. Tempel snippet GTM untuk area <head>. Gunakan hanya script tepercaya.'),

                        Textarea::make('gtm_custom_body_noscript')
                            ->label('Noscript GTM di Body')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('Opsional. Tempel noscript/iframe GTM untuk area <body>.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('analytics-tracking')),

                Section::make('Popup Homepage')
                    ->description('Tampilkan atau sembunyikan popup pengumuman di halaman utama.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('home_popup_enabled')
                            ->label('Aktifkan Popup')
                            ->default(true)
                            ->helperText('Nonaktifkan jika tidak ingin menampilkan popup ke pengunjung.'),
                    ])
                    ->collapsible()
                    ->extraAttributes($this->onboardingSectionAttributes('homepage-popup')),

                Section::make('Live Sales Toast')
                    ->description('Tampilkan atau sembunyikan notifikasi transaksi terbaru di homepage.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('live_sales_enabled')
                            ->label('Aktifkan Live Sales')
                            ->default(true)
                            ->helperText('Nonaktifkan jika tidak ingin menampilkan toast pembelian terbaru.'),
                    ])
                    ->collapsible()
                    ->extraAttributes($this->onboardingSectionAttributes('live-sales')),

                Section::make('SEO Crawling')
                    ->description('Atur robots.txt dan sitemap.xml untuk mesin pencari.')
                    ->headerActions([
                        $this->makeValidateSitemapXmlAction(),
                    ])
                    ->columns(2)
                    ->schema([
                        Toggle::make('seo_robots_enabled')
                            ->label('Aktifkan robots.txt')
                            ->default(true)
                            ->helperText('Nonaktifkan jika ingin mencegah semua bot melakukan crawl.'),

                        Toggle::make('seo_sitemap_enabled')
                            ->label('Aktifkan sitemap.xml')
                            ->default(true)
                            ->live()
                            ->helperText('Sitemap membantu mesin pencari menemukan halaman website.'),

                        Select::make('seo_sitemap_mode')
                            ->label('Mode Sitemap')
                            ->options([
                                'dynamic' => 'Otomatis (Disarankan)',
                                'custom_upload' => 'Upload Manual (Media Manager)',
                            ])
                            ->default('dynamic')
                            ->native(false)
                            ->live()
                            ->visible(fn (Get $get): bool => (bool) $get('seo_sitemap_enabled'))
                            ->helperText('Otomatis cocok untuk mayoritas toko. Upload manual hanya jika butuh file XML khusus.'),

                        Toggle::make('seo_sitemap_include_categories')
                            ->label('Masukkan Kategori ke Sitemap')
                            ->default(true)
                            ->visible(fn (Get $get): bool => (bool) $get('seo_sitemap_enabled')),

                        Toggle::make('seo_sitemap_include_articles')
                            ->label('Masukkan Artikel ke Sitemap')
                            ->default(true)
                            ->visible(fn (Get $get): bool => (bool) $get('seo_sitemap_enabled')),

                        TextInput::make('seo_sitemap_cache_minutes')
                            ->label('Cache Sitemap (menit)')
                            ->numeric()
                            ->default(30)
                            ->minValue(5)
                            ->maxValue(1440)
                            ->visible(fn (Get $get): bool => (bool) $get('seo_sitemap_enabled'))
                            ->helperText('Disarankan 15-60 menit agar tetap ringan.'),

                        Hidden::make('seo_sitemap_index_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Hidden::make('seo_sitemap_main_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Hidden::make('seo_sitemap_categories_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Placeholder::make('seo_sitemap_index_picker')
                            ->label('Custom sitemap.xml (index)')
                            ->visible(fn (Get $get): bool => (bool) $get('seo_sitemap_enabled') && $get('seo_sitemap_mode') === 'custom_upload')
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseSeoSitemapIndexAsset',
                                    'seo_sitemap_index_asset_id',
                                    'Pilih File sitemap.xml dari Media Manager',
                                    ['xml', 'dokumen', 'lainnya'],
                                    'xml',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearSeoSitemapIndexAsset',
                                    'seo_sitemap_index_asset_id',
                                ),
                            ])
                            ->content(fn (Get $get) => MediaAssetPicker::renderPreview($get('seo_sitemap_index_asset_id')))
                            ->columnSpanFull(),

                        Placeholder::make('seo_sitemap_main_picker')
                            ->label('Custom sitemap-main.xml')
                            ->visible(fn (Get $get): bool => (bool) $get('seo_sitemap_enabled') && $get('seo_sitemap_mode') === 'custom_upload')
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseSeoSitemapMainAsset',
                                    'seo_sitemap_main_asset_id',
                                    'Pilih File sitemap-main.xml dari Media Manager',
                                    ['xml', 'dokumen', 'lainnya'],
                                    'xml',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearSeoSitemapMainAsset',
                                    'seo_sitemap_main_asset_id',
                                ),
                            ])
                            ->content(fn (Get $get) => MediaAssetPicker::renderPreview($get('seo_sitemap_main_asset_id')))
                            ->columnSpanFull(),

                        Placeholder::make('seo_sitemap_categories_picker')
                            ->label('Custom sitemap-categories.xml')
                            ->visible(fn (Get $get): bool => (bool) $get('seo_sitemap_enabled') && $get('seo_sitemap_mode') === 'custom_upload')
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseSeoSitemapCategoriesAsset',
                                    'seo_sitemap_categories_asset_id',
                                    'Pilih File sitemap-categories.xml dari Media Manager',
                                    ['xml', 'dokumen', 'lainnya'],
                                    'xml',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearSeoSitemapCategoriesAsset',
                                    'seo_sitemap_categories_asset_id',
                                ),
                            ])
                            ->content(fn (Get $get) => MediaAssetPicker::renderPreview($get('seo_sitemap_categories_asset_id')))
                            ->columnSpanFull(),

                        Textarea::make('seo_robots_custom_lines')
                            ->label('Baris Tambahan robots.txt')
                            ->rows(6)
                            ->live(debounce: 500)
                            ->columnSpanFull()
                            ->placeholder("User-agent: Googlebot-Image\nAllow: /payment\nDisallow: /private")
                            ->helperText('Opsional. Satu aturan per baris, ditambahkan ke aturan default.'),

                        Placeholder::make('seo_robots_preview')
                            ->label('Preview robots.txt')
                            ->content(function (Get $get): HtmlString {
                                $robotsEnabled = (bool) $get('seo_robots_enabled');
                                $sitemapEnabled = (bool) $get('seo_sitemap_enabled');
                                $customLinesRaw = (string) ($get('seo_robots_custom_lines') ?? '');

                                if (! $robotsEnabled) {
                                    $preview = "User-agent: *\nDisallow: /\n";
                                } else {
                                    $lines = [
                                        'User-agent: *',
                                        'Allow: /',
                                        'Disallow: /admin',
                                        'Disallow: /livewire',
                                        'Disallow: /callback',
                                        'Disallow: /wejizy',
                                        'Disallow: /cronjob',
                                        'Disallow: /ipay88',
                                    ];

                                    $customLines = preg_split('/\R+/', $customLinesRaw) ?: [];
                                    foreach ($customLines as $line) {
                                        $line = trim($line);
                                        if ($line !== '') {
                                            $lines[] = $line;
                                        }
                                    }

                                    if ($sitemapEnabled) {
                                        $lines[] = '';
                                        $lines[] = 'Sitemap: ' . url('/sitemap.xml');
                                    }

                                    $preview = implode("\n", $lines) . "\n";
                                }

                                return new HtmlString(
                                    '<pre style="white-space:pre-wrap;word-break:break-word;padding:12px;border-radius:10px;background:rgba(15,23,42,.55);border:1px solid rgba(148,163,184,.25);font-size:12px;line-height:1.55;">'
                                    . e($preview)
                                    . '</pre>'
                                );
                            })
                            ->columnSpanFull(),

                        Placeholder::make('seo_endpoint_info')
                            ->label('Endpoint SEO Aktif')
                            ->content(fn (): string => 'robots: ' . url('/robots.txt') . ' | sitemap index: ' . url('/sitemap.xml') . ' | sitemap main: ' . url('/sitemap-main.xml') . ' | sitemap categories: ' . url('/sitemap-categories.xml'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('seo')),

                Section::make('Keamanan Login Admin')
                    ->description('Atur CAPTCHA untuk membantu melindungi halaman login admin.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('captcha_enabled')
                            ->label('Aktifkan CAPTCHA')
                            ->default(true)
                            ->helperText('Jika aktif, admin wajib melewati verifikasi CAPTCHA saat login.'),

                        Toggle::make('captcha_bypass')
                            ->label('Bypass CAPTCHA (Darurat)')
                            ->default(false)
                            ->helperText('Gunakan hanya saat troubleshooting login.'),

                        TextInput::make('captcha_site_key')
                            ->label('Site Key CAPTCHA')
                            ->helperText('Site key dari Google reCAPTCHA admin console.')
                            ->columnSpan(1),

                        TextInput::make('captcha_secret')
                            ->label('Secret Key CAPTCHA')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key dari Google reCAPTCHA. Jangan bagikan nilai ini.')
                            ->columnSpan(1),

                        TextInput::make('google_client_id')
                            ->label('Google Client ID')
                            ->placeholder('xxxxxxxxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com')
                            ->helperText('Dipakai untuk tombol login Google di halaman user.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('admin-captcha')),
                    
                // Branding
                Section::make('Logo & Warna')
                    ->description('Atur logo dan warna utama storefront.')
                    ->columns([
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        Radio::make('logo_header_input_mode')
                            ->label('Sumber Logo Header')
                            ->options([
                                'library' => 'Media Manager',
                                'upload' => 'Upload Manual',
                            ])
                            ->default('upload')
                            ->inline()
                            ->inlineLabel(false)
                            ->live()
                            ->dehydrated()
                            ->afterStateUpdated(function (string $state, callable $set): void {
                                if ($state === 'library') {
                                    $set('logo_header', null);
                                }
                            })
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),

                        Hidden::make('logo_header_media_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Placeholder::make('logo_header_media_asset_picker')
                            ->label('Logo Header dari Media Manager')
                            ->visible(fn (Get $get): bool => $get('logo_header_input_mode') === 'library')
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseSettingsLogoHeaderMediaAsset',
                                    'logo_header_media_asset_id',
                                    'Pilih Header Logo dari Media Manager',
                                    ['logo', 'lainnya'],
                                    'logo',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearSettingsLogoHeaderMediaAsset',
                                    'logo_header_media_asset_id',
                                ),
                            ])
                            ->content(fn (Get $get) => MediaAssetPicker::renderPreview($get('logo_header_media_asset_id')))
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),

                        FileUpload::make('logo_header')
                            ->label('Logo Header')
                            ->image()
                            ->disk(config('uploads.disk', 'assets'))
                            ->visibility('public')
                            ->directory('assets/logo')
                            ->maxSize(2048)
                            ->visible(fn (Get $get): bool => $get('logo_header_input_mode') === 'upload')
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),

                        Radio::make('logo_footer_input_mode')
                            ->label('Sumber Logo Footer')
                            ->options([
                                'library' => 'Media Manager',
                                'upload' => 'Upload Manual',
                            ])
                            ->default('upload')
                            ->inline()
                            ->inlineLabel(false)
                            ->live()
                            ->dehydrated()
                            ->afterStateUpdated(function (string $state, callable $set): void {
                                if ($state === 'library') {
                                    $set('logo_footer', null);
                                }
                            })
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),

                        Hidden::make('logo_footer_media_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Placeholder::make('logo_footer_media_asset_picker')
                            ->label('Logo Footer dari Media Manager')
                            ->visible(fn (Get $get): bool => $get('logo_footer_input_mode') === 'library')
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseSettingsLogoFooterMediaAsset',
                                    'logo_footer_media_asset_id',
                                    'Pilih Footer Logo dari Media Manager',
                                    ['logo', 'lainnya'],
                                    'logo',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearSettingsLogoFooterMediaAsset',
                                    'logo_footer_media_asset_id',
                                ),
                            ])
                            ->content(fn (Get $get) => MediaAssetPicker::renderPreview($get('logo_footer_media_asset_id')))
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),
                            
                        FileUpload::make('logo_footer')
                            ->label('Logo Footer')
                            ->image()
                            ->disk(config('uploads.disk', 'assets'))
                            ->visibility('public')
                            ->directory('assets/logo')
                            ->maxSize(2048)
                            ->visible(fn (Get $get): bool => $get('logo_footer_input_mode') === 'upload')
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),

                        Radio::make('logo_favicon_input_mode')
                            ->label('Sumber Favicon')
                            ->options([
                                'library' => 'Media Manager',
                                'upload' => 'Upload Manual',
                            ])
                            ->default('upload')
                            ->inline()
                            ->inlineLabel(false)
                            ->live()
                            ->dehydrated()
                            ->afterStateUpdated(function (string $state, callable $set): void {
                                if ($state === 'library') {
                                    $set('logo_favicon', null);
                                }
                            })
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),

                        Hidden::make('logo_favicon_media_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Placeholder::make('logo_favicon_media_asset_picker')
                            ->label('Favicon dari Media Manager')
                            ->visible(fn (Get $get): bool => $get('logo_favicon_input_mode') === 'library')
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseSettingsLogoFaviconMediaAsset',
                                    'logo_favicon_media_asset_id',
                                    'Pilih Favicon dari Media Manager',
                                    ['logo', 'lainnya'],
                                    'logo',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearSettingsLogoFaviconMediaAsset',
                                    'logo_favicon_media_asset_id',
                                ),
                            ])
                            ->content(fn (Get $get) => MediaAssetPicker::renderPreview($get('logo_favicon_media_asset_id')))
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),
                            
                        FileUpload::make('logo_favicon')
                            ->label('Favicon')
                            ->disk(config('uploads.disk', 'assets'))
                            ->visibility('public')
                            ->directory('assets/logo')
                            ->rules(['nullable', 'mimes:ico,png,svg,webp'])
                            ->maxSize(512)
                            ->visible(fn (Get $get): bool => $get('logo_favicon_input_mode') === 'upload')
                            ->helperText('Format .ico/.png/.svg/.webp, disarankan 32x32 px.')
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),

                        FileUpload::make('pwa_icon_source')
                            ->label('Icon PWA')
                            ->image()
                            ->disk('assets')
                            ->visibility('public')
                            ->directory('assets/pwa/source')
                            ->rules(['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'dimensions:min_width=512,min_height=512'])
                            ->maxSize(4096)
                            ->helperText('Upload 1 gambar utama minimal 512x512. Sistem akan membuat ukuran icon aplikasi otomatis.')
                            ->columnSpan([
                                'sm' => 2,
                                'lg' => 2,
                            ]),
                            
                        ColorPicker::make('warna1')
                            ->label('Warna Utama')
                            ->columnSpan(1),
                            
                        ColorPicker::make('warna2')
                            ->label('Warna Kedua')
                            ->columnSpan(1),
                            
                        ColorPicker::make('warna3')
                            ->label('Warna Aksen')
                            ->columnSpan(1),
                            
                        ColorPicker::make('warna4')
                            ->label('Warna Background')
                            ->columnSpan(1),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('branding')),

                Section::make('Tema Musiman')
                    ->description('Atur tema event musiman tanpa mengubah struktur halaman utama.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('seasonal_enabled')
                            ->label('Aktifkan Tema Musiman')
                            ->default(false)
                            ->live()
                            ->helperText('Jika nonaktif, website selalu memakai tema normal/default.'),

                        Select::make('seasonal_mode')
                            ->label('Mode Aktivasi')
                            ->options([
                                'manual' => 'Manual',
                                'date_range' => 'Terjadwal (Rentang Tanggal)',
                            ])
                            ->default('manual')
                            ->live()
                            ->visible(fn (callable $get): bool => (bool) $get('seasonal_enabled'))
                            ->helperText('Manual: langsung aktif. Terjadwal: aktif hanya di rentang tanggal.'),

                        Select::make('seasonal_theme')
                            ->label('Tema Musiman')
                            ->options([
                                'ramadhan' => 'Ramadhan',
                                'halloween' => 'Halloween',
                            ])
                            ->default('ramadhan')
                            ->required()
                            ->visible(fn (callable $get): bool => (bool) $get('seasonal_enabled'))
                            ->helperText('Pilih tema visual musiman yang ingin ditampilkan.'),

                        Select::make('seasonal_effect_intensity')
                            ->label('Intensitas Efek')
                            ->options([
                                'subtle' => 'Subtle (Halus)',
                                'normal' => 'Normal',
                            ])
                            ->default('subtle')
                            ->required()
                            ->visible(fn (callable $get): bool => (bool) $get('seasonal_enabled'))
                            ->helperText('Subtle untuk efek ringan, Normal untuk nuansa lebih terasa.'),

                        Radio::make('seasonal_background_image_input_mode')
                            ->label('Sumber Background Musiman')
                            ->options([
                                'library' => 'Media Manager',
                                'upload' => 'Upload Manual',
                            ])
                            ->default('upload')
                            ->inline()
                            ->inlineLabel(false)
                            ->live()
                            ->dehydrated()
                            ->afterStateUpdated(function (string $state, callable $set): void {
                                if ($state === 'library') {
                                    $set('seasonal_background_image', null);
                                }
                            })
                            ->columnSpanFull()
                            ->visible(fn (callable $get): bool => (bool) $get('seasonal_enabled')),

                        Hidden::make('seasonal_background_image_media_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Placeholder::make('seasonal_background_image_media_asset_picker')
                            ->label('Background Musiman dari Media Manager')
                            ->visible(fn (Get $get): bool => (bool) $get('seasonal_enabled') && $get('seasonal_background_image_input_mode') === 'library')
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseSettingsSeasonalBackgroundMediaAsset',
                                    'seasonal_background_image_media_asset_id',
                                    'Pilih Background Musiman dari Media Manager',
                                    ['seasonal', 'banner', 'lainnya'],
                                    'seasonal',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearSettingsSeasonalBackgroundMediaAsset',
                                    'seasonal_background_image_media_asset_id',
                                ),
                            ])
                            ->content(fn (Get $get) => MediaAssetPicker::renderPreview($get('seasonal_background_image_media_asset_id')))
                            ->columnSpanFull(),

                        FileUpload::make('seasonal_background_image')
                            ->label('Background Image (Opsional)')
                            ->image()
                            ->disk(config('uploads.disk', 'assets'))
                            ->visibility('public')
                            ->directory('assets/seasonal')
                            ->maxSize(4096)
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => (bool) $get('seasonal_enabled') && $get('seasonal_background_image_input_mode') === 'upload')
                            ->helperText('Upload background custom (JPG/PNG/WebP). Jika diisi, gambar ini akan ditumpuk di atas gradient seasonal.'),

                        TextInput::make('seasonal_background_opacity')
                            ->label('Opacity Gambar Background (%)')
                            ->numeric()
                            ->default(38)
                            ->minValue(5)
                            ->maxValue(95)
                            ->suffix('%')
                            ->visible(fn (callable $get): bool => (bool) $get('seasonal_enabled'))
                            ->helperText('Atur transparansi gambar background agar teks tetap terbaca (disarankan 25-50%).'),

                        DateTimePicker::make('seasonal_starts_at')
                            ->label('Mulai Aktif')
                            ->seconds(false)
                            ->native(false)
                            ->visible(fn (callable $get): bool => (bool) $get('seasonal_enabled') && $get('seasonal_mode') === 'date_range')
                            ->helperText('Kosongkan jika ingin mulai langsung saat disimpan.'),

                        DateTimePicker::make('seasonal_ends_at')
                            ->label('Berakhir Pada')
                            ->seconds(false)
                            ->native(false)
                            ->visible(fn (callable $get): bool => (bool) $get('seasonal_enabled') && $get('seasonal_mode') === 'date_range')
                            ->helperText('Kosongkan jika ingin tetap aktif sampai dinonaktifkan manual.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('seasonal-theme')),
                    
                // Social Media
                Section::make('Link Sosial Media')
                    ->description('Link yang ditampilkan di storefront atau kontak toko.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('url_wa')
                            ->label('Link WhatsApp')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Contoh: wa.me/62812xxxx.'),
                            
                        TextInput::make('url_ig')
                            ->label('Link Instagram')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Link profil Instagram.'),
                            
                        TextInput::make('url_tiktok')
                            ->label('Link TikTok')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Link profil TikTok.'),
                            
                        TextInput::make('url_youtube')
                            ->label('Link YouTube')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Link channel YouTube.'),
                            
                        TextInput::make('url_fb')
                            ->label('Link Facebook')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Link halaman Facebook.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('social-media')),
                    
                // Top-Up Providers
                Section::make('TopUpIndo')
                    ->description('Simpan API key TopUpIndo untuk order dan sinkron produk.')
                    ->schema([
                        TextInput::make('topupindo_api')
                            ->label('API Key TopUpIndo')
                            ->password()
                            ->revealable()
                            ->helperText('Ambil dari dashboard TopUpIndo. Jangan isi username atau password login.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('topupindo')),
                    
                Section::make('BangJeff')
                    ->description('Simpan API key BangJeff untuk cek saldo, sinkron produk, dan order provider.')
                    ->schema([
                        TextInput::make('apikey_bangjeff')
                            ->label('API Key BangJeff')
                            ->password()
                            ->revealable()
                            ->helperText('Credential utama BangJeff diambil dari setting ini.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('bangjeff')),
                    
                Section::make('Aoshi')
                    ->description('Masukkan secret/API key Aoshi dari dashboard merchant/provider.')
                    ->schema([
                        TextInput::make('apikey_aoshi')
                            ->label('Aoshi API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Gunakan secret key dari panel Aoshi, bukan password login akun.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('aoshi')),
                    
                Section::make('Mobile Game Store')
                    ->description('Simpan API key Mobile Game Store untuk koneksi provider.')
                    ->schema([
                        TextInput::make('api_mobilegamestore')
                            ->label('API Key Mobile Game Store')
                            ->password()
                            ->revealable()
                            ->helperText('Ambil dari dashboard Mobile Game Store.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('mobile-game-store')),
                    
                Section::make('VIP Reseller')
                    ->description('Isi credential VIP Reseller untuk order, callback, dan cek saldo.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('vip_apiid')
                            ->label('API ID VIP')
                            ->helperText('API ID dari dashboard VIP Reseller.'),
                            
                        TextInput::make('vip_apikey')
                            ->label('API Key VIP')
                            ->password()
                            ->revealable()
                            ->helperText('API key dari dashboard VIP Reseller.'),

                        TextInput::make('vip_sign')
                            ->label('API Sign VIP (Opsional)')
                            ->password()
                            ->revealable()
                            ->helperText('Kosongkan jika ingin sistem menghitung sign otomatis.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('vip-reseller')),
                    
                // Payment Gateways
                Section::make('Deposit QRIS')
                    ->description('Pilih gateway utama untuk deposit saldo via QRIS.')
                    ->schema([
                        \Filament\Forms\Components\Select::make('deposit_jalur')
                            ->label('Gateway Deposit Aktif')
                            ->options([
                                'duitku' => 'Duitku',
                                'tripay' => 'TriPay',
                                'tokopay' => 'TokoPay',
                            ])
                            ->default('duitku')
                            ->required()
                            ->helperText('Gateway ini dipakai untuk deposit QRIS member.'),
                    ])
                    ->collapsible()
                    ->extraAttributes($this->onboardingSectionAttributes('deposit-configuration')),

                Section::make('PayDisini')
                    ->description('Simpan API key PayDisini untuk membuat invoice dan cek status pembayaran.')
                    ->schema([
                        TextInput::make('paydisini_apikey')
                            ->label('API Key PayDisini')
                            ->password()
                            ->revealable()
                            ->helperText('Ambil dari dashboard PayDisini.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('paydisini')),
                    
                Section::make('TriPay')
                    ->description('Isi credential TriPay untuk membuat transaksi dan memverifikasi callback.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('tripay_api')
                            ->label('API Key TriPay')
                            ->password()
                            ->revealable()
                            ->helperText('API key dari dashboard TriPay.'),
                            
                        TextInput::make('tripay_merchant_code')
                            ->label('Merchant Code TriPay')
                            ->helperText('Kode merchant dari dashboard TriPay.'),
                            
                        TextInput::make('tripay_private_key')
                            ->label('Private Key TriPay')
                            ->password()
                            ->revealable()
                            ->helperText('Private key untuk signature pembayaran.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('tripay')),
                    
                Section::make('TokoPay')
                    ->description('Isi credential TokoPay untuk membuat transaksi dan cek status pembayaran.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('tokopay_merchant_id')
                            ->label('Merchant ID TokoPay')
                            ->helperText('Merchant ID dari dashboard TokoPay.'),
                            
                        TextInput::make('tokopay_secret_key')
                            ->label('Secret Key TokoPay')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key dari dashboard TokoPay.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('tokopay')),
                    
                Section::make('Duitku')
                    ->description('Isi credential Duitku dan URL callback/return untuk pembayaran.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('duitku_merchant_code')
                            ->label('Merchant Code Duitku')
                            ->helperText('Merchant code dari dashboard Duitku.')
                            ->columnSpan(1),
                            
                        TextInput::make('duitku_merchant_key')
                            ->label('Merchant Key Duitku')
                            ->password()
                            ->revealable()
                            ->helperText('Merchant key/API key dari dashboard Duitku.')
                            ->columnSpan(1),
                            
                        TextInput::make('duitku_callback_url')
                            ->label('URL Callback Duitku')
                            ->url()
                            ->default(config('app.url').'/wejizy/duitku/callback')
                            ->helperText('URL yang menerima notifikasi pembayaran dari Duitku.')
                            ->columnSpan(1),
                            
                        TextInput::make('duitku_return_url')
                            ->label('URL Return Duitku')
                            ->url()
                            ->default(config('app.url').'/id/invoices/')
                            ->helperText('Halaman tujuan user setelah pembayaran.')
                            ->columnSpan(1),
                            
                        \Filament\Forms\Components\Select::make('duitku_mode')
                            ->label('Mode Duitku')
                            ->options([
                                'sandbox' => 'Sandbox / Testing',
                                'production' => 'Production / Live',
                            ])
                            ->default('sandbox')
                            ->required()
                            ->helperText('Gunakan Production hanya untuk transaksi live.')
                            ->columnSpan(1),
                            
                        Toggle::make('duitku_enabled')
                            ->label('Aktifkan Duitku')
                            ->helperText('Jika aktif, Duitku dapat dipakai sebagai payment gateway.')
                            ->default(false)
                            ->columnSpan(1),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('duitku')),
                    
                Section::make('Digiflazz')
                    ->description('Isi credential buyer Digiflazz untuk order dan cek saldo.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('username_digi')
                            ->label('Username Buyer')
                            ->helperText('Username akun buyer Digiflazz.'),
                            
                        TextInput::make('api_key_digi')
                            ->label('API Key Digiflazz')
                            ->password()
                            ->revealable()
                            ->helperText('API key dari dashboard Digiflazz.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('digiflazz')),
                    
                Section::make('API Games')
                    ->description('Isi Merchant ID dan Secret Key API Games untuk order dan cek saldo.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('apigames_merchant')
                            ->label('Merchant ID API Games')
                            ->helperText('Merchant ID dari dashboard API Games.'),
                            
                        TextInput::make('apigames_secret')
                            ->label('Secret Key API Games')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key dari dashboard API Games.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('api-games')),
                    
                // WhatsApp Integration
                Section::make('Konfigurasi WhatsApp')
                    ->description('Atur provider dan akun WhatsApp untuk notifikasi otomatis.')
                    ->headerActions([
                        $this->makeSendTestWhatsappAction(),
                        $this->makeCheckWhatsappStatusAction(),
                    ])
                    ->columns(3)
                    ->schema([
                        Select::make('wa_provider')
                            ->label('Provider WhatsApp')
                            ->options([
                                'fonnte' => 'Fonnte',
                                'easywa' => 'EasyWA',
                            ])
                            ->default('fonnte')
                            ->native(false)
                            ->helperText('Pilih provider yang sedang dipakai.'),

                        TextInput::make('nomor_admin')
                            ->label('Nomor WhatsApp Admin')
                            ->tel()
                            ->prefix('+62')
                            ->helperText('Nomor utama admin untuk menerima notifikasi sistem.'),
                            
                        TextInput::make('wa_key')
                            ->label('Token Fonnte')
                            ->password()
                            ->revealable()
                            ->helperText('Isi jika provider yang dipakai adalah Fonnte.'),
                            
                        TextInput::make('wa_number')
                            ->label('Nomor Device Fonnte')
                            ->tel()
                            ->prefix('+62')
                            ->helperText('Nomor WhatsApp yang terhubung di Fonnte.'),

                        TextInput::make('easywa_email')
                            ->label('Email EasyWA')
                            ->helperText('Email akun EasyWA.')
                            ->visible(fn ($get) => ($get('wa_provider') ?? 'fonnte') === 'easywa'),

                        TextInput::make('easywa_secret_key')
                            ->label('Secret Key EasyWA')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key dari dashboard EasyWA.')
                            ->visible(fn ($get) => ($get('wa_provider') ?? 'fonnte') === 'easywa'),

                        Select::make('easywa_send_type')
                            ->label('Mode Kirim EasyWA')
                            ->options([
                                'sync' => 'Langsung',
                                'async' => 'Antrian',
                            ])
                            ->default('sync')
                            ->native(false)
                            ->helperText('Pilih Langsung untuk kirim saat itu juga, atau Antrian jika memakai delay.')
                            ->visible(fn ($get) => ($get('wa_provider') ?? 'fonnte') === 'easywa'),

                        TextInput::make('easywa_send_delay')
                            ->label('Delay Kirim (detik)')
                            ->numeric()
                            ->default(0)
                            ->helperText('Dipakai hanya saat mode EasyWA = Antrian.')
                            ->visible(fn ($get) => ($get('wa_provider') ?? 'fonnte') === 'easywa' && ($get('easywa_send_type') ?? 'sync') === 'async'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('whatsapp-configuration')),

                Section::make('Konfigurasi Email')
                    ->description('Atur SMTP yang dipakai sistem untuk mengirim email otomatis.')
                    ->headerActions([
                        $this->makeSendTestEmailAction(),
                    ])
                    ->columns(2)
                    ->schema([
                        Select::make('mail_mailer')
                            ->label('Mode Email')
                            ->options([
                                'smtp' => 'SMTP',
                                'log' => 'Log / Testing',
                            ])
                            ->default('smtp')
                            ->native(false)
                            ->helperText('Gunakan SMTP untuk email asli. Log hanya untuk testing.'),

                        TextInput::make('mail_host')
                            ->label('Host SMTP')
                            ->placeholder('smtp.gmail.com')
                            ->helperText('Alamat server SMTP dari provider email.'),

                        TextInput::make('mail_port')
                            ->label('Port SMTP')
                            ->numeric()
                            ->placeholder('587')
                            ->helperText('Umumnya 587 untuk TLS atau 465 untuk SSL.'),

                        TextInput::make('mail_encryption')
                            ->label('Enkripsi')
                            ->placeholder('tls / ssl')
                            ->helperText('Biasanya tls. Ikuti instruksi provider email.'),

                        TextInput::make('mail_username')
                            ->label('Username SMTP')
                            ->helperText('Username dari provider email.'),

                        TextInput::make('mail_password')
                            ->label('Password SMTP')
                            ->password()
                            ->revealable()
                            ->helperText('Gunakan password SMTP atau app password, bukan password akun utama.'),

                        TextInput::make('mail_from_address')
                            ->label('Email Pengirim')
                            ->email()
                            ->helperText('Alamat email yang tampil sebagai pengirim.'),

                        TextInput::make('mail_from_name')
                            ->label('Nama Pengirim')
                            ->helperText('Nama toko yang tampil di inbox pembeli.'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('mail-configuration')),

                Section::make('Channel Notifikasi')
                    ->description('Pilih channel otomatis untuk invoice, affiliate, dan tenant Reseller Topup.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('invoice_notify_via_whatsapp')
                            ->label('Invoice via WhatsApp')
                            ->default(true)
                            ->helperText('Kirim update transaksi ke WhatsApp pembeli.'),

                        Toggle::make('invoice_notify_via_email')
                            ->label('Invoice via Email')
                            ->default(true)
                            ->helperText('Kirim update transaksi ke email pembeli.'),

                        Toggle::make('affiliate_notify_via_whatsapp')
                            ->label('Affiliate via WhatsApp')
                            ->default(true)
                            ->helperText('Kirim hasil review affiliate lewat WhatsApp.'),

                        Toggle::make('affiliate_notify_via_email')
                            ->label('Affiliate via Email')
                            ->default(true)
                            ->helperText('Kirim hasil review affiliate lewat email.'),

                        Toggle::make('tenant_notify_via_whatsapp')
                            ->label('Tenant via WhatsApp')
                            ->default(true)
                            ->helperText('Kirim invoice dan aktivasi Reseller Topup lewat WhatsApp.'),

                        Toggle::make('tenant_notify_via_email')
                            ->label('Tenant via Email')
                            ->default(true)
                            ->helperText('Kirim invoice dan aktivasi Reseller Topup lewat email.'),
                    ])
                    ->collapsible()
                    ->extraAttributes($this->onboardingSectionAttributes('invoice-delivery')),
                    
                // Payment Accounts
                Section::make('Nomor E-Wallet Manual')
                    ->description('Nomor admin yang ditampilkan untuk pembayaran manual e-wallet.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ovo_admin')
                            ->label('OVO 1')
                            ->tel(),
                            
                        TextInput::make('ovo1_admin')
                            ->label('OVO 2')
                            ->tel(),
                            
                        TextInput::make('gopay_admin')
                            ->label('GoPay 1')
                            ->tel(),
                            
                        TextInput::make('gopay1_admin')
                            ->label('GoPay 2')
                            ->tel(),
                            
                        TextInput::make('dana_admin')
                            ->label('DANA')
                            ->tel(),
                            
                        TextInput::make('shopeepay_admin')
                            ->label('ShopeePay')
                            ->tel(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('ewallet-accounts')),
                    
                Section::make('Rekening Bank Manual')
                    ->description('Nomor rekening bank admin untuk pembayaran manual.')
                    ->schema([
                        TextInput::make('bca_admin')
                            ->label('Nomor Rekening BCA')
                            ->numeric(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('bank-account')),
                    
                // Tier Markup Settings
                Section::make('Tier Markup Settings')
                    ->description('Set default markup percentage for each selling tier')
                    ->columns([
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextInput::make('profit_member')
                            ->label('Member / Publik Markup (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->helperText('Dipakai untuk seed harga publik dan member dari harga modal'),
                            
                        TextInput::make('profit_gold')
                            ->label('Gold Markup (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                            
                        TextInput::make('profit_platinum')
                            ->label('Platinum Markup (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('tier-markup')),

                // Tier System Configuration
                Section::make('Tier System Configuration')
                    ->description('Set transaction thresholds for automatic role upgrades')
                    ->columns(2)
                    ->schema([
                        TextInput::make('trx_count_gold')
                            ->label('Gold Tier Threshold')
                            ->helperText('Jumlah transaksi sukses untuk naik ke Gold')
                            ->numeric()
                            ->default(50)
                            ->required(),

                        TextInput::make('trx_count_platinum')
                            ->label('Platinum Tier Threshold')
                            ->helperText('Jumlah transaksi sukses untuk naik ke Platinum')
                            ->numeric()
                            ->default(100)
                            ->required(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes($this->onboardingSectionAttributes('tier-system')),

                // Point System Configuration
                Section::make('Point System Configuration')
                    ->description('Atur sistem reward poin untuk user yang melakukan transaksi')
                    ->columns(3)
                    ->schema([
                        TextInput::make('point_per_nominal')
                            ->label('Poin per Rp 1.000')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->required()
                            ->helperText('Jumlah poin yang didapat tiap Rp 1.000 belanja. Contoh: 1 = tiap Rp 1.000 dapat 1 poin'),

                        TextInput::make('point_value')
                            ->label('Nilai 1 Poin (Rp)')
                            ->numeric()
                            ->default(100)
                            ->minValue(1)
                            ->required()
                            ->prefix('Rp')
                            ->helperText('1 poin setara berapa rupiah diskon. Contoh: 100 = 1 poin = Rp 100'),

                        TextInput::make('max_point_usage_percent')
                            ->label('Maks. Penggunaan Poin (%)')
                            ->numeric()
                            ->default(50)
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required()
                            ->helperText('Batas maksimal % harga yang bisa dibayar dengan poin. Contoh: 50 = maksimal 50% harga bisa pakai poin'),
                    ])
                    ->collapsible()
                    ->extraAttributes($this->onboardingSectionAttributes('point-system')),
        ];

        return $schema
            ->components($this->filterSections($sections))
            ->statePath('data');
    }

    private function onboardingSectionAttributes(string $key): array
    {
        return [
            'data-onboarding-target' => 'settings-' . $key,
        ];
    }

    /**
     * @return array<string>|null
     */
    protected function getVisibleSectionHeadings(): ?array
    {
        return null;
    }

    /**
     * @return array<string>|null
     */
    protected function getSettingFieldWhitelist(): ?array
    {
        return null;
    }

    /**
     * @param  array<Component>  $sections
     * @return array<Component>
     */
    private function filterSections(array $sections): array
    {
        $visibleHeadings = $this->getVisibleSectionHeadings();

        if (! is_array($visibleHeadings) || $visibleHeadings === []) {
            return $sections;
        }

        return array_values(array_filter($sections, function (Component $section) use ($visibleHeadings): bool {
            if (! method_exists($section, 'getHeading')) {
                return true;
            }

            $heading = (string) ($section->getHeading() ?? '');

            return in_array($heading, $visibleHeadings, true);
        }));
    }
    
    protected function getSettingsData(): array
    {
        // Load data from database
        $settings = SettingWeb::first();
        
        $data = $settings?->toArray() ?? [];

        $data['mail_mailer'] ??= env('MAIL_MAILER', 'smtp');
        $data['mail_host'] ??= env('MAIL_HOST', 'smtp.mailgun.org');
        $data['mail_port'] ??= env('MAIL_PORT', 587);
        $data['mail_username'] ??= env('MAIL_USERNAME');
        $data['mail_password'] ??= env('MAIL_PASSWORD');
        $data['mail_encryption'] ??= env('MAIL_ENCRYPTION', 'tls');
        $data['mail_from_address'] ??= env('MAIL_FROM_ADDRESS', 'hello@example.com');
        $data['mail_from_name'] ??= env('MAIL_FROM_NAME', 'Example');
        $data['invoice_notify_via_whatsapp'] = array_key_exists('invoice_notify_via_whatsapp', $data)
            ? (bool) $data['invoice_notify_via_whatsapp']
            : true;
        $data['invoice_notify_via_email'] = array_key_exists('invoice_notify_via_email', $data)
            ? (bool) $data['invoice_notify_via_email']
            : true;
        $data['affiliate_notify_via_whatsapp'] = array_key_exists('affiliate_notify_via_whatsapp', $data)
            ? (bool) $data['affiliate_notify_via_whatsapp']
            : true;
        $data['affiliate_notify_via_email'] = array_key_exists('affiliate_notify_via_email', $data)
            ? (bool) $data['affiliate_notify_via_email']
            : true;
        $data['tenant_notify_via_whatsapp'] = array_key_exists('tenant_notify_via_whatsapp', $data)
            ? (bool) $data['tenant_notify_via_whatsapp']
            : true;
        $data['tenant_notify_via_email'] = array_key_exists('tenant_notify_via_email', $data)
            ? (bool) $data['tenant_notify_via_email']
            : true;
        $data['home_popup_enabled'] = array_key_exists('home_popup_enabled', $data)
            ? (bool) $data['home_popup_enabled']
            : true;
        $data['live_sales_enabled'] = array_key_exists('live_sales_enabled', $data)
            ? (bool) $data['live_sales_enabled']
            : true;
        $data['gtm_custom_head_script'] ??= null;
        $data['gtm_custom_body_noscript'] ??= null;
        $data['wa_provider'] ??= 'fonnte';
        $data['easywa_email'] ??= null;
        $data['easywa_secret_key'] ??= null;
        $data['easywa_send_type'] ??= 'sync';
        $data['easywa_send_delay'] ??= 0;
        $data['captcha_site_key'] ??= env('NOCAPTCHA_SITEKEY');
        $data['captcha_secret'] ??= env('NOCAPTCHA_SECRET');
        $data['google_client_id'] ??= env('GOOGLE_CLIENT_ID');
        $data['captcha_enabled'] = array_key_exists('captcha_enabled', $data)
            ? (bool) $data['captcha_enabled']
            : filter_var((string) env('ADMIN_LOGIN_CAPTCHA_ENABLED', 'true'), FILTER_VALIDATE_BOOL);
        $data['captcha_bypass'] = array_key_exists('captcha_bypass', $data)
            ? (bool) $data['captcha_bypass']
            : false;
        $data['seasonal_enabled'] = array_key_exists('seasonal_enabled', $data)
            ? (bool) $data['seasonal_enabled']
            : false;
        $data['seasonal_mode'] ??= 'manual';
        $data['seasonal_theme'] ??= 'ramadhan';
        $data['seasonal_effect_intensity'] ??= 'subtle';
        $data['seasonal_starts_at'] ??= null;
        $data['seasonal_ends_at'] ??= null;
        $data['seasonal_background_image'] ??= null;
        $data['seasonal_background_opacity'] ??= 38;
        $data['seo_robots_enabled'] = array_key_exists('seo_robots_enabled', $data)
            ? (bool) $data['seo_robots_enabled']
            : true;
        $data['seo_sitemap_enabled'] = array_key_exists('seo_sitemap_enabled', $data)
            ? (bool) $data['seo_sitemap_enabled']
            : true;
        $data['seo_sitemap_include_categories'] = array_key_exists('seo_sitemap_include_categories', $data)
            ? (bool) $data['seo_sitemap_include_categories']
            : true;
        $data['seo_sitemap_include_articles'] = array_key_exists('seo_sitemap_include_articles', $data)
            ? (bool) $data['seo_sitemap_include_articles']
            : true;
        $data['seo_sitemap_cache_minutes'] = max(5, (int) ($data['seo_sitemap_cache_minutes'] ?? 30));
        $seoSitemapMode = (string) ($data['seo_sitemap_mode'] ?? 'dynamic');
        $data['seo_sitemap_mode'] = in_array($seoSitemapMode, ['dynamic', 'custom_upload'], true)
            ? $seoSitemapMode
            : 'dynamic';
        $data['seo_sitemap_index_asset_id'] = isset($data['seo_sitemap_index_asset_id']) ? (int) $data['seo_sitemap_index_asset_id'] : null;
        $data['seo_sitemap_main_asset_id'] = isset($data['seo_sitemap_main_asset_id']) ? (int) $data['seo_sitemap_main_asset_id'] : null;
        $data['seo_sitemap_categories_asset_id'] = isset($data['seo_sitemap_categories_asset_id']) ? (int) $data['seo_sitemap_categories_asset_id'] : null;
        $data['seo_robots_custom_lines'] ??= null;
        $data['public_theme'] = PublicThemeRegistry::normalize($data['public_theme'] ?? null);
        $data = $this->hydrateMediaFieldState($data);

        return $data;
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        $data['seo_sitemap_cache_minutes'] = max(5, min(1440, (int) ($data['seo_sitemap_cache_minutes'] ?? 30)));
        $seoSitemapMode = (string) ($data['seo_sitemap_mode'] ?? 'dynamic');
        $data['seo_sitemap_mode'] = in_array($seoSitemapMode, ['dynamic', 'custom_upload'], true)
            ? $seoSitemapMode
            : 'dynamic';
        $data['public_theme'] = PublicThemeRegistry::normalize($data['public_theme'] ?? null);
        $data['seo_sitemap_index_asset_id'] = ! empty($data['seo_sitemap_index_asset_id']) ? (int) $data['seo_sitemap_index_asset_id'] : null;
        $data['seo_sitemap_main_asset_id'] = ! empty($data['seo_sitemap_main_asset_id']) ? (int) $data['seo_sitemap_main_asset_id'] : null;
        $data['seo_sitemap_categories_asset_id'] = ! empty($data['seo_sitemap_categories_asset_id']) ? (int) $data['seo_sitemap_categories_asset_id'] : null;

        $sitemapValidation = $this->validateCustomSitemapSelection($data);
        if (! $sitemapValidation['ok']) {
            $summary = implode(' | ', array_slice($sitemapValidation['errors'], 0, 2));
            if (count($sitemapValidation['errors']) > 2) {
                $summary .= ' | +' . (count($sitemapValidation['errors']) - 2) . ' error lainnya.';
            }

            Notification::make()
                ->title('Pengaturan SEO belum valid')
                ->body($summary)
                ->danger()
                ->send();

            return;
        }

        if (
            ($data['seasonal_mode'] ?? 'manual') === 'date_range' &&
            ! empty($data['seasonal_starts_at']) &&
            ! empty($data['seasonal_ends_at']) &&
            strtotime((string) $data['seasonal_ends_at']) < strtotime((string) $data['seasonal_starts_at'])
        ) {
            Notification::make()
                ->title('Jadwal tema musiman tidak valid')
                ->body('Tanggal berakhir harus lebih besar atau sama dengan tanggal mulai.')
                ->danger()
                ->send();

            return;
        }
        
        // Get or create settings record
        $settings = SettingWeb::firstOrNew(['id' => 1]);

        $this->applyMediaLibrarySelectionToData($data);

        $previousPwaIconSource = (string) ($settings->pwa_icon_source ?? '');

        // Jangan timpa logo yang sudah ada dengan nilai kosong.
        foreach (['logo_header', 'logo_footer', 'logo_favicon', 'seasonal_background_image', 'pwa_icon_source'] as $logoField) {
            if (empty($data[$logoField]) && !empty($settings->{$logoField})) {
                $data[$logoField] = $settings->{$logoField};
            }
        }

        if (empty($data['mail_password']) && !empty($settings->mail_password)) {
            $data['mail_password'] = $settings->mail_password;
        }

        if (empty($data['easywa_secret_key']) && !empty($settings->easywa_secret_key)) {
            $data['easywa_secret_key'] = $settings->easywa_secret_key;
        }

        if (empty($data['captcha_secret']) && !empty($settings->captcha_secret)) {
            $data['captcha_secret'] = $settings->captcha_secret;
        }

        $this->stripMediaFormOnlyFields($data);
        
        // Check if WA Number changed and trigger API update
        if (
            ($data['wa_provider'] ?? 'fonnte') === 'fonnte' &&
            isset($data['wa_number']) &&
            $settings->wa_number !== $data['wa_number']
        ) {
             $this->changeNumber($data['wa_number'], $data['wa_key'] ?? $settings->wa_key);
        }
        
        $data = $this->filterStateByWhitelist($data);

        // Update all fields
        $settings->fill($data);
        $settings->save();

        if ($this->shouldRegeneratePwaIcons($previousPwaIconSource, (string) ($settings->pwa_icon_source ?? ''))) {
            try {
                app(PwaIconGeneratorService::class)->generate(
                    (string) $settings->pwa_icon_source,
                    $settings->warna1 ?: ($settings->warna4 ?: '#111111'),
                );
                $settings->forceFill(['pwa_icon_generated_at' => now()])->save();
            } catch (\Throwable $exception) {
                report($exception);

                Notification::make()
                    ->title('Icon PWA belum bisa dibuat')
                    ->body('Cek kembali gambar yang diupload, lalu simpan ulang.')
                    ->danger()
                    ->send();

                return;
            }
        }

        $this->optimizeManagedMediaFields($settings);
        \Illuminate\Support\Facades\Cache::forget('public:active-theme');
        \Illuminate\Support\Facades\Cache::forget('seo:sitemap:index:v3');
        \Illuminate\Support\Facades\Cache::forget('seo:sitemap:main:v3');
        \Illuminate\Support\Facades\Cache::forget('seo:sitemap:categories:v3');
        
        Notification::make()
            ->title('Pengaturan Tersimpan')
            ->body('Pengaturan website berhasil diperbarui.')
            ->success()
            ->send();
    }

    private function shouldRegeneratePwaIcons(string $previousSource, string $currentSource): bool
    {
        return $currentSource !== '' && $currentSource !== $previousSource;
    }

    private function filterStateByWhitelist(array $data): array
    {
        $whitelist = $this->getSettingFieldWhitelist();

        if (! is_array($whitelist) || $whitelist === []) {
            return $data;
        }

        return array_intersect_key($data, array_flip($whitelist));
    }

    private function hydrateMediaFieldState(array $data): array
    {
        foreach ($this->getManagedMediaFields() as $field) {
            $assetId = $this->resolveAssetIdFromStoredPath($data[$field] ?? null);
            $data["{$field}_media_asset_id"] = $assetId;
            $data["{$field}_input_mode"] = $assetId ? 'library' : 'upload';
        }

        return $data;
    }

    private function applyMediaLibrarySelectionToData(array &$data): void
    {
        foreach ($this->getManagedMediaFields() as $field) {
            $mode = (string) ($data["{$field}_input_mode"] ?? 'upload');

            if ($mode !== 'library') {
                continue;
            }

            $assetId = $data["{$field}_media_asset_id"] ?? null;

            if (! MediaAssetPicker::isUsable($assetId)) {
                continue;
            }

            $asset = MediaAsset::find($assetId);
            $relativePath = $asset?->resolveRelativePath();

            if (filled($relativePath)) {
                $data[$field] = ltrim((string) $relativePath, '/');
            }
        }
    }

    private function stripMediaFormOnlyFields(array &$data): void
    {
        foreach ($this->getManagedMediaFields() as $field) {
            unset($data["{$field}_input_mode"], $data["{$field}_media_asset_id"]);
        }
    }

    private function getManagedMediaFields(): array
    {
        return [
            'logo_header',
            'logo_footer',
            'logo_favicon',
            'seasonal_background_image',
        ];
    }

    private function optimizeManagedMediaFields(SettingWeb $settings): void
    {
        $optimizer = app(OptimizedImageService::class);

        foreach ($this->getManagedMediaFields() as $field) {
            $path = $settings->{$field} ?? null;

            if (! $path) {
                continue;
            }

            $optimizer->ensureVariants(
                $path,
                $field === 'seasonal_background_image' ? 'banner' : 'thumbnail',
            );
        }
    }

    private function resolveAssetIdFromStoredPath(?string $path): ?int
    {
        $normalizedPath = trim((string) $path);

        if ($normalizedPath === '' || filter_var($normalizedPath, FILTER_VALIDATE_URL)) {
            return null;
        }

        $candidates = array_values(array_unique([
            '/' . ltrim($normalizedPath, '/'),
            ltrim($normalizedPath, '/'),
        ]));

        $id = MediaAsset::query()
            ->whereIn('path', $candidates)
            ->value('id');

        return $id ? (int) $id : null;
    }

    protected function changeNumber($number, $waKey)
    {
        try {
            \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => $waKey,
            ])->post("https://solo.wablas.com/api/device/change-number", [
                'phone' => $number,
            ]);
        } catch (\Exception $e) {
            // Log error or silently fail if not critical
            \Illuminate\Support\Facades\Log::error('Failed to change WA number: ' . $e->getMessage());
        }
    }

    protected function makeValidateSitemapXmlAction(): Action
    {
        return Action::make('validate_sitemap_xml')
            ->label('Validate XML')
            ->icon('heroicon-o-document-check')
            ->color('gray')
            ->action(function (): void {
                $state = $this->form->getState();

                if (! (bool) ($state['seo_sitemap_enabled'] ?? true)) {
                    Notification::make()
                        ->title('Sitemap nonaktif')
                        ->body('Aktifkan sitemap terlebih dahulu sebelum validasi.')
                        ->warning()
                        ->send();

                    return;
                }

                if (($state['seo_sitemap_mode'] ?? 'dynamic') !== 'custom_upload') {
                    Notification::make()
                        ->title('Mode masih Dynamic')
                        ->body('Validasi XML custom hanya berlaku saat mode sitemap = Custom Upload.')
                        ->warning()
                        ->send();

                    return;
                }

                $result = $this->validateCustomSitemapSelection($state);

                if ($result['ok']) {
                    Notification::make()
                        ->title('Validasi sitemap custom berhasil')
                        ->body(implode(' | ', $result['ok_messages']))
                        ->success()
                        ->send();

                    return;
                }

                $summary = implode(' | ', array_slice($result['errors'], 0, 2));
                if (count($result['errors']) > 2) {
                    $summary .= ' | +' . (count($result['errors']) - 2) . ' error lainnya.';
                }

                Notification::make()
                    ->title('Validasi sitemap custom gagal')
                    ->body($summary)
                    ->danger()
                    ->send();
            });
    }

    private function validateCustomSitemapSelection(array $state): array
    {
        if (! (bool) ($state['seo_sitemap_enabled'] ?? true)) {
            return ['ok' => true, 'ok_messages' => [], 'errors' => []];
        }

        if (($state['seo_sitemap_mode'] ?? 'dynamic') !== 'custom_upload') {
            return ['ok' => true, 'ok_messages' => [], 'errors' => []];
        }

        $targets = $this->buildSitemapValidationTargets($state);
        $okMessages = [];
        $errorMessages = [];

        foreach ($targets as $label => $target) {
            $result = $this->validateSitemapXmlAsset($target['asset_id'], $target['expected_root']);

            if ($result['ok']) {
                $okMessages[] = $label . ': ' . $result['message'];
            } else {
                $errorMessages[] = $label . ': ' . $result['message'];
            }
        }

        return [
            'ok' => $errorMessages === [],
            'ok_messages' => $okMessages,
            'errors' => $errorMessages,
        ];
    }

    private function buildSitemapValidationTargets(array $state): array
    {
        $targets = [
            'sitemap.xml (index)' => [
                'asset_id' => isset($state['seo_sitemap_index_asset_id']) ? (int) $state['seo_sitemap_index_asset_id'] : null,
                'expected_root' => 'sitemapindex',
            ],
            'sitemap-main.xml' => [
                'asset_id' => isset($state['seo_sitemap_main_asset_id']) ? (int) $state['seo_sitemap_main_asset_id'] : null,
                'expected_root' => 'urlset',
            ],
        ];

        if ((bool) ($state['seo_sitemap_include_categories'] ?? true)) {
            $targets['sitemap-categories.xml'] = [
                'asset_id' => isset($state['seo_sitemap_categories_asset_id']) ? (int) $state['seo_sitemap_categories_asset_id'] : null,
                'expected_root' => 'urlset',
            ];
        }

        return $targets;
    }

    private function validateSitemapXmlAsset(?int $assetId, string $expectedRoot): array
    {
        if (! $assetId) {
            return ['ok' => false, 'message' => 'file belum dipilih'];
        }

        $asset = MediaAsset::query()->find($assetId);
        if (! $asset) {
            return ['ok' => false, 'message' => 'asset tidak ditemukan'];
        }

        $absolutePath = $asset->resolveAbsolutePath();
        if (! $absolutePath || ! is_file($absolutePath)) {
            return ['ok' => false, 'message' => 'file fisik tidak ditemukan'];
        }

        $xmlRaw = @file_get_contents($absolutePath);
        if (! is_string($xmlRaw) || trim($xmlRaw) === '') {
            return ['ok' => false, 'message' => 'file kosong / tidak terbaca'];
        }

        $previousInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string($xmlRaw);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousInternalErrors);

        if ($xml === false) {
            $firstError = $errors[0]->message ?? 'XML tidak valid';

            return ['ok' => false, 'message' => trim((string) $firstError)];
        }

        $root = strtolower((string) $xml->getName());
        if ($root !== strtolower($expectedRoot)) {
            return ['ok' => false, 'message' => "root harus {$expectedRoot}, ditemukan {$root}"];
        }

        $locNodes = $root === 'sitemapindex'
            ? ($xml->xpath('//*[local-name()="sitemap"]/*[local-name()="loc"]') ?: [])
            : ($xml->xpath('//*[local-name()="url"]/*[local-name()="loc"]') ?: []);

        $count = count($locNodes);
        if ($count < 1) {
            return ['ok' => false, 'message' => $root === 'sitemapindex'
                ? 'sitemapindex tidak memiliki node <sitemap><loc>'
                : 'urlset tidak memiliki node <url><loc>'];
        }

        $expectedHost = strtolower((string) parse_url(url('/'), PHP_URL_HOST));
        $mismatchCount = 0;
        $adminLikeCount = 0;

        foreach ($locNodes as $locNode) {
            $loc = trim((string) $locNode);
            if ($loc === '') {
                continue;
            }

            $host = strtolower((string) parse_url($loc, PHP_URL_HOST));
            $path = (string) (parse_url($loc, PHP_URL_PATH) ?? '');

            if ($host === '') {
                continue;
            }

            if ($expectedHost !== '' && $host !== $expectedHost) {
                $mismatchCount++;
            }

            if (str_starts_with($host, 'admin.') || str_contains($host, '.admin.') || str_starts_with($path, '/admin')) {
                $adminLikeCount++;
            }
        }

        if ($mismatchCount > 0 || $adminLikeCount > 0) {
            $issues = [];
            if ($mismatchCount > 0) {
                $issues[] = "{$mismatchCount} loc beda host (harus {$expectedHost})";
            }
            if ($adminLikeCount > 0) {
                $issues[] = "{$adminLikeCount} loc mengarah ke admin subdomain/path";
            }

            return ['ok' => false, 'message' => implode(' | ', $issues)];
        }

        return ['ok' => true, 'message' => "valid ({$count} loc entries, host {$expectedHost})"];
    }

    protected function makeSendTestEmailAction(): Action
    {
        return Action::make('send_test_email')
            ->label('Send Test Email')
            ->icon('heroicon-o-envelope')
            ->color('info')
            ->form([
                TextInput::make('email')
                    ->label('Email Tujuan')
                    ->email()
                    ->required(),
            ])
            ->action(function (array $data, EmailNotificationService $emailNotificationService): void {
                $sent = $emailNotificationService->sendTestEmail($data['email']);

                Notification::make()
                    ->title($sent ? 'Test email terkirim' : 'Test email gagal')
                    ->body($sent ? 'Cek inbox email tujuan.' : 'Cek konfigurasi SMTP dan log aplikasi.')
                    ->{$sent ? 'success' : 'danger'}()
                    ->send();
            });
    }

    protected function makeSendTestWhatsappAction(): Action
    {
        return Action::make('send_test_whatsapp')
            ->label('Send Test WhatsApp')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->form([
                TextInput::make('target')
                    ->label('Nomor Tujuan')
                    ->required()
                    ->helperText('Contoh: 08123456789'),
                TextInput::make('message')
                    ->label('Pesan Test')
                    ->default('Test WhatsApp dari halaman settings admin.')
                    ->required(),
            ])
            ->action(function (array $data, WhatsappNotificationService $whatsappNotificationService): void {
                $result = $whatsappNotificationService->sendTestMessage($data['target'], $data['message']);
                $success = (bool) ($result['success'] ?? false);
                $body = trim((string) ($result['message'] ?? 'Unknown response'));

                if (($result['provider'] ?? null) === 'fonnte' && ! empty($result['request_id'])) {
                    $body .= ' | request id: ' . $result['request_id'];
                }

                Notification::make()
                    ->title($success ? 'Test WhatsApp terkirim' : 'Test WhatsApp gagal')
                    ->body($body)
                    ->{$success ? 'success' : 'danger'}()
                    ->send();
            });
    }

    protected function makeCheckWhatsappStatusAction(): Action
    {
        return Action::make('check_whatsapp_status')
            ->label('Check WA Status')
            ->icon('heroicon-o-signal')
            ->color('gray')
            ->action(function (WhatsappNotificationService $whatsappNotificationService): void {
                $result = $whatsappNotificationService->getProviderStatus();
                $success = (bool) ($result['success'] ?? false);
                $status = strtoupper((string) ($result['status'] ?? 'UNKNOWN'));

                Notification::make()
                    ->title($success ? 'Status WhatsApp Provider' : 'Gagal cek status WhatsApp')
                    ->body($success ? ($status . ' - ' . ($result['message'] ?? '')) : ($result['message'] ?? 'Unknown response'))
                    ->{$success ? 'success' : 'danger'}()
                    ->send();
            });
    }
}

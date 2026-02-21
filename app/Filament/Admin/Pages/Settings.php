<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use App\Models\SettingWeb;
use BackedEnum;
use UnitEnum;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static UnitEnum|string|null $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 1;
    
    protected string $view = 'filament.admin.pages.settings';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill($this->getSettingsData());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Website Information
                Section::make('Website Information')
                    ->description('Basic information about your website')
                    ->columns(2)
                    ->schema([
                        TextInput::make('judul_web')
                            ->label('Website Title')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Judul utama website yang tampil di browser'),
                            
                        TextInput::make('order_prefik')
                            ->label('Order Prefix')
                            ->helperText('Prefix untuk ID Order (contoh: INV, ORD). Maksimal 10 karakter'),
                            
                        Textarea::make('deskripsi_web')
                            ->label('Website Description')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Deskripsi singkat website untuk SEO dan sharing link'),
                            
                        Textarea::make('keywords')
                            ->label('SEO Keywords')
                            ->rows(2)
                            ->helperText('Kata kunci pencarian, pisahkan dengan koma (contoh: topup, game, murah)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Analytics & Tracking
                Section::make('Analytics & Tracking')
                    ->description('Google Analytics, Facebook Pixel, and Tag Manager')
                    ->columns(2)
                    ->schema([
                        TextInput::make('google_analytics_id')
                            ->label('Google Analytics 4 (GA4) ID')
                            ->placeholder('G-XXXXXXXXXX')
                            ->helperText('Measurement ID dari GA4'),
                        
                        TextInput::make('facebook_pixel_id')
                            ->label('Facebook Pixel ID')
                            ->placeholder('XXXXXXXXXXXXXXX')
                            ->helperText('Pixel ID (Angka saja)'),

                        TextInput::make('google_tag_manager_id')
                            ->label('Google Tag Manager ID')
                            ->placeholder('GTM-XXXXXXX')
                            ->helperText('Container ID dari GTM (Optional)'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // Branding
                Section::make('Logo & Colors')
                    ->description('Upload logos and set color theme')
                    ->columns([
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        FileUpload::make('logo_header')
                            ->label('Header Logo')
                            ->image()
                            ->disk('assets')
                            ->visibility('public')
                            ->directory('assets/logo')
                            ->maxSize(2048)
                            ->columnSpan(1),
                            
                        FileUpload::make('logo_footer')
                            ->label('Footer Logo')
                            ->image()
                            ->disk('assets')
                            ->visibility('public')
                            ->directory('assets/logo')
                            ->maxSize(2048)
                            ->columnSpan(1),
                            
                        FileUpload::make('logo_favicon')
                            ->label('Favicon')
                            ->image()
                            ->disk('assets')
                            ->visibility('public')
                            ->directory('assets/logo')
                            ->helperText('16x16 or 32x32 px')
                            ->columnSpan(1),
                            
                        ColorPicker::make('warna1')
                            ->label('Primary Color')
                            ->columnSpan(1),
                            
                        ColorPicker::make('warna2')
                            ->label('Secondary Color')
                            ->columnSpan(1),
                            
                        ColorPicker::make('warna3')
                            ->label('Accent Color')
                            ->columnSpan(1),
                            
                        ColorPicker::make('warna4')
                            ->label('Background Color')
                            ->columnSpan(1),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // Social Media
                Section::make('Social Media Links')
                    ->description('Add your social media profile URLs')
                    ->columns(2)
                    ->schema([
                        TextInput::make('url_wa')
                            ->label('WhatsApp URL')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Link WhatsApp Business (wa.me/...)'),
                            
                        TextInput::make('url_ig')
                            ->label('Instagram URL')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Link profil Instagram'),
                            
                        TextInput::make('url_tiktok')
                            ->label('TikTok URL')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Link profil TikTok'),
                            
                        TextInput::make('url_youtube')
                            ->label('YouTube URL')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Link channel YouTube'),
                            
                        TextInput::make('url_fb')
                            ->label('Facebook URL')
                            ->url()
                            ->prefix('https://')
                            ->helperText('Link halaman Facebook'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // Top-Up Providers
                Section::make('TopUpIndo')
                    ->schema([
                        TextInput::make('topupindo_api')
                            ->label('TopUpIndo API Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('BangJeff')
                    ->schema([
                        TextInput::make('apikey_bangjeff')
                            ->label('BangJeff API Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('Aoshi')
                    ->schema([
                        TextInput::make('apikey_aoshi')
                            ->label('Aoshi API Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('Mobile Game Store')
                    ->schema([
                        TextInput::make('api_mobilegamestore')
                            ->label('Mobile Game Store API Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('VIP Reseller')
                    ->columns(2)
                    ->schema([
                        TextInput::make('vip_apiid')
                            ->label('VIP API ID'),
                            
                        TextInput::make('vip_apikey')
                            ->label('VIP API Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // Payment Gateways
                Section::make('Deposit Configuration')
                    ->description('Select the active gateway for QRIS Deposits')
                    ->schema([
                        \Filament\Forms\Components\Select::make('deposit_jalur')
                            ->label('Active Deposit Gateway (QRIS)')
                            ->options([
                                'duitku' => 'Duitku',
                                'tripay' => 'TriPay',
                                'tokopay' => 'TokoPay',
                            ])
                            ->default('duitku')
                            ->required(),
                    ])
                    ->collapsible(),

                Section::make('PayDisini')
                    ->schema([
                        TextInput::make('paydisini_apikey')
                            ->label('PayDisini API Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('Tripay')
                    ->columns(3)
                    ->schema([
                        TextInput::make('tripay_api')
                            ->label('API Key')
                            ->password()
                            ->revealable(),
                            
                        TextInput::make('tripay_merchant_code')
                            ->label('Merchant Code'),
                            
                        TextInput::make('tripay_private_key')
                            ->label('Private Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('TokoPay')
                    ->columns(2)
                    ->schema([
                        TextInput::make('tokopay_merchant_id')
                            ->label('Merchant ID'),
                            
                        TextInput::make('tokopay_secret_key')
                            ->label('Secret Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('Duitku')
                    ->description('Configure Duitku payment gateway. Callback URL: '.config('app.url').'/wejizy/duitku/callback')
                    ->columns(2)
                    ->schema([
                        TextInput::make('duitku_merchant_code')
                            ->label('Merchant Code')
                            ->helperText('Merchant Code dari Duitku dashboard')
                            ->columnSpan(1),
                            
                        TextInput::make('duitku_merchant_key')
                            ->label('Merchant Key (API Key)')
                            ->password()
                            ->revealable()
                            ->helperText('API Key untuk autentikasi')
                            ->columnSpan(1),
                            
                        TextInput::make('duitku_callback_url')
                            ->label('Callback URL')
                            ->url()
                            ->default(config('app.url').'/wejizy/duitku/callback')
                            ->helperText('URL untuk menerima notifikasi pembayaran dari Duitku')
                            ->columnSpan(1),
                            
                        TextInput::make('duitku_return_url')
                            ->label('Return URL')
                            ->url()
                            ->default(config('app.url').'/id/invoices/')
                            ->helperText('URL redirect setelah pembayaran selesai')
                            ->columnSpan(1),
                            
                        \Filament\Forms\Components\Select::make('duitku_mode')
                            ->label('Mode')
                            ->options([
                                'sandbox' => 'Sandbox (Testing)',
                                'production' => 'Production (Live)',
                            ])
                            ->default('sandbox')
                            ->required()
                            ->helperText('Pilih sandbox untuk testing, production untuk live')
                            ->columnSpan(1),
                            
                        Toggle::make('duitku_enabled')
                            ->label('Enable Duitku Payment')
                            ->helperText('Aktifkan untuk menggunakan Duitku sebagai payment gateway')
                            ->default(false)
                            ->columnSpan(1),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('Digiflazz')
                    ->columns(2)
                    ->schema([
                        TextInput::make('username_digi')
                            ->label('Username'),
                            
                        TextInput::make('api_key_digi')
                            ->label('API Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('API Games')
                    ->columns(2)
                    ->schema([
                        TextInput::make('apigames_merchant')
                            ->label('Merchant ID'),
                            
                        TextInput::make('apigames_secret')
                            ->label('Secret Key')
                            ->password()
                            ->revealable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // WhatsApp Integration
                Section::make('WhatsApp Configuration')
                    ->description('Configure WhatsApp API integration')
                    ->columns(3)
                    ->schema([
                        TextInput::make('nomor_admin')
                            ->label('Admin Phone Number')
                            ->tel()
                            ->prefix('+62')
                            ->helperText('Nomor HP admin utama untuk notifikasi sistem (Format: 812...)'),
                            
                        TextInput::make('wa_key')
                            ->label('WhatsApp API Key')
                            ->password()
                            ->revealable(),
                            
                        TextInput::make('wa_number')
                            ->label('WhatsApp Number')
                            ->tel()
                            ->prefix('+62'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // Payment Accounts
                Section::make('E-Wallet Accounts')
                    ->description('Admin account numbers for manual payments')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ovo_admin')
                            ->label('OVO Account 1')
                            ->tel(),
                            
                        TextInput::make('ovo1_admin')
                            ->label('OVO Account 2')
                            ->tel(),
                            
                        TextInput::make('gopay_admin')
                            ->label('GoPay Account 1')
                            ->tel(),
                            
                        TextInput::make('gopay1_admin')
                            ->label('GoPay Account 2')
                            ->tel(),
                            
                        TextInput::make('dana_admin')
                            ->label('DANA Account')
                            ->tel(),
                            
                        TextInput::make('shopeepay_admin')
                            ->label('ShopeePay Account')
                            ->tel(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('Bank Account')
                    ->schema([
                        TextInput::make('bca_admin')
                            ->label('BCA Account Number')
                            ->numeric(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // Profit Settings
                Section::make('Profit Settings')
                    ->description('Set profit percentage for each user role')
                    ->columns([
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->schema([
                        TextInput::make('profit_public')
                            ->label('Public User Profit (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->helperText('Keuntungan dari user yang belum login'),
                            
                        TextInput::make('profit_member')
                            ->label('Member Profit (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                            
                        TextInput::make('profit_gold')
                            ->label('Gold Member Profit (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                            
                        TextInput::make('profit_platinum')
                            ->label('Platinum Member Profit (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                    ])
                    ->collapsible()
                    ->collapsed(),

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
                    ->collapsed(),
            ])
            ->statePath('data');
    }
    
    protected function getSettingsData(): array
    {
        // Load data from database
        $settings = SettingWeb::first();
        
        if (!$settings) {
            // Return empty array if no settings exist yet
            return [];
        }
        
        return $settings->toArray();
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        // Get or create settings record
        $settings = SettingWeb::firstOrNew(['id' => 1]);
        
        // Check if WA Number changed and trigger API update
        if (isset($data['wa_number']) && $settings->wa_number !== $data['wa_number']) {
             $this->changeNumber($data['wa_number'], $data['wa_key'] ?? $settings->wa_key);
        }
        
        // Update all fields
        $settings->fill($data);
        $settings->save();
        
        Notification::make()
            ->title('Pengaturan Tersimpan')
            ->body('Pengaturan website berhasil diperbarui.')
            ->success()
            ->send();
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
}

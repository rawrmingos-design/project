<?php

namespace App\Filament\Admin\Pages;

use App\Models\SettingWeb;
use App\Services\EmailNotificationService;
use App\Services\WhatsappNotificationService;
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
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
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

                Section::make('Admin Login CAPTCHA')
                    ->description('Konfigurasi Google reCAPTCHA untuk halaman login admin Filament. Bisa diaktifkan/nonaktifkan dan disediakan bypass darurat.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('captcha_enabled')
                            ->label('Aktifkan CAPTCHA di Login Admin')
                            ->default(true)
                            ->helperText('Jika aktif, admin wajib verifikasi CAPTCHA saat login.'),

                        Toggle::make('captcha_bypass')
                            ->label('Bypass CAPTCHA (Darurat)')
                            ->default(false)
                            ->helperText('Jika aktif, CAPTCHA dilewati walaupun fitur CAPTCHA hidup. Gunakan hanya saat troubleshooting.'),

                        TextInput::make('captcha_site_key')
                            ->label('CAPTCHA Site Key')
                            ->helperText('Site key dari Google reCAPTCHA admin console untuk domain panel admin.')
                            ->columnSpan(1),

                        TextInput::make('captcha_secret')
                            ->label('CAPTCHA Secret Key')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key dari Google reCAPTCHA admin console. Simpan rahasia ini.')
                            ->columnSpan(1),
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
                            ->disk('assets')
                            ->visibility('public')
                            ->directory('assets/logo')
                            ->rules(['nullable', 'mimes:ico,png,svg,webp'])
                            ->maxSize(512)
                            ->helperText('Format .ico/.png/.svg/.webp (16x16 atau 32x32 px)')
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

                Section::make('Seasonal Theme')
                    ->description('Atur nuansa event musiman (contoh Ramadhan/Halloween) tanpa mengubah struktur halaman utama.')
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

                        FileUpload::make('seasonal_background_image')
                            ->label('Background Image (Opsional)')
                            ->image()
                            ->disk('assets')
                            ->visibility('public')
                            ->directory('assets/seasonal')
                            ->maxSize(4096)
                            ->columnSpanFull()
                            ->visible(fn (callable $get): bool => (bool) $get('seasonal_enabled'))
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
                    ->description('Masukkan API key dari dashboard TopUpIndo. Biasanya tersedia di menu API, developer, atau integrasi.')
                    ->schema([
                        TextInput::make('topupindo_api')
                            ->label('TopUpIndo API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Isi dengan API key rahasia dari TopUpIndo, bukan username atau email akun.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('BangJeff')
                    ->description('Masukkan API key dari dashboard BangJeff. Dipakai saat order dan sinkronisasi provider.')
                    ->schema([
                        TextInput::make('apikey_bangjeff')
                            ->label('BangJeff API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Didapat dari dashboard BangJeff pada menu API key / developer.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
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
                    ->collapsed(),
                    
                Section::make('Mobile Game Store')
                    ->description('Masukkan API key Mobile Game Store dari dashboard merchant.')
                    ->schema([
                        TextInput::make('api_mobilegamestore')
                            ->label('Mobile Game Store API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Didapat dari menu API atau integrasi pada dashboard Mobile Game Store.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('VIP Reseller')
                    ->description('Isi kredensial VIP Reseller. API ID dan API Key biasanya tersedia di dashboard akun VIP Reseller.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('vip_apiid')
                            ->label('VIP API ID')
                            ->helperText('ID merchant / API ID dari dashboard VIP Reseller.'),
                            
                        TextInput::make('vip_apikey')
                            ->label('VIP API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key API VIP Reseller.'),
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
                    ->description('Masukkan API key PayDisini dari dashboard merchant pada menu API atau integrasi.')
                    ->schema([
                        TextInput::make('paydisini_apikey')
                            ->label('PayDisini API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Kunci rahasia untuk membuat invoice dan cek status PayDisini.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('Tripay')
                    ->description('Isi seluruh kredensial TriPay dari dashboard akun TriPay. Biasanya ada di menu developer atau channel pembayaran.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('tripay_api')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->helperText('API key TriPay.'),
                            
                        TextInput::make('tripay_merchant_code')
                            ->label('Merchant Code')
                            ->helperText('Kode merchant dari dashboard TriPay.'),
                            
                        TextInput::make('tripay_private_key')
                            ->label('Private Key')
                            ->password()
                            ->revealable()
                            ->helperText('Private key untuk membuat signature request TriPay.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('TokoPay')
                    ->description('Masukkan Merchant ID dan Secret Key dari dashboard TokoPay.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('tokopay_merchant_id')
                            ->label('Merchant ID')
                            ->helperText('ID merchant dari dashboard TokoPay.'),
                            
                        TextInput::make('tokopay_secret_key')
                            ->label('Secret Key')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key/API key TokoPay.'),
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
                    ->description('Masukkan username dan API key buyer Digiflazz dari dashboard developer.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('username_digi')
                            ->label('Username')
                            ->helperText('Username buyer Digiflazz, biasanya sama seperti username akun buyer.'),
                            
                        TextInput::make('api_key_digi')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->helperText('API key buyer Digiflazz dari menu API.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                Section::make('API Games')
                    ->description('Masukkan Merchant ID dan Secret Key dari dashboard API Games.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('apigames_merchant')
                            ->label('Merchant ID')
                            ->helperText('Merchant ID dari dashboard API Games.'),
                            
                        TextInput::make('apigames_secret')
                            ->label('Secret Key')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key/API key API Games.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                    
                // WhatsApp Integration
                Section::make('WhatsApp Configuration')
                    ->description('Configure WhatsApp API integration')
                    ->headerActions([
                        $this->makeSendTestWhatsappAction(),
                        $this->makeCheckWhatsappStatusAction(),
                    ])
                    ->columns(3)
                    ->schema([
                        Select::make('wa_provider')
                            ->label('WhatsApp Provider')
                            ->options([
                                'fonnte' => 'Fonnte',
                                'easywa' => 'EasyWA',
                            ])
                            ->default('fonnte')
                            ->native(false)
                            ->helperText('Pilih provider WhatsApp yang aktif. Fonnte untuk token/device Fonnte, EasyWA untuk integrasi API EasyWA.'),

                        TextInput::make('nomor_admin')
                            ->label('Admin Phone Number')
                            ->tel()
                            ->prefix('+62')
                            ->helperText('Nomor HP admin utama untuk notifikasi sistem (Format: 812...)'),
                            
                        TextInput::make('wa_key')
                            ->label('WhatsApp API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Dipakai untuk provider Fonnte. Ambil dari dashboard/token Fonnte.'),
                            
                        TextInput::make('wa_number')
                            ->label('WhatsApp Number')
                            ->tel()
                            ->prefix('+62')
                            ->helperText('Nomor device aktif untuk provider Fonnte.'),

                        TextInput::make('easywa_email')
                            ->label('EasyWA Email')
                            ->helperText('Email akun EasyWA yang terdaftar di dashboard EasyWA.')
                            ->visible(fn ($get) => ($get('wa_provider') ?? 'fonnte') === 'easywa'),

                        TextInput::make('easywa_secret_key')
                            ->label('EasyWA Secret Key')
                            ->password()
                            ->revealable()
                            ->helperText('Secret key EasyWA dari menu API.')
                            ->visible(fn ($get) => ($get('wa_provider') ?? 'fonnte') === 'easywa'),

                        Select::make('easywa_send_type')
                            ->label('EasyWA Send Type')
                            ->options([
                                'sync' => 'Sync',
                                'async' => 'Async',
                            ])
                            ->default('sync')
                            ->native(false)
                            ->helperText('Sync menunggu hasil langsung. Async cocok jika pengiriman ingin diantrikan dengan delay.')
                            ->visible(fn ($get) => ($get('wa_provider') ?? 'fonnte') === 'easywa'),

                        TextInput::make('easywa_send_delay')
                            ->label('EasyWA Delay (detik)')
                            ->numeric()
                            ->default(0)
                            ->helperText('Delay hanya dipakai jika mode EasyWA = async.')
                            ->visible(fn ($get) => ($get('wa_provider') ?? 'fonnte') === 'easywa' && ($get('easywa_send_type') ?? 'sync') === 'async'),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Mail Configuration')
                    ->description('Konfigurasi SMTP/email yang akan dipakai sistem tanpa perlu edit file .env lagi. Nilai ini biasanya didapat dari dashboard provider email seperti Gmail App Password, Mailgun, Brevo, Zoho, Resend SMTP, atau mail server cPanel.')
                    ->headerActions([
                        $this->makeSendTestEmailAction(),
                    ])
                    ->columns(2)
                    ->schema([
                        Select::make('mail_mailer')
                            ->label('Mailer')
                            ->options([
                                'smtp' => 'SMTP',
                                'log' => 'Log',
                            ])
                            ->default('smtp')
                            ->native(false)
                            ->helperText('Pilih SMTP untuk pengiriman email sungguhan. Gunakan Log hanya untuk testing lokal.'),

                        TextInput::make('mail_host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.gmail.com')
                            ->helperText('Host server SMTP dari provider. Contoh: Gmail = smtp.gmail.com, Brevo = smtp-relay.brevo.com, Mailgun = smtp.mailgun.org.'),

                        TextInput::make('mail_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->placeholder('587')
                            ->helperText('Port SMTP dari provider. Umumnya 587 untuk TLS atau 465 untuk SSL.'),

                        TextInput::make('mail_encryption')
                            ->label('Encryption')
                            ->placeholder('tls / ssl')
                            ->helperText('Jenis enkripsi koneksi SMTP. Nilai paling umum adalah tls.'),

                        TextInput::make('mail_username')
                            ->label('SMTP Username')
                            ->helperText('Username/login SMTP dari provider. Biasanya email penuh atau username SMTP khusus yang diberikan provider.'),

                        TextInput::make('mail_password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable()
                            ->helperText('Password SMTP, API key SMTP, atau App Password. Untuk Gmail gunakan App Password, bukan password akun utama.'),

                        TextInput::make('mail_from_address')
                            ->label('From Address')
                            ->email()
                            ->helperText('Alamat pengirim yang tampil ke pembeli. Sebaiknya email valid dari domain/provider yang sama, contoh: no-reply@domainkamu.com.'),

                        TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->helperText('Nama pengirim yang terlihat di inbox pembeli, contoh: '. env('APP_NAME')),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Invoice Delivery Channels')
                    ->description('Atur apakah invoice / update transaksi dikirim via WhatsApp, email, atau keduanya.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('invoice_notify_via_whatsapp')
                            ->label('Kirim Invoice via WhatsApp')
                            ->default(true)
                            ->helperText('Jika aktif, update transaksi/invoice akan dikirim ke WhatsApp pembeli.'),

                        Toggle::make('invoice_notify_via_email')
                            ->label('Kirim Invoice via Email')
                            ->default(true)
                            ->helperText('Jika aktif, update transaksi/invoice akan dikirim ke email pembeli.'),
                    ])
                    ->collapsible(),
                    
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
                    ->collapsible(),
            ])

            ->statePath('data');
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
        $data['wa_provider'] ??= 'fonnte';
        $data['easywa_email'] ??= null;
        $data['easywa_secret_key'] ??= null;
        $data['easywa_send_type'] ??= 'sync';
        $data['easywa_send_delay'] ??= 0;
        $data['captcha_site_key'] ??= env('NOCAPTCHA_SITEKEY');
        $data['captcha_secret'] ??= env('NOCAPTCHA_SECRET');
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

        return $data;
    }
    
    public function save(): void
    {
        $data = $this->form->getState();

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

        // Jangan timpa logo yang sudah ada dengan nilai kosong.
        foreach (['logo_header', 'logo_footer', 'logo_favicon', 'seasonal_background_image'] as $logoField) {
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
        
        // Check if WA Number changed and trigger API update
        if (
            ($data['wa_provider'] ?? 'fonnte') === 'fonnte' &&
            isset($data['wa_number']) &&
            $settings->wa_number !== $data['wa_number']
        ) {
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

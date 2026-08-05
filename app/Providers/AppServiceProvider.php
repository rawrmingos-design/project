<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\Kategori;
use App\Models\MediaAsset;
use App\Models\Tenant;
use App\Observers\PembelianObserver;
use App\Observers\PembayaranObserver;
use App\Observers\KategoriObserver;
use App\Observers\TenantObserver;
use App\Services\OptimizedImageService;
use App\Services\PublicUploadUrlService;
use App\Support\PublicThemeRegistry;
use App\Tenancy\Contracts\DnsResolverInterface;
use App\Tenancy\NativeDnsResolver;
use App\Tenancy\TenantContext;
use Spatie\MediaLibrary\MediaCollections\Events\CollectionHasBeenClearedEvent;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());

        $this->app->bind(
            DnsResolverInterface::class,
            NativeDnsResolver::class,
        );

        $this->app->booting(function (): void {
            $this->registerLivewireUpdateRoutes();
        });
    }

    private function registerLivewireUpdateRoutes(): void
    {
        Livewire::setUpdateRoute(function ($handle) {
            $adminDomain = $this->normalizeHost((string) env('FILAMENT_ADMIN_DOMAIN', ''));

            if ($adminDomain !== '') {
                Route::domain($adminDomain)
                    ->middleware('web')
                    ->post('/livewire/update', $handle)
                    ->name('filament.livewire.update');
            }

            return Route::post('/livewire/update', $handle)
                ->middleware('web')
                ->name('default.livewire.update');
        });
    }

    private function normalizeHost(string $host): string
    {
        $host = trim($host);

        if ($host === '') {
            return '';
        }

        if (str_contains($host, '://')) {
            $host = (string) (parse_url($host, PHP_URL_HOST) ?? '');
        }

        return preg_replace('/:\d+$/', '', $host) ?? '';
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $defaultConfig = [
            'judul_web' => 'Game Top-Up',
            'deskripsi_web' => 'Platform Top-Up Game Terpercaya',
            'keywords' => 'top up, game, diamond, voucher',
            'logo_header' => '/assets/logo/01KGSN7TWDAQXP947X0GH07TDE.webp',
            'logo_footer' => '/assets/logo/01KGSN7TXFTHQYY8T2SM6HQ6S2.png',
            'logo_favicon' => '/assets/logo/favicon.ico',
            'warna1' => '#222222',
            'warna2' => '#d06800',
            'warna3' => '#ffa54a',
            'warna4' => '#ff8040',
            'public_theme' => PublicThemeRegistry::DEFAULT,
            'wa_key' => '',
            'nomor_admin' => '',
            'invoice_notify_via_whatsapp' => true,
            'invoice_notify_via_email' => true,
            'home_popup_enabled' => true,
            'live_sales_enabled' => true,
            'url_wa' => '',
            'url_ig' => '',
            'url_tiktok' => '',
            'url_youtube' => '',
            'url_fb' => '',
            'url_discord' => '',
            'captcha_site_key' => env('NOCAPTCHA_SITEKEY'),
            'captcha_secret' => env('NOCAPTCHA_SECRET'),
            'captcha_enabled' => filter_var((string) env('ADMIN_LOGIN_CAPTCHA_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
            'captcha_bypass' => false,
        ];

        if (!app()->runningInConsole()) {
            // Prevent accidental cross-domain assets when ASSET_URL is set in server env.
            $adminDomain = (string) env('FILAMENT_ADMIN_DOMAIN', '');
            if ($adminDomain !== '' && strcasecmp(request()->getHost(), $adminDomain) === 0) {
                config(['app.asset_url' => null]);
                // Force Filament disk previews to same-origin on admin subdomain.
                // This avoids 403 from main-domain anti-hotlink/anti-leech rules.
                config([
                    'filesystems.disks.assets.url' => '',
                    'filesystems.disks.public.url' => '/storage',
                    'filesystems.disks.banner.url' => '/assets/banner',
                ]);
            }
        }

        // Force HTTPS scheme untuk URL yang di-generate Laravel (route(), url(), asset())
        // Menggunakan $_SERVER langsung karena config() bisa stale dari docker build cache.
        // TrustProxies middleware mengontrol proxy mana yang boleh dipercaya
        // sebelum header forwarded dipakai untuk penentuan scheme/IP.
        if (
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        ) {
            URL::forceScheme('https');
        }

        
        Pembelian::observe(PembelianObserver::class);
        Pembayaran::observe(PembayaranObserver::class);
        Kategori::observe(KategoriObserver::class);
        Tenant::observe(TenantObserver::class);

        Event::listen(MediaHasBeenAddedEvent::class, function (MediaHasBeenAddedEvent $event): void {
            $model = $event->media->model;

            if ($model instanceof MediaAsset) {
                if (config('uploads.disk', 'assets') !== 'assets') {
                    return;
                }

                $path = $model->resolveRelativePath();

                if ($path) {
                    app(OptimizedImageService::class)->ensureVariants(
                        $path,
                        match ($model->folder) {
                            'banner' => 'banner',
                            'artikel' => 'article',
                            'produk' => 'product_logo',
                            'seasonal' => 'banner',
                            default => 'thumbnail',
                        },
                    );
                }
            }

            if (! $model || ! method_exists($model, 'syncLegacyMediaColumn')) {
                return;
            }

            $model->syncLegacyMediaColumn($event->media->collection_name);
        });

        Event::listen(CollectionHasBeenClearedEvent::class, function (CollectionHasBeenClearedEvent $event): void {
            $model = $event->model;

            if (! method_exists($model, 'syncLegacyMediaColumn')) {
                return;
            }

            $model->syncLegacyMediaColumn($event->collectionName);
        });

        // FIX #3 XSS: Register @safeHtml Blade directive
        // Menggunakan strip_tags() native PHP — mempertahankan tag HTML yang aman
        // tapi menghapus <script>, <iframe>, event handlers, dll.
        \Illuminate\Support\Facades\Blade::directive('safeHtml', function ($expression) {
            return "<?php echo \\App\\Helpers\\HtmlSanitizer::clean($expression); ?>";
        });

        $config = (object) $defaultConfig;

        if (! app()->runningInConsole()) {
            try {
                $dbConfig = \DB::table('setting_webs')->where('id', 1)->first();

                if ($dbConfig) {
                    $dbConfig = (array) $dbConfig;
                    unset($dbConfig['tiktok_access_token_encrypted']);
                    $config = (object) array_merge($defaultConfig, $dbConfig);

                    config([
                        'mail.default' => $config->mail_mailer ?: env('MAIL_MAILER', 'smtp'),
                        'mail.mailers.smtp.host' => $config->mail_host ?: env('MAIL_HOST', 'smtp.mailgun.org'),
                        'mail.mailers.smtp.port' => $config->mail_port ?: env('MAIL_PORT', 587),
                        'mail.mailers.smtp.encryption' => $config->mail_encryption ?: env('MAIL_ENCRYPTION', 'tls'),
                        'mail.mailers.smtp.username' => $config->mail_username ?: env('MAIL_USERNAME'),
                        'mail.mailers.smtp.password' => $config->mail_password ?: env('MAIL_PASSWORD'),
                        'mail.from.address' => $config->mail_from_address ?: env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                        'mail.from.name' => $config->mail_from_name ?: env('MAIL_FROM_NAME', 'Example'),
                        'captcha.sitekey' => $config->captcha_site_key ?: env('NOCAPTCHA_SITEKEY'),
                        'captcha.secret' => $config->captcha_secret ?: env('NOCAPTCHA_SECRET'),
                    ]);
                }
            } catch (\Exception $e) {
                // Fallback to default config when database is unavailable.
            }
        }

        $uploadUrl = app(PublicUploadUrlService::class);
        $config->logo_header = $uploadUrl->url($config->logo_header ?? null);
        $config->logo_footer = $uploadUrl->url($config->logo_footer ?? null);
        $config->logo_favicon = $uploadUrl->url($config->logo_favicon ?? null);

        View::share('config', $config);
    }
}

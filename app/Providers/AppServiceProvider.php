<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;

use App\Models\Pembelian;
use App\Models\Kategori;
use App\Models\MediaAsset;
use App\Observers\PembelianObserver;
use App\Observers\KategoriObserver;
use App\Services\OptimizedImageService;
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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
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
        // TrustProxies middleware (dengan $proxies = '*') sudah memastikan header ini trusted.
        if (
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        ) {
            URL::forceScheme('https');
        }

        
        Pembelian::observe(PembelianObserver::class);
        Kategori::observe(KategoriObserver::class);

        Event::listen(MediaHasBeenAddedEvent::class, function (MediaHasBeenAddedEvent $event): void {
            $model = $event->media->model;

            if ($model instanceof MediaAsset) {
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

        try {
            $config = \DB::table('setting_webs')->where('id',1)->first();
            
            // Provide default values if config is null
            if (!$config) {
                $config = (object) [
                    'judul_web' => 'Game Top-Up',
                    'deskripsi_web' => 'Platform Top-Up Game Terpercaya',
                    'keywords' => 'top up, game, diamond, voucher',
                    'logo_favicon' => '/assets/logo/favicon.ico',
                    'warna1' => '#222222',
                    'warna2' => '#d06800', 
                    'warna3' => '#ffa54a',
                    'warna4' => '#ff8040',
                    'wa_key' => '',
                    'nomor_admin' => '',
                    'invoice_notify_via_whatsapp' => true,
                    'invoice_notify_via_email' => true,
                    'home_popup_enabled' => true,
                    'captcha_site_key' => env('NOCAPTCHA_SITEKEY'),
                    'captcha_secret' => env('NOCAPTCHA_SECRET'),
                    'captcha_enabled' => filter_var((string) env('ADMIN_LOGIN_CAPTCHA_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
                    'captcha_bypass' => false,
                ];
            }

            if ($config) {
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
            
            View::share('config', $config);
            
        } catch (\Exception $e) {
            // Fallback config if database is not available
            $config = (object) [
                'judul_web' => 'Game Top-Up',
                'deskripsi_web' => 'Platform Top-Up Game Terpercaya',
                'keywords' => 'top up, game, diamond, voucher',
                'logo_favicon' => '/assets/logo/favicon.ico',
                'warna1' => '#222222',
                'warna2' => '#d06800', 
                'warna3' => '#ffa54a',
                'warna4' => '#ff8040',
                'wa_key' => '',
                'nomor_admin' => '',
                'invoice_notify_via_whatsapp' => true,
                'invoice_notify_via_email' => true,
                'home_popup_enabled' => true,
                'captcha_site_key' => env('NOCAPTCHA_SITEKEY'),
                'captcha_secret' => env('NOCAPTCHA_SECRET'),
                'captcha_enabled' => filter_var((string) env('ADMIN_LOGIN_CAPTCHA_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
                'captcha_bypass' => false,
            ];
            
            View::share('config', $config);
        }
    }
}

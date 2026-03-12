<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use View;
use Illuminate\Support\Facades\URL;

use App\Models\Pembelian;
use App\Observers\PembelianObserver;

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
                    'nomor_admin' => ''
                ];
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
                'nomor_admin' => ''
            ];
            
            View::share('config', $config);
        }
    }
}

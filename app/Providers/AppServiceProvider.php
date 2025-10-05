<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use View;

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

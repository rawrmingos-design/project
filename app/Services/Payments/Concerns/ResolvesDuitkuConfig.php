<?php

namespace App\Services\Payments\Concerns;

use Duitku\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait ResolvesDuitkuConfig
{
    protected function resolveDuitkuSettings(): object
    {
        $settings = DB::table('setting_webs')->where('id', 1)->first();

        if (! $settings
            || blank($settings->duitku_merchant_code ?? null)
            || blank($settings->duitku_merchant_key ?? null)) {
            throw new RuntimeException('Duitku configuration not found.');
        }

        return $settings;
    }

    protected function resolveDuitkuConfig(object $settings): Config
    {
        $config = new Config($settings->duitku_merchant_key, $settings->duitku_merchant_code);
        $config->setSandboxMode(($settings->duitku_mode ?? 'sandbox') === 'sandbox');
        $config->setSanitizedMode(true);
        $config->setDuitkuLogs((bool) config('app.debug'));

        return $config;
    }
}


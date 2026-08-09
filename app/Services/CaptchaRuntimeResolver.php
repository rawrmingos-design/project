<?php

namespace App\Services;

use App\Models\SettingWeb;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CaptchaRuntimeResolver
{
    /**
     * @return array{enabled: bool, bypass: bool, site_key: string, secret: string, is_active: bool}
     */
    public function resolve(): array
    {
        $runtime = [
            'enabled' => filter_var((string) env('ADMIN_LOGIN_CAPTCHA_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
            'bypass' => false,
            'site_key' => trim((string) config('captcha.sitekey', '')),
            'secret' => trim((string) config('captcha.secret', '')),
            'is_active' => false,
        ];

        try {
            if (Schema::hasTable('setting_webs')) {
                $settings = SettingWeb::query()
                    ->select(['captcha_enabled', 'captcha_bypass', 'captcha_site_key', 'captcha_secret'])
                    ->first();

                if ($settings) {
                    $runtime['enabled'] = (bool) ($settings->captcha_enabled ?? $runtime['enabled']);
                    $runtime['bypass'] = (bool) ($settings->captcha_bypass ?? false);
                    $runtime['site_key'] = trim((string) ($settings->captcha_site_key ?: $runtime['site_key']));
                    $runtime['secret'] = trim((string) ($settings->captcha_secret ?: $runtime['secret']));
                }
            }
        } catch (\Throwable) {
            // Keep environment/config fallback when the settings table is unavailable.
        }

        $runtime['is_active'] = $runtime['enabled']
            && ! $runtime['bypass']
            && $runtime['site_key'] !== ''
            && $runtime['secret'] !== '';

        if ($runtime['is_active']) {
            config([
                'captcha.sitekey' => $runtime['site_key'],
                'captcha.secret' => $runtime['secret'],
            ]);
        } elseif ($runtime['enabled'] && ! $runtime['bypass']) {
            Log::warning('CAPTCHA is enabled but not fully configured', [
                'missing_site_key' => $runtime['site_key'] === '',
                'missing_secret' => $runtime['secret'] === '',
            ]);
        }

        return $runtime;
    }
}

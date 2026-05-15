<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SettingWeb;
use Illuminate\Support\Facades\Schema;

trait ResolvesAuthCaptchaRuntime
{
    protected function getAuthCaptchaRuntime(): array
    {
        $runtime = [
            'enabled' => filter_var((string) env('ADMIN_LOGIN_CAPTCHA_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
            'bypass' => false,
            'sitekey' => config('captcha.sitekey'),
            'secret' => config('captcha.secret'),
            'is_active' => false,
        ];

        try {
            if (! Schema::hasTable('setting_webs')) {
                return $this->finalizeCaptchaRuntime($runtime);
            }

            $settings = SettingWeb::query()
                ->select(['captcha_enabled', 'captcha_bypass', 'captcha_site_key', 'captcha_secret'])
                ->first();

            if ($settings) {
                $runtime['enabled'] = (bool) ($settings->captcha_enabled ?? $runtime['enabled']);
                $runtime['bypass'] = (bool) ($settings->captcha_bypass ?? false);
                $runtime['sitekey'] = $settings->captcha_site_key ?: $runtime['sitekey'];
                $runtime['secret'] = $settings->captcha_secret ?: $runtime['secret'];
            }
        } catch (\Throwable) {
            // Keep fallback runtime from env/config when DB unavailable.
        }

        return $this->finalizeCaptchaRuntime($runtime);
    }

    protected function isAuthCaptchaEnabled(): bool
    {
        return (bool) ($this->getAuthCaptchaRuntime()['is_active'] ?? false);
    }

    private function finalizeCaptchaRuntime(array $runtime): array
    {
        $runtime['is_active'] = ($runtime['enabled'] ?? false) === true
            && ($runtime['bypass'] ?? false) !== true
            && filled($runtime['sitekey'] ?? null)
            && filled($runtime['secret'] ?? null);

        if ($runtime['is_active']) {
            config([
                'captcha.sitekey' => $runtime['sitekey'],
                'captcha.secret' => $runtime['secret'],
            ]);
        }

        return $runtime;
    }
}


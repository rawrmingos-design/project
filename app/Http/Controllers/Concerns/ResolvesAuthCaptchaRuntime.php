<?php

namespace App\Http\Controllers\Concerns;

use App\Services\CaptchaRuntimeResolver;

trait ResolvesAuthCaptchaRuntime
{
    protected function getAuthCaptchaRuntime(): array
    {
        $runtime = app(CaptchaRuntimeResolver::class)->resolve();

        return [
            'enabled' => $runtime['enabled'],
            'bypass' => $runtime['bypass'],
            'sitekey' => $runtime['site_key'],
            'secret' => $runtime['secret'],
            'is_active' => $runtime['is_active'],
        ];
    }

    protected function isAuthCaptchaEnabled(): bool
    {
        return (bool) ($this->getAuthCaptchaRuntime()['is_active'] ?? false);
    }
}


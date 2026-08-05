<?php

namespace App\Services;

use App\Models\SettingWeb;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TikTokSettingsService
{
    private ?SettingWeb $settings = null;

    private bool $settingsLoaded = false;

    public function enabled(): bool
    {
        $settings = $this->settings();

        if ($settings && $settings->getRawOriginal('tiktok_tracking_enabled') !== null) {
            return (bool) $settings->tiktok_tracking_enabled
                && filled($this->pixelId())
                && filled($this->accessToken());
        }

        return filled($this->pixelId()) && filled($this->accessToken());
    }

    public function pixelId(): ?string
    {
        $databaseValue = trim((string) ($this->settings()?->tiktok_pixel_id ?? ''));
        $value = $databaseValue !== ''
            ? $databaseValue
            : trim((string) config('services.tiktok.pixel_id'));

        return $value !== '' ? $value : null;
    }

    public function accessToken(): ?string
    {
        $databaseValue = $this->settings()?->decryptedTiktokAccessToken() ?? '';
        $value = $databaseValue !== ''
            ? $databaseValue
            : trim((string) config('services.tiktok.access_token'));

        return $value !== '' ? $value : null;
    }

    public function testEventCode(): ?string
    {
        $databaseValue = trim((string) ($this->settings()?->tiktok_test_event_code ?? ''));
        $value = $databaseValue !== ''
            ? $databaseValue
            : trim((string) config('services.tiktok.test_event_code'));

        return $value !== '' ? $value : null;
    }

    public function hasDatabaseAccessToken(): bool
    {
        return $this->settings()?->hasTiktokAccessToken() ?? false;
    }

    public function accessTokenSource(): string
    {
        if ($this->hasDatabaseAccessToken()) {
            return 'database';
        }

        return filled(config('services.tiktok.access_token')) ? 'environment' : 'missing';
    }

    public function pixelIdSource(): string
    {
        if (filled($this->settings()?->tiktok_pixel_id)) {
            return 'database';
        }

        return filled(config('services.tiktok.pixel_id')) ? 'environment' : 'missing';
    }

    public function settings(): ?SettingWeb
    {
        if ($this->settingsLoaded) {
            return $this->settings;
        }

        $this->settingsLoaded = true;

        try {
            if (! Schema::hasTable('setting_webs')
                || ! Schema::hasColumn('setting_webs', 'tiktok_tracking_enabled')) {
                return null;
            }

            return $this->settings = SettingWeb::query()->find(1);
        } catch (Throwable) {
            return null;
        }
    }
}

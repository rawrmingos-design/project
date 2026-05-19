<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Models\SettingWeb;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Schemas\Schema;
use AbanoubNassem\FilamentGRecaptchaField\Forms\Components\GRecaptcha;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class Login extends BaseLogin
{
    protected ?array $captchaRuntime = null;

    public function mount(): void
    {
        $auth = Filament::auth();

        if ($auth->check()) {
            $user = $auth->user();
            $panel = Filament::getCurrentOrDefaultPanel();

            if (($user instanceof FilamentUser) && $user->canAccessPanel($panel)) {
                redirect()->intended(Filament::getUrl());

                return;
            }

            $auth->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),

                GRecaptcha::make('captcha')
                    ->required(fn (): bool => $this->isCaptchaEnabled())
                    ->dehydrated(fn (): bool => $this->isCaptchaEnabled())
                    ->validationMessages([
                        'required' => 'Captcha wajib diverifikasi.',
                        'captcha' => 'Verifikasi captcha gagal. Silakan refresh halaman dan coba lagi.',
                    ])
                    ->visible(fn (): bool => $this->isCaptchaEnabled() && blank($this->userUndertakingMultiFactorAuthentication)),
            ]);
    }

    protected function isCaptchaEnabled(): bool
    {
        $runtime = $this->getCaptchaRuntime();

        if (($runtime['bypass'] ?? false) === true) {
            return false;
        }

        if (($runtime['enabled'] ?? true) !== true) {
            return false;
        }

        return filled($runtime['sitekey'] ?? null) && filled($runtime['secret'] ?? null);
    }

    protected function getCaptchaRuntime(): array
    {
        if ($this->captchaRuntime !== null) {
            return $this->captchaRuntime;
        }

        $runtime = [
            'enabled' => filter_var((string) env('ADMIN_LOGIN_CAPTCHA_ENABLED', 'true'), FILTER_VALIDATE_BOOL),
            'bypass' => false,
            'sitekey' => config('captcha.sitekey'),
            'secret' => config('captcha.secret'),
        ];

        try {
            if (! SchemaFacade::hasTable('setting_webs')) {
                return $this->captchaRuntime = $runtime;
            }

            $settings = SettingWeb::query()
                ->select(['captcha_enabled', 'captcha_bypass', 'captcha_site_key', 'captcha_secret'])
                ->first();

            if ($settings) {
                $runtime['enabled'] = (bool) ($settings->captcha_enabled ?? $runtime['enabled']);
                $runtime['bypass'] = (bool) ($settings->captcha_bypass ?? false);
                $runtime['sitekey'] = $settings->captcha_site_key ?: $runtime['sitekey'];
                $runtime['secret'] = $settings->captcha_secret ?: $runtime['secret'];

                config([
                    'captcha.sitekey' => $runtime['sitekey'],
                    'captcha.secret' => $runtime['secret'],
                ]);
            }
        } catch (\Throwable) {
            // Keep fallback from env/config when DB unavailable.
        }

        return $this->captchaRuntime = $runtime;
    }
}

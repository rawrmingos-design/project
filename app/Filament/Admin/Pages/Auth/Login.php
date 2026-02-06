<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use AbanoubNassem\FilamentGRecaptchaField\Forms\Components\GRecaptcha;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),

                GRecaptcha::make('captcha')
                    ->required()
                    ->visible(fn () => blank($this->userUndertakingMultiFactorAuthentication)),
            ]);
    }

    protected function getFormValidationRules(): array
    {
        return [
            'captcha' => ['required', 'captcha'],
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\Tenants\Schemas;

use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use JsonException;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tenant Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Store Name')
                            ->required()
                            ->maxLength(255),

                        Select::make('owner_user_id')
                            ->label('Owner')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        TextInput::make('subdomain')
                            ->required()
                            ->maxLength(63)
                            ->rules([
                                'alpha_dash:ascii',
                                fn () => Rule::notIn(Tenant::RESERVED_SUBDOMAINS),
                            ])
                            ->unique(ignoreRecord: true)
                            ->helperText('Reserved: ' . implode(', ', Tenant::RESERVED_SUBDOMAINS)),

                        TextInput::make('custom_domain')
                            ->nullable()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Select::make('tier')
                            ->required()
                            ->default('starter')
                            ->options([
                                'starter' => 'Starter',
                                'business' => 'Business',
                                'enterprise' => 'Enterprise',
                            ]),

                        Select::make('status')
                            ->required()
                            ->default(Tenant::STATUS_PENDING_PAYMENT)
                            ->options([
                                Tenant::STATUS_PENDING_PAYMENT => 'Pending Payment',
                                Tenant::STATUS_ACTIVE => 'Active',
                                Tenant::STATUS_SUSPENDED => 'Suspended',
                                Tenant::STATUS_CANCELLED => 'Cancelled',
                            ]),
                    ]),

                Section::make('Configuration')
                    ->columns(1)
                    ->collapsible()
                    ->schema([
                        self::jsonTextarea('margin_config', 'Margin Config JSON'),
                        self::jsonTextarea('theme', 'Theme JSON'),
                        self::jsonTextarea('settings', 'Settings JSON'),
                    ]),
            ]);
    }

    private static function jsonTextarea(string $name, string $label): Textarea
    {
        return Textarea::make($name)
            ->label($label)
            ->rules(['nullable', 'json'])
            ->nullable()
            ->formatStateUsing(fn ($state): ?string => $state === null ? null : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
            ->dehydrateStateUsing(function (?string $state): ?array {
                if (blank($state)) {
                    return null;
                }

                try {
                    $decoded = json_decode($state, true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    return null;
                }

                return is_array($decoded) ? $decoded : null;
            });
    }
}

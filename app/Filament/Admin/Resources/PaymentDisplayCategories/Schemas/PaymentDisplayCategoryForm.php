<?php

namespace App\Filament\Admin\Resources\PaymentDisplayCategories\Schemas;

use App\Tenancy\TenantContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class PaymentDisplayCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Display Category')
                    ->schema([
                        TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(100)
                            ->rules([
                                fn (?Model $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                    $tenantId = app()->bound(TenantContext::class)
                                        ? app(TenantContext::class)->id()
                                        : null;

                                    if ($tenantId === null) {
                                        return;
                                    }

                                    $rule = Rule::unique('payment_display_categories', 'label')
                                        ->where('tenant_id', $tenantId);

                                    if ($record) {
                                        $rule->ignore($record->getKey());
                                    }

                                    $validator = validator(
                                        [$attribute => $value],
                                        [$attribute => $rule],
                                    );

                                    if ($validator->fails()) {
                                        $fail('Label already exists for this store.');
                                    }
                                },
                            ])
                            ->helperText('Wajib diisi. Nama kategori tampilan yang unik per store.')
                            ->validationMessages([
                                'required' => 'Label wajib diisi.',
                                'max' => 'Label maksimal 100 karakter.',
                            ]),

                        Select::make('display_style')
                            ->label('Display Style')
                            ->options([
                                'flat' => 'Flat',
                                'accordion' => 'Accordion',
                            ])
                            ->required()
                            ->helperText('Pilih gaya tampilan: Flat (langsung tampil) atau Accordion (bisa di-collapse).')
                            ->validationMessages([
                                'required' => 'Display style wajib dipilih.',
                            ]),

                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(999)
                            ->default(0)
                            ->helperText('Urutan tampilan (0–999). Angka kecil tampil lebih dulu.')
                            ->validationMessages([
                                'required' => 'Sort order wajib diisi.',
                                'min' => 'Sort order minimal 0.',
                                'max' => 'Sort order maksimal 999.',
                            ]),

                        Toggle::make('is_visible')
                            ->label('Visible')
                            ->default(true)
                            ->helperText('Jika nonaktif, kategori ini tidak ditampilkan di halaman checkout.'),

                        TextInput::make('icon')
                            ->label('Icon')
                            ->maxLength(50)
                            ->nullable()
                            ->helperText('Opsional. Identifier ikon (maks 50 karakter).')
                            ->validationMessages([
                                'max' => 'Icon maksimal 50 karakter.',
                            ]),
                    ]),
            ]);
    }
}

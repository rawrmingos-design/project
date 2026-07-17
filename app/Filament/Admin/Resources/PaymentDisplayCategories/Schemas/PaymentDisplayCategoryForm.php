<?php

namespace App\Filament\Admin\Resources\PaymentDisplayCategories\Schemas;

use App\Models\PaymentDisplayCategory;
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
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, ?string $state): void {
                                $set('code', PaymentDisplayCategory::normalizeCode(null, $state));
                            })
                            ->rules([
                                fn (?Model $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                    $rule = Rule::unique('payment_display_categories', 'label')
                                        ->whereNull('tenant_id');

                                    if ($record) {
                                        $rule->ignore($record->getKey());
                                    }

                                    $validator = validator(
                                        [$attribute => $value],
                                        [$attribute => $rule],
                                    );

                                    if ($validator->fails()) {
                                        $fail('Label already exists in the global catalog.');
                                    }
                                },
                            ])
                            ->helperText('Wajib diisi. Nama kategori tampilan yang unik per store.')
                            ->validationMessages([
                                'required' => 'Label wajib diisi.',
                                'max' => 'Label maksimal 100 karakter.',
                            ]),

                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, ?string $state): void {
                                $set('code', PaymentDisplayCategory::normalizeCode($state));
                            })
                            ->dehydrateStateUsing(fn (?string $state): string => PaymentDisplayCategory::normalizeCode($state))
                            ->rules([
                                fn (?Model $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                    $code = PaymentDisplayCategory::normalizeCode($value);
                                    $rule = Rule::unique('payment_display_categories', 'code')
                                        ->whereNull('tenant_id');

                                    if ($record) {
                                        $rule->ignore($record->getKey());
                                    }

                                    $validator = validator(
                                        [$attribute => $code],
                                        [$attribute => $rule],
                                    );

                                    if ($validator->fails()) {
                                        $fail('Code already exists in the global catalog.');
                                    }
                                },
                            ])
                            ->helperText('Kode stabil untuk sync ke tipe method. Contoh: qris, e-walet, virtual-account.')
                            ->validationMessages([
                                'required' => 'Code wajib diisi.',
                                'max' => 'Code maksimal 100 karakter.',
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

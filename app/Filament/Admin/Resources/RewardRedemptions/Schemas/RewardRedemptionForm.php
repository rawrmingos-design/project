<?php

namespace App\Filament\Admin\Resources\RewardRedemptions\Schemas;

use App\Models\RewardRedemption;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RewardRedemptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penukaran')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')->label('Kode')->disabled(),
                        TextInput::make('reward_name')->label('Hadiah')->disabled(),
                        TextInput::make('points_spent')->label('Poin Terpakai')->disabled()->suffix(' poin'),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                RewardRedemption::STATUS_PENDING    => 'Menunggu Diproses',
                                RewardRedemption::STATUS_PROCESSING => 'Diproses',
                                RewardRedemption::STATUS_SHIPPED    => 'Dikirim',
                                RewardRedemption::STATUS_COMPLETED  => 'Selesai',
                            ])
                            ->required()
                            ->helperText('Untuk menolak & mengembalikan poin, pakai tombol "Tolak & Refund" di daftar.'),
                    ]),

                Section::make('Alamat Penerima')
                    ->columns(2)
                    ->schema([
                        TextInput::make('recipient_name')->label('Nama Penerima')->disabled(),
                        TextInput::make('recipient_phone')->label('No. HP')->disabled(),
                        Textarea::make('address')->label('Alamat')->disabled()->columnSpanFull(),
                        TextInput::make('city')->label('Kota')->disabled(),
                        TextInput::make('province')->label('Provinsi')->disabled(),
                        TextInput::make('postal_code')->label('Kode Pos')->disabled(),
                    ]),

                Section::make('Pengiriman')
                    ->columns(2)
                    ->schema([
                        TextInput::make('courier')
                            ->label('Kurir')
                            ->placeholder('mis. JNE, J&T, SiCepat'),

                        TextInput::make('tracking_no')
                            ->label('No. Resi'),

                        Textarea::make('admin_note')
                            ->label('Catatan Admin')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

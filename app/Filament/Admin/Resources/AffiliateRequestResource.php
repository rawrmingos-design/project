<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AffiliateRequestResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;
class AffiliateRequestResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Permintaan Affiliate';
    
    protected static ?string $pluralLabel = 'Permintaan Affiliate';

    protected static UnitEnum|string|null $navigationGroup = 'Affiliate System';
    
    protected static ?string $slug = 'affiliate-requests';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled(),
                TextInput::make('username')
                    ->disabled(),
                TextInput::make('email')
                    ->disabled(),
                Select::make('affiliate_status')
                    ->options([
                        'inactive' => 'Inactive',
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'rejected' => 'Rejected',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Tgl Daftar'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                BadgeColumn::make('affiliate_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve')
                    ->label('Terima')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Catatan Review')
                            ->rows(3)
                            ->maxLength(600),
                    ])
                    ->visible(fn (User $record): bool => $record->normalizedAffiliateStatus() === 'pending')
                    ->action(function (array $data, User $record) {
                        $record->affiliate_status = 'active';
                        // Generate referral code if not exists
                        if (!$record->referral_code) {
                            $record->referral_code = 'REF-' . strtoupper(Str::random(6));
                        }
                        static::recordAffiliateReview($record, 'approved', $data['review_note'] ?? null);
                        $record->save();
                    })
                    ->requiresConfirmation(),
                    
                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Catatan Review')
                            ->rows(3)
                            ->maxLength(600),
                    ])
                    ->visible(fn (User $record): bool => $record->normalizedAffiliateStatus() === 'pending')
                    ->action(function (array $data, User $record) {
                        $record->affiliate_status = 'rejected';
                        static::recordAffiliateReview($record, 'rejected', $data['review_note'] ?? null);
                        $record->save();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliateRequests::route('/'),
        ];
    }

    private static function recordAffiliateReview(User $record, string $decision, ?string $note): void
    {
        $meta = is_array($record->affiliate_application_meta)
            ? $record->affiliate_application_meta
            : [];

        $history = data_get($meta, 'review_history');
        if (! is_array($history)) {
            $history = [];
        }

        $review = [
            'decision' => $decision,
            'note' => blank($note) ? null : trim((string) $note),
            'reviewed_at' => now()->toIso8601String(),
            'reviewed_by_id' => Auth::id(),
            'reviewed_by_username' => Auth::user()?->username,
        ];

        $history[] = $review;

        $record->affiliate_application_meta = array_merge($meta, [
            'review_history' => $history,
            'review_last' => $review,
        ]);
    }
}

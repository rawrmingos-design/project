<?php

namespace App\Filament\Admin\Resources\ResellerApplications;

use App\Filament\Admin\Resources\ResellerApplications\Pages\ListResellerApplications;
use App\Filament\Admin\Resources\ResellerApplications\Pages\ViewResellerApplication;
use App\Models\ResellerApplication;
use App\Services\ResellerApplicationReviewService;
use BackedEnum;
use UnitEnum;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResellerApplicationResource extends Resource
{
    protected static ?string $model = ResellerApplication::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static UnitEnum|string|null $navigationGroup = 'Reseller Management';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Reseller Application';

    protected static ?string $pluralModelLabel = 'Reseller Applications';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'user:id,username,email',
                'reviewer:id,username',
            ]))
            ->columns([
                TextColumn::make('user.username')
                    ->label('Applicant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('business_name')
                    ->label('Business Name')
                    ->searchable()
                    ->limit(30),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'secondary' => 'inactive',
                    ]),
                TextColumn::make('applied_at')
                    ->label('Applied')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('reviewer.username')
                    ->label('Reviewed By')
                    ->placeholder('Not reviewed')
                    ->toggleable(),
            ])
            ->defaultSort('applied_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('note')
                            ->label('Admin Note (optional)')
                            ->rows(3),
                    ])
                    ->visible(fn(ResellerApplication $record): bool => $record->isPending())
                    ->action(function (ResellerApplication $record, array $data): void {
                        $service = app(ResellerApplicationReviewService::class);
                        $admin = auth()->user();

                        $service->approve($record, $admin, $data);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Application Approved')
                            ->body("Reseller application for {$record->user->username} has been approved.")
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn(ResellerApplication $record): bool => $record->isPending())
                    ->action(function (ResellerApplication $record, array $data): void {
                        $service = app(ResellerApplicationReviewService::class);
                        $admin = auth()->user();

                        $service->reject($record, $admin, $data['reason']);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Application Rejected')
                            ->body("Reseller application for {$record->user->username} has been rejected.")
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResellerApplications::route('/'),
            'view' => ViewResellerApplication::route('/{record}'),
        ];
    }
}

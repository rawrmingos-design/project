<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RecentActivities extends BaseWidget
{
    protected static ?string $heading = 'Leaderboard';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->latest('updated_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('User')
                    ->searchable()
                    ->weight('bold'),
                    
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Tier')
                    ->colors([
                        'danger' => 'Admin',
                        'info' => 'Platinum',
                        'warning' => 'Gold',
                        'success' => 'Member',
                    ]),
                    
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money('IDR')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record): string => route('filament.admin.resources.users.edit', $record))
                    ->openUrlInNewTab(),
            ])
            ->paginated(false);
    }
}

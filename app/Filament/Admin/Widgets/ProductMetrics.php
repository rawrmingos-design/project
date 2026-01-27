<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Layanan;
use Illuminate\Database\Eloquent\Builder;

class ProductMetrics extends BaseWidget
{
    protected static ?string $heading = 'Best-selling Product';
    
    protected static ?int $sort = 6;
    
    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Layanan::query()
                    ->withCount('pembelians')
                    ->orderByDesc('pembelians_count')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('layanan')
                    ->label('Product')
                    ->weight('bold')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('pembelians_count')
                    ->label('Transaction')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Sales')
                    ->getStateUsing(function (Layanan $record) {
                        return 'IDR ' . number_format($record->pembelians->where('status', 'Success')->sum('harga'), 0, ',', '.');
                    }),
            ])
            ->paginated(false);
    }
}

<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;

class StatsOverviewWidget extends ChartWidget
{
    protected ?string $heading = 'Stats Overview Widget';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

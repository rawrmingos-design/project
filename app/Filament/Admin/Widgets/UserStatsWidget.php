<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;

class UserStatsWidget extends ChartWidget
{
    protected ?string $heading = 'User Stats Widget';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

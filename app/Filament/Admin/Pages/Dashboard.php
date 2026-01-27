<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public string $view = 'filament.admin.pages.dashboard';
    
    public function getColumns(): int | array
    {
        return 3;
    }
}

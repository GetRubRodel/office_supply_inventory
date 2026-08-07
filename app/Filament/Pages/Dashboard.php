<?php

namespace App\Filament\Pages;

use App\Support\CurrentUser;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('dashboard.view');
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}

<?php

namespace App\Filament\Resources\BacResolutionResource\Widgets;

use App\Support\CurrentUser;

use App\Models\BacResolution;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BacStatsOverviewWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('bac_resolutions.view') ?? false;
    }

    protected function getStats(): array
    {
        $totalResolutions = BacResolution::count();

        return [
            Stat::make('Total BAC Resolutions', number_format($totalResolutions))
                ->description('All BAC Resolution records')
                ->descriptionIcon('heroicon-o-document-check')
                ->color('primary'),
        ];
    }
}

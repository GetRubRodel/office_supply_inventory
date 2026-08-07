<?php

namespace App\Filament\Resources\RpciResource\Widgets;

use App\Support\CurrentUser;

use App\Models\Rpci;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RpciStatsOverview extends BaseWidget
{
    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('rpcis.view') ?? false;
    }

    protected function getStats(): array
    {
        $total = Rpci::count();
        $pending = Rpci::where('status', 'draft')->count();
        $finalized = Rpci::where('status', 'finalized')->count();

        return [
            Stat::make('Total RPCI Reports', $total)
                ->description('All physical count reports')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),
            Stat::make('Pending (Draft)', $pending)
                ->description('Reports awaiting finalization')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Finalized Reports', $finalized)
                ->description('Completed physical count reports')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}

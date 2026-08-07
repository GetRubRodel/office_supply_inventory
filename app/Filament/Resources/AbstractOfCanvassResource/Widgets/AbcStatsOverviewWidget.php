<?php

namespace App\Filament\Resources\AbstractOfCanvassResource\Widgets;

use App\Support\CurrentUser;

use App\Models\AbstractOfCanvass;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AbcStatsOverviewWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('abstracts_of_canvass.view') ?? false;
    }

    protected function getStats(): array
    {
        $totalAbstracts = AbstractOfCanvass::count();

        $pendingEvaluation = AbstractOfCanvass::where('status', 'draft')
            ->whereNull('approved_by_name')
            ->count();

        $completedAbstracts = AbstractOfCanvass::whereNotNull('approved_by_name')
            ->whereNotNull('recommendation')
            ->count();

        $winningSuppliers = AbstractOfCanvass::whereNotNull('approved_by_name')
            ->whereNotNull('recommendation')
            ->distinct('recommendation')
            ->count('recommendation');

        return [
            Stat::make('Total Abstracts', number_format($totalAbstracts))
                ->description('All ABC records')
                ->descriptionIcon('heroicon-o-scale')
                ->color('primary'),

            Stat::make('Pending Evaluation', number_format($pendingEvaluation))
                ->description('Awaiting evaluation')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Completed Abstracts', number_format($completedAbstracts))
                ->description('With winning supplier selected')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Winning Suppliers', number_format($winningSuppliers))
                ->description('Distinct suppliers awarded')
                ->descriptionIcon('heroicon-o-trophy')
                ->color('info'),
        ];
    }
}

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

        // Approval details are sourced from the originating RFQ, so the ABC's
        // own approved_by_* columns are never populated. Pending/completed are
        // derived from the ABC lifecycle instead: draft ABCs still awaiting
        // evaluation vs. ABCs with a winning supplier that have been converted
        // into a BAC Resolution.
        $pendingEvaluation = AbstractOfCanvass::where('status', AbstractOfCanvass::STATUS_DRAFT)
            ->whereDoesntHave('bacResolutions')
            ->count();

        $completedAbstracts = AbstractOfCanvass::whereNotNull('recommendation')
            ->whereHas('bacResolutions')
            ->count();

        $winningSuppliers = AbstractOfCanvass::whereNotNull('recommendation')
            ->whereHas('bacResolutions')
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

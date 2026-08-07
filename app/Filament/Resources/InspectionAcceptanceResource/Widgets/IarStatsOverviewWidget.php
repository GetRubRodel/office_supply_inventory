<?php

namespace App\Filament\Resources\InspectionAcceptanceResource\Widgets;

use App\Support\CurrentUser;

use App\Models\InspectionAcceptanceReport;
use App\Models\IarItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IarStatsOverviewWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('inspection_acceptance_reports.view') ?? false;
    }

    protected function getStats(): array
    {
        $totalIars = InspectionAcceptanceReport::count();

        $pendingStockIn = InspectionAcceptanceReport::where('status', InspectionAcceptanceReport::STATUS_APPROVED)
            ->count();

        $stockedIn = InspectionAcceptanceReport::where('status', InspectionAcceptanceReport::STATUS_STOCKED_IN)
            ->count();

        $totalReceivedItems = IarItem::sum('quantity_accepted');

        return [
            Stat::make('Total IARs', number_format($totalIars))
                ->description('All IAR records')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('primary'),

            Stat::make('Pending Stock In', number_format($pendingStockIn))
                ->description('Approved, awaiting stock in')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Stocked In', number_format($stockedIn))
                ->description('Processed and added to inventory')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Total Received Items', number_format($totalReceivedItems))
                ->description('Cumulative quantity accepted')
                ->descriptionIcon('heroicon-o-archive-box')
                ->color('info'),
        ];
    }
}

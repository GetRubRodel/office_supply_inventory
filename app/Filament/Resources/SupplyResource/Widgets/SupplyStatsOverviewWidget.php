<?php

namespace App\Filament\Resources\SupplyResource\Widgets;

use App\Support\CurrentUser;

use App\Models\Supply;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupplyStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('supplies.view') ?? false;
    }

    protected function getStats(): array
    {
        $totalSupplies = Supply::count();

        $availableQty = Supply::where('status', 'active')->sum('current_stock');

        $lowStockCount = Supply::where('status', 'active')
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->count();

        $totalValue = Supply::where('status', 'active')
            ->selectRaw('COALESCE(SUM(total_cost), 0) as total_value')
            ->value('total_value') ?? 0;

        return [
            Stat::make('Total Supplies', number_format($totalSupplies))
                ->description('All supplies in inventory')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Available Inventory', number_format($availableQty))
                ->description('Total units across all active supplies')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info'),

            Stat::make('Low Stock Items', number_format($lowStockCount))
                ->description('Items at or below reorder level')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Total Inventory Value', '₱' . number_format($totalValue, 2))
                ->description('Total value at moving average cost')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}

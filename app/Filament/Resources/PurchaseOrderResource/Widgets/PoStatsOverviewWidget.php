<?php

namespace App\Filament\Resources\PurchaseOrderResource\Widgets;

use App\Support\CurrentUser;

use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PoStatsOverviewWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('purchase_orders.view') ?? false;
    }

    protected function getStats(): array
    {
        $totalPos = PurchaseOrder::count();

        $pendingPos = PurchaseOrder::whereDoesntHave('iar')->count();

        $completedPos = PurchaseOrder::whereHas('iar')->count();

        $totalSuppliers = PurchaseOrder::whereNotNull('supplier_id')
            ->distinct('supplier_id')
            ->count('supplier_id');

        return [
            Stat::make('Total Purchase Orders', number_format($totalPos))
                ->description('All PO records')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make('Pending Purchase Orders', number_format($pendingPos))
                ->description('Awaiting delivery or completion')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Completed Purchase Orders', number_format($completedPos))
                ->description('With linked IAR')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Total Suppliers', number_format($totalSuppliers))
                ->description('Unique suppliers in POs')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),
        ];
    }
}

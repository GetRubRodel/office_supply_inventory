<?php

namespace App\Filament\Widgets;

use App\Support\CurrentUser;

use App\Models\RequisitionItem;
use App\Models\Supply;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('dashboard.view') ?? false;
    }

    protected function getStats(): array
    {
        $user = CurrentUser::get();
        $stats = [];

        // Issued Items — visible to all dashboard viewers (RIS-related)
        $issuedQty = RequisitionItem::whereHas('requisition', function ($q) use ($user) {
            $q->whereIn('status', ['issued', 'pending_receipt', 'received']);

            // Staff (own-records-only) see only their own issued quantities.
            if ($user?->isOwnRecordsOnly()) {
                $q->where('user_id', $user->id);
            }
        })->sum('quantity_issued');

        $stats[] = Stat::make('Issued Items', number_format($issuedQty))
            ->description('Total quantity issued via RIS')
            ->descriptionIcon('heroicon-m-document-text')
            ->color('info');

        // Supply-specific stats — only for users with supplies.view
        if ($user?->hasPermissionTo('supplies.view')) {
            $totalSupplies = Supply::count();
            $availableInventory = Supply::where('status', 'active')->sum('current_stock');
            $inventoryValue = Supply::where('status', 'active')->sum('total_cost');

            $stats[] = Stat::make('Total Supplies', number_format($totalSupplies))
                ->description('Unique supply items')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary');

            $stats[] = Stat::make('Available Inventory', number_format($availableInventory))
                ->description('Total available units')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning');

            $stats[] = Stat::make('Inventory Value', '₱' . number_format($inventoryValue, 2))
                ->description('Total value at moving average cost')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success');
        }

        return $stats;
    }
}

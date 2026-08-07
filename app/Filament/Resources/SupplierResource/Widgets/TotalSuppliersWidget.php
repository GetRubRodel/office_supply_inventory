<?php

namespace App\Filament\Resources\SupplierResource\Widgets;

use App\Support\CurrentUser;

use App\Models\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalSuppliersWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('suppliers.view') ?? false;
    }

    protected function getStats(): array
    {
        $total = Supplier::count();

        return [
            Stat::make('Total Suppliers', number_format($total))
                ->description('All registered suppliers in the system')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
        ];
    }
}

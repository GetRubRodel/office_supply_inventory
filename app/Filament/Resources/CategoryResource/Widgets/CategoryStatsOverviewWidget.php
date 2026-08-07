<?php

namespace App\Filament\Resources\CategoryResource\Widgets;

use App\Support\CurrentUser;

use App\Models\Category;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CategoryStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('categories.view') ?? false;
    }

    protected function getStats(): array
    {
        $total = Category::count();
        $active = Category::where('status', 'active')->count();
        $inactive = Category::where('status', 'inactive')->count();

        return [
            Stat::make('Total Categories', number_format($total))
                ->description('All categories in the system')
                ->descriptionIcon('heroicon-m-tag')
                ->color('primary'),

            Stat::make('Active Categories', number_format($active))
                ->description('Currently active categories')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Inactive Categories', number_format($inactive))
                ->description('Disabled or inactive categories')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($inactive > 0 ? 'warning' : 'gray'),
        ];
    }
}

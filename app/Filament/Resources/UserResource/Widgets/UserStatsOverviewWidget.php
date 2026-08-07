<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Support\CurrentUser;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canAccess(): bool
    {
        return CurrentUser::get()?->hasPermissionTo('users.view') ?? false;
    }

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $admins = User::where('role', User::ROLE_ADMIN)->count();
        $regionalDirectors = User::where('role', User::ROLE_REGIONAL_DIRECTOR)->count();
        $divisionChiefs = User::where('role', User::ROLE_DIVISION_CHIEF)->count();
        $supplyOfficers = User::where('role', User::ROLE_SUPPLY_OFFICER)->count();
        $propertyCustodians = User::where('role', User::ROLE_PROPERTY_CUSTODIAN)->count();
        $staff = User::where('role', User::ROLE_STAFF)->count();

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description('All registered accounts')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Administrators', number_format($admins))
                ->description('System administrators')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('danger'),

            Stat::make('Regional Directors', number_format($regionalDirectors))
                ->description('Regional director accounts')
                ->descriptionIcon('heroicon-m-map')
                ->color('info'),

            Stat::make('Division Chiefs', number_format($divisionChiefs))
                ->description('Division chief accounts')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Supply Officers', number_format($supplyOfficers))
                ->description('Supply officer accounts')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning'),

            Stat::make('Property Custodians', number_format($propertyCustodians))
                ->description('Property custodian accounts')
                ->descriptionIcon('heroicon-m-key')
                ->color('gray'),

            Stat::make('Staff', number_format($staff))
                ->description('General staff accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}

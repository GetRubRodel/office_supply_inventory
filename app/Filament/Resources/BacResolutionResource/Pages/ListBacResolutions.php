<?php

namespace App\Filament\Resources\BacResolutionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\BacResolutionResource;
use App\Filament\Resources\BacResolutionResource\Widgets\BacStatsOverviewWidget;
use App\Filament\Resources\BacResolutionResource\Widgets\NoEligibleAbcWarning;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBacResolutions extends ListRecords
{
    protected static string $resource = BacResolutionResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New BAC Resolution')
                ->visible(fn (): bool => CurrentUser::get()?->canManageProcurement()
                    && CurrentUser::get()?->hasPermissionTo('bac_resolutions.create')
                    && BacResolutionResource::hasEligibleSource()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            NoEligibleAbcWarning::class,
            BacStatsOverviewWidget::class,
        ];
    }
}

<?php

namespace App\Filament\Resources\BacResolutionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\BacResolutionResource;
use App\Filament\Resources\BacResolutionResource\Widgets\BacStatsOverviewWidget;
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
                ->visible(fn (): bool => CurrentUser::get()?->canManageLegal()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BacStatsOverviewWidget::class,
        ];
    }
}

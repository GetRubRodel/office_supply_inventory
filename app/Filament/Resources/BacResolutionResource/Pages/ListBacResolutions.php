<?php

namespace App\Filament\Resources\BacResolutionResource\Pages;

use App\Support\CurrentUser;

use App\Filament\Resources\BacResolutionResource;
use App\Filament\Resources\BacResolutionResource\Widgets\BacStatsOverviewWidget;
use App\Filament\Resources\BacResolutionResource\Widgets\NoEligibleIarWarning;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBacResolutions extends ListRecords
{
    protected static string $resource = BacResolutionResource::class;

    protected ?string $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('New BAC Resolution')
                ->icon('heroicon-o-plus')
                ->color('primary')
                // Hidden when no eligible IAR exists — a BAC Resolution can
                // only be created from an unconverted Inspection and
                // Acceptance Report (the warning banner explains why).
                ->visible(fn (): bool => CurrentUser::get()?->canManageLegal()
                    && BacResolutionResource::hasEligibleIar())
                ->action(fn () => $this->redirect(
                    BacResolutionResource::getUrl('create')
                )),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BacStatsOverviewWidget::class,
            NoEligibleIarWarning::class,
        ];
    }
}
